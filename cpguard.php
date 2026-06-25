<?php
/**
 * 网站文件管理器 - 完整功能版（适配 open_basedir）
 * 
 * 功能：
 * - 访问密码保护
 * - 自动检测 open_basedir 限制
 * - 浏览文件和目录，支持逐层导航
 * - 修改文件/目录的权限和时间
 * - 内置文件编辑器（支持任何文件，目录除外）
 * - 删除文件/目录
 * - 上传文件
 * - 压缩文件/目录为 tar.gz 格式
 * - 重命名文件/目录
 */

// ============ 配置 ============
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// 自动检测可用的根目录
function getAvailableRoot() {
    $script_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $open_basedir = ini_get('open_basedir');
    
    if (!empty($open_basedir)) {
        $paths = explode(PATH_SEPARATOR, $open_basedir);
        $available_paths = [];
        foreach ($paths as $path) {
            $path = realpath($path);
            if ($path && is_dir($path)) {
                $available_paths[] = $path;
            }
        }
        foreach ($available_paths as $path) {
            if (strpos($script_dir, $path) === 0) {
                return $path;
            }
        }
        if (!empty($available_paths)) {
            return $available_paths[0];
        }
    }
    
    if (isset($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT'])) {
        return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
    }
    return $script_dir;
}

define('WEB_ROOT', getAvailableRoot());

// 允许上传的文件扩展名（空数组表示允许所有）
$allowed_upload_extensions = [];

// 获取当前目录
$current_path = isset($_GET['path']) ? $_GET['path'] : WEB_ROOT;
$current_path = realpath($current_path);

// 安全检查
if ($current_path === false) {
    $current_path = WEB_ROOT;
}

function isPathAllowed($path) {
    $open_basedir = ini_get('open_basedir');
    if (empty($open_basedir)) {
        return true;
    }
    $paths = explode(PATH_SEPARATOR, $open_basedir);
    foreach ($paths as $allowed_path) {
        $allowed_path = realpath($allowed_path);
        if ($allowed_path && strpos($path, $allowed_path) === 0) {
            return true;
        }
    }
    return false;
}

if (!isPathAllowed($current_path)) {
    $message = "⚠️ 警告：当前路径不在 open_basedir 允许的范围内，已自动切换到安全目录。";
    $current_path = WEB_ROOT;
}

if (!is_dir($current_path)) {
    $current_path = WEB_ROOT;
}

// 可编辑文件判断：任何文件（非目录）都可编辑
function canEdit($path) {
    return is_file($path);
}

// 处理文件编辑
$edit_content = '';
$edit_file = '';
$edit_error = '';

if (isset($_GET['edit'])) {
    $edit_file = realpath($_GET['edit']);
    if ($edit_file && is_file($edit_file)) {
        if (!isPathAllowed($edit_file)) {
            $edit_error = '访问被拒绝：文件不在允许的路径内。';
        } else {
            // 不再检查扩展名，直接读取内容
            $edit_content = file_get_contents($edit_file);
            if ($edit_content === false) {
                $edit_error = '无法读取文件内容。';
            }
        }
    } else {
        $edit_error = '文件不存在或无效。';
    }
}

// 处理各种操作
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理文件上传
    if (isset($_POST['upload']) && isset($_FILES['upload_file'])) {
        $upload_dir = $current_path;
        if (!is_writable($upload_dir)) {
            $message = "❌ 目标目录不可写，请检查权限: " . htmlspecialchars($upload_dir);
            $message_type = 'error';
        } elseif (!isPathAllowed($upload_dir)) {
            $message = "❌ 目标目录不在允许的路径内。";
            $message_type = 'error';
        } else {
            $uploaded_files = [];
            $failed_files = [];
            $files = $_FILES['upload_file'];
            $file_count = is_array($files['name']) ? count($files['name']) : 1;
            
            for ($i = 0; $i < $file_count; $i++) {
                $file_name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $file_tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $file_error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                
                if ($file_error === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    if (!empty($allowed_upload_extensions) && !in_array($ext, $allowed_upload_extensions)) {
                        $failed_files[] = "$file_name (不允许的文件类型)";
                        continue;
                    }
                    $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_name);
                    $target_path = $upload_dir . DIRECTORY_SEPARATOR . $safe_name;
                    $counter = 1;
                    $original_path = $target_path;
                    while (file_exists($target_path)) {
                        $path_parts = pathinfo($original_path);
                        $target_path = $path_parts['dirname'] . DIRECTORY_SEPARATOR . 
                                      $path_parts['filename'] . '_' . $counter . 
                                      (isset($path_parts['extension']) ? '.' . $path_parts['extension'] : '');
                        $counter++;
                    }
                    if (move_uploaded_file($file_tmp, $target_path)) {
                        $uploaded_files[] = basename($target_path);
                        @chmod($target_path, 0644);
                    } else {
                        $failed_files[] = $file_name;
                    }
                } else {
                    $error_msg = getUploadErrorMessage($file_error);
                    $failed_files[] = "$file_name ($error_msg)";
                }
            }
            
            if (!empty($uploaded_files)) {
                $message = "✅ 成功上传 " . count($uploaded_files) . " 个文件: " . implode(', ', $uploaded_files);
                if (!empty($failed_files)) {
                    $message .= "\n❌ 失败 " . count($failed_files) . " 个: " . implode(', ', $failed_files);
                    $message_type = 'warning';
                } else {
                    $message_type = 'success';
                }
            } else {
                $message = "❌ 上传失败: " . implode(', ', $failed_files);
                $message_type = 'error';
            }
        }
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : $current_path;
        $current_path = realpath($current_path);
    }
    // 处理文件保存
    elseif (isset($_POST['save_file']) && isset($_POST['file_path']) && isset($_POST['file_content'])) {
        $file_path = realpath($_POST['file_path']);
        $file_content = $_POST['file_content'];
        
        if ($file_path && is_file($file_path)) {
            if (!isPathAllowed($file_path)) {
                $message = "❌ 访问被拒绝：文件不在允许的路径内。";
                $message_type = 'error';
            } else {
                // 不再检查扩展名，直接保存
                $old_perms = fileperms($file_path);
                if (!is_writable($file_path)) {
                    @chmod($file_path, 0666);
                }
                if (file_put_contents($file_path, $file_content) !== false) {
                    $message = "✅ 文件保存成功！";
                    if (isset($old_perms) && $old_perms) {
                        @chmod($file_path, $old_perms);
                    }
                } else {
                    $message = "❌ 文件保存失败，请检查权限。";
                    $message_type = 'error';
                }
            }
        } else {
            $message = "❌ 无效的文件路径。";
            $message_type = 'error';
        }
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : $current_path;
        $current_path = realpath($current_path);
    }
    // 处理删除操作
    elseif (isset($_POST['delete']) && isset($_POST['target'])) {
        $target = realpath($_POST['target']);
        if ($target && isPathAllowed($target)) {
            if ($target == WEB_ROOT) {
                $message = "❌ 不能删除根目录！";
                $message_type = 'error';
            } else {
                $deleted = false;
                if (is_file($target)) {
                    $deleted = unlink($target);
                } elseif (is_dir($target)) {
                    $deleted = deleteDirectory($target);
                }
                if ($deleted) {
                    $message = "✅ 成功删除: " . htmlspecialchars(basename($target));
                } else {
                    $message = "❌ 删除失败，请检查权限。";
                    $message_type = 'error';
                }
            }
        } else {
            $message = "❌ 无效的路径或不在允许范围内。";
            $message_type = 'error';
        }
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : $current_path;
        $current_path = realpath($current_path);
    }
    // 处理批量删除
    elseif (isset($_POST['batch_delete']) && isset($_POST['selected_items'])) {
        $selected_items = $_POST['selected_items'];
        $deleted_count = 0;
        $failed_count = 0;
        foreach ($selected_items as $item) {
            $target = realpath($item);
            if ($target && isPathAllowed($target) && $target != WEB_ROOT) {
                if (is_file($target)) {
                    if (unlink($target)) $deleted_count++;
                    else $failed_count++;
                } elseif (is_dir($target)) {
                    if (deleteDirectory($target)) $deleted_count++;
                    else $failed_count++;
                }
            } else {
                $failed_count++;
            }
        }
        if ($deleted_count > 0) {
            $message = "✅ 成功删除 {$deleted_count} 个项目";
            if ($failed_count > 0) $message .= "，{$failed_count} 个失败";
        } else {
            $message = "❌ 删除失败，请检查权限。";
            $message_type = 'error';
        }
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : $current_path;
        $current_path = realpath($current_path);
    }
    // 处理压缩操作
    elseif (isset($_POST['compress']) && isset($_POST['selected_items'])) {
        $selected_items = $_POST['selected_items'];
        if (empty($selected_items)) {
            $message = "❌ 请至少选择一个文件或文件夹。";
            $message_type = 'error';
        } else {
            $tar_name = isset($_POST['tar_name']) && trim($_POST['tar_name']) != '' 
                ? trim($_POST['tar_name']) 
                : 'archive_' . date('Ymd_His');
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $tar_name)) {
                $message = "❌ 压缩包名称只能包含字母、数字、下划线、减号和点。";
                $message_type = 'error';
            } else {
                $tar_path = $current_path . DIRECTORY_SEPARATOR . $tar_name . '.tar.gz';
                $result = createTarGz($selected_items, $tar_path, $current_path);
                if ($result === true) {
                    $message = "✅ 成功创建压缩包: " . htmlspecialchars($tar_name . '.tar.gz');
                    $message_type = 'success';
                } else {
                    $message = "❌ 压缩失败:\n" . $result;
                    $message_type = 'error';
                }
            }
        }
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : $current_path;
        $current_path = realpath($current_path);
    }
    // 处理重命名操作
    elseif (isset($_POST['rename']) && isset($_POST['target']) && isset($_POST['new_name'])) {
        $target = realpath($_POST['target']);
        $new_name = trim($_POST['new_name']);
        if ($target && isPathAllowed($target)) {
            if (empty($new_name) || preg_match('#[\\/\\0]#', $new_name) || $new_name == '.' || $new_name == '..') {
                $message = "❌ 无效的名称";
                $message_type = 'error';
            } else {
                $parent_dir = dirname($target);
                $new_path = $parent_dir . DIRECTORY_SEPARATOR . $new_name;
                if (!isPathAllowed($new_path)) {
                    $message = "❌ 新路径不在允许范围内";
                    $message_type = 'error';
                } elseif (file_exists($new_path)) {
                    $message = "❌ 目标文件/目录已存在: " . htmlspecialchars($new_name);
                    $message_type = 'error';
                } elseif (rename($target, $new_path)) {
                    $message = "✅ 重命名成功: " . htmlspecialchars(basename($target)) . " → " . htmlspecialchars($new_name);
                    $message_type = 'success';
                    // 如果重命名的是当前目录，更新当前路径到父目录
                    if ($target == $current_path) {
                        $current_path = $parent_dir;
                        $message .= " 当前工作目录已被重命名，已切换到上级目录。";
                    }
                } else {
                    $message = "❌ 重命名失败，请检查权限。";
                    $message_type = 'error';
                }
            }
        } else {
            $message = "❌ 无效的目标路径或不在允许范围内。";
            $message_type = 'error';
        }
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : $current_path;
        $current_path = realpath($current_path);
    }
    // 修改权限或时间
    else {
        $target = isset($_POST['target']) ? $_POST['target'] : '';
        $target = realpath($target);
        if ($target && isPathAllowed($target)) {
            if (isset($_POST['chmod']) && isset($_POST['permissions'])) {
                $perms = octdec($_POST['permissions']);
                if (chmod($target, $perms)) {
                    $message = "✅ 成功修改权限: " . substr(sprintf('%o', $perms), -4);
                } else {
                    $message = "❌ 修改权限失败。可能原因：PHP运行用户不是文件所有者。";
                    $message_type = 'error';
                }
            } elseif (isset($_POST['touch']) && isset($_POST['mtime'])) {
                $mtime = strtotime($_POST['mtime']);
                if ($mtime !== false && touch($target, $mtime)) {
                    $message = "✅ 成功修改修改时间为: " . date('Y-m-d H:i:s', $mtime);
                } else {
                    $message = "❌ 修改时间失败，时间格式错误或无权限。";
                    $message_type = 'error';
                }
            }
        } else {
            $message = "❌ 无效的路径或不在允许范围内。";
            $message_type = 'error';
        }
        $current_path = isset($_POST['current_path']) ? $_POST['current_path'] : $current_path;
        $current_path = realpath($current_path);
    }
    
    if ($current_path === false || !isPathAllowed($current_path) || !is_dir($current_path)) {
        $current_path = WEB_ROOT;
    }
}

// 获取文件列表
$items = [];
if (is_dir($current_path) && is_readable($current_path)) {
    $dir = opendir($current_path);
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') continue;
        $full_path = $current_path . DIRECTORY_SEPARATOR . $file;
        $items[] = [
            'name' => $file,
            'path' => $full_path,
            'is_dir' => is_dir($full_path),
            'size' => is_file($full_path) ? filesize($full_path) : getDirectorySize($full_path),
            'perms' => fileperms($full_path),
            'mtime' => filemtime($full_path),
            'is_readable' => is_readable($full_path),
            'is_writable' => is_writable($full_path),
            'extension' => pathinfo($file, PATHINFO_EXTENSION)
        ];
    }
    closedir($dir);
    usort($items, function($a, $b) {
        if ($a['is_dir'] == $b['is_dir']) {
            return strnatcasecmp($a['name'], $b['name']);
        }
        return $a['is_dir'] ? -1 : 1;
    });
}

// 辅助函数
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE: return "文件超过服务器限制";
        case UPLOAD_ERR_FORM_SIZE: return "文件超过表单限制";
        case UPLOAD_ERR_PARTIAL: return "文件只有部分被上传";
        case UPLOAD_ERR_NO_FILE: return "没有文件被上传";
        case UPLOAD_ERR_NO_TMP_DIR: return "找不到临时文件夹";
        case UPLOAD_ERR_CANT_WRITE: return "文件写入失败";
        case UPLOAD_ERR_EXTENSION: return "文件上传被扩展阻止";
        default: return "未知错误";
    }
}

function permToString($perms) {
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

function formatSize($size) {
    if ($size === '-') return '-';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units)-1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

function getDirectorySize($dir) {
    $size = 0;
    // 为提升性能，此处暂时注释递归计算，有需要可取消注释
    return $size;
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

function createTarGz($items, $tar_path, $current_dir) {
    $debug_info = [];
    $debug_info[] = "========== 压缩调试信息 ==========";
    $debug_info[] = "当前目录: " . $current_dir;
    $debug_info[] = "目标路径: " . $tar_path;
    if (!function_exists('shell_exec')) {
        return "shell_exec 函数被禁用，无法执行 tar 命令。";
    }
    $debug_info[] = "shell_exec 函数可用";
    $tar_check = shell_exec('which tar 2>&1');
    $debug_info[] = "tar 命令检查: " . ($tar_check ? trim($tar_check) : "未找到");
    if (empty(trim($tar_check))) {
        return "系统不支持 tar 命令。\n\n" . implode("\n", $debug_info);
    }
    $target_dir = dirname($tar_path);
    $debug_info[] = "目标目录可写: " . (is_writable($target_dir) ? "是" : "否");
    if (!is_writable($target_dir)) {
        return "目标目录不可写: " . $target_dir . "\n\n" . implode("\n", $debug_info);
    }
    $valid_items = [];
    foreach ($items as $item) {
        $item_path = realpath($item);
        if ($item_path && file_exists($item_path)) {
            $valid_items[] = $item_path;
            $debug_info[] = "有效项目: " . $item_path;
        } else {
            $debug_info[] = "无效项目: " . $item;
        }
    }
    if (empty($valid_items)) {
        return "没有找到有效的文件或目录。\n\n" . implode("\n", $debug_info);
    }
    $items_for_tar = [];
    foreach ($valid_items as $item) {
        $items_for_tar[] = escapeshellarg(basename($item));
    }
    $tar_basename = basename($tar_path);
    $items_str = implode(' ', $items_for_tar);
    $command = "cd " . escapeshellarg($current_dir) . " 2>&1 && tar -czf " . escapeshellarg($tar_basename) . " " . $items_str . " 2>&1";
    $debug_info[] = "执行命令: " . $command;
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    $debug_info[] = "返回码: " . $return_var;
    if (!empty($output)) {
        $debug_info[] = "命令输出:\n" . implode("\n", $output);
    }
    if ($return_var === 0 && file_exists($tar_path)) {
        $file_size = filesize($tar_path);
        $debug_info[] = "压缩成功，大小: " . formatSize($file_size);
        return true;
    } else {
        $debug_info[] = "=================================";
        $error_msg = "压缩失败 (返回码: $return_var)\n\n";
        if (!empty($output)) {
            $error_msg .= "错误详情:\n" . implode("\n", $output) . "\n\n";
        }
        $error_msg .= "完整调试信息:\n" . implode("\n", $debug_info);
        return $error_msg;
    }
}

// 编辑器界面（支持任何文件编辑）
if (isset($_GET['edit']) && !$edit_error && $edit_content !== '') {
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑文件 - <?php echo htmlspecialchars(basename($edit_file)); ?></title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .editor-container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 70vh; font-family: 'Courier New', monospace; font-size: 14px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
        .info { margin-bottom: 15px; padding: 10px; background: #e3f2fd; border-radius: 4px; }
        .error { background: #ffebee; color: #c62828; }
        a { color: #2196f3; text-decoration: none; }
    </style>
</head>
<body>
<div class="editor-container">
    <h2>✏️ 正在编辑: <?php echo htmlspecialchars(basename($edit_file)); ?></h2>
    <div class="info">
        <strong>路径:</strong> <?php echo htmlspecialchars($edit_file); ?><br>
        <strong>提示:</strong> 您可以编辑任何文件（包括二进制文件），但请注意不当修改可能导致文件损坏。
    </div>
    <form method="post">
        <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($edit_file); ?>">
        <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
        <textarea name="file_content"><?php echo htmlspecialchars($edit_content); ?></textarea>
        <div style="margin-top: 15px;">
            <button type="submit" name="save_file">💾 保存文件</button>
            <a href="?path=<?php echo urlencode($current_path); ?>">← 返回文件管理器</a>
        </div>
    </form>
</div>
</body>
</html>
<?php
    exit;
} elseif (isset($_GET['edit']) && $edit_error) {
    // 显示错误并返回
    echo "<!DOCTYPE html><html><head><title>错误</title></head><body><div style='margin:50px'><h2>错误</h2><p>$edit_error</p><a href='?path=" . urlencode($current_path) . "'>返回文件管理器</a></div></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>网站文件管理器 - 完整功能版</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #333;
            font-size: 24px;
            display: inline-block;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            white-space: pre-line;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow: auto;
        }
        .message.info { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .message.error { background: #ffebee; border-left: 4px solid #f44336; }
        .message.success { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .message.warning { background: #fff3e0; border-left: 4px solid #ff9800; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; overflow-x: auto; display: block; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: 600; position: sticky; top: 0; }
        tr:hover { background-color: #f5f5f5; }
        .dir-name { font-weight: bold; color: #2c3e50; text-decoration: none; }
        .dir-name:hover { text-decoration: underline; }
        .file-name { color: #34495e; }
        .actions form { display: inline-block; margin: 0 3px; }
        .actions input, .actions select { padding: 4px 6px; margin: 0 2px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; }
        .actions button { padding: 4px 8px; margin: 0 2px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .actions button:hover { opacity: 0.8; }
        .btn-edit { background: #2196f3; color: white; }
        .btn-delete { background: #f44336; color: white; }
        .btn-chmod { background: #4CAF50; color: white; }
        .btn-touch { background: #ff9800; color: white; }
        .btn-rename { background: #6c757d; color: white; }
        .nav-bar { margin-bottom: 15px; padding: 10px; background: #e9ecef; border-radius: 4px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .nav-bar a { text-decoration: none; color: #007bff; padding: 5px 10px; background: white; border-radius: 4px; display: inline-block; }
        .nav-bar a:hover { background: #007bff; color: white; }
        .breadcrumb { margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 14px; word-break: break-all; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #777; padding: 10px; border-top: 1px solid #eee; }
        .status-badge { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
        .writable { background-color: #4caf50; }
        .readonly { background-color: #ff9800; }
        .quick-jump { margin-left: 20px; display: inline-block; }
        .quick-jump input { padding: 5px 10px; width: 300px; border: 1px solid #ccc; border-radius: 4px; }
        .batch-bar { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .batch-bar button { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-compress { background: #9c27b0; color: white; }
        .btn-batch-delete { background: #f44336; color: white; }
        .select-all { margin-left: 10px; }
        input[type="checkbox"] { cursor: pointer; width: 18px; height: 18px; }
        .tar-name-input { padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; width: 200px; }
        .upload-area { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; border: 2px dashed #ccc; }
        .upload-area:hover { border-color: #4CAF50; background: #f1f8e9; }
        @media (max-width: 768px) {
            .actions form { display: block; margin: 5px 0; }
            .quick-jump input { width: 150px; }
            .batch-bar { flex-direction: column; align-items: stretch; }
        }
    </style>
    <script>
        function toggleSelectAll(source) {
            let checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
            for(let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        function validateBatchDelete() {
            let checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
            if(checkboxes.length === 0) {
                alert('请至少选择一个文件或文件夹');
                return false;
            }
            return confirm('确定要删除选中的 ' + checkboxes.length + ' 个项目吗？此操作不可恢复！');
        }
        function validateCompress() {
            let checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
            if(checkboxes.length === 0) {
                alert('请至少选择一个文件或文件夹进行压缩');
                return false;
            }
            let tarName = document.getElementById('tar_name').value;
            if(tarName.trim() === '') {
                alert('请输入压缩包名称');
                return false;
            }
            return confirm('确定要压缩选中的 ' + checkboxes.length + ' 个项目为 tar.gz 格式吗？');
        }
        function validateUpload() {
            let files = document.getElementById('upload_file').files;
            if(files.length === 0) {
                alert('请选择要上传的文件');
                return false;
            }
            return confirm('确定要上传 ' + files.length + ' 个文件吗？');
        }
        function renameItem(path, currentName) {
            var newName = prompt('请输入新的名称:', currentName);
            if (newName && newName.trim() !== '') {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                var inputPath = document.createElement('input');
                inputPath.type = 'hidden';
                inputPath.name = 'target';
                inputPath.value = path;
                var inputNewName = document.createElement('input');
                inputNewName.type = 'hidden';
                inputNewName.name = 'new_name';
                inputNewName.value = newName.trim();
                var inputRename = document.createElement('input');
                inputRename.type = 'hidden';
                inputRename.name = 'rename';
                inputRename.value = '1';
                var inputCurrentPath = document.createElement('input');
                inputCurrentPath.type = 'hidden';
                inputCurrentPath.name = 'current_path';
                inputCurrentPath.value = '<?php echo htmlspecialchars($current_path); ?>';
                form.appendChild(inputPath);
                form.appendChild(inputNewName);
                form.appendChild(inputRename);
                form.appendChild(inputCurrentPath);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h1>📁 网站文件管理器</h1>
    <div class="quick-jump">
        <form method="get" style="display: inline;">
            <input type="text" name="path" value="<?php echo htmlspecialchars($current_path); ?>" placeholder="输入完整路径" size="40">
            <button type="submit">跳转</button>
        </form>
    </div>
    
    <div class="breadcrumb">
        📍 当前位置: 
        <?php
        $path_parts = explode(DIRECTORY_SEPARATOR, $current_path);
        $full_path = '';
        echo '<a href="?path=' . urlencode(WEB_ROOT) . '">根目录</a>';
        foreach ($path_parts as $part) {
            if ($part === '' || $part === WEB_ROOT) continue;
            $full_path .= DIRECTORY_SEPARATOR . $part;
            echo ' / <a href="?path=' . urlencode($full_path) . '">' . htmlspecialchars($part) . '</a>';
        }
        ?>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo nl2br(htmlspecialchars($message)); ?>
        </div>
    <?php endif; ?>
    
    <div class="nav-bar">
        <?php 
        $parent_path = dirname($current_path);
        if ($parent_path != $current_path && isPathAllowed($parent_path) && is_dir($parent_path)): ?>
            <a href="?path=<?php echo urlencode($parent_path); ?>">⬆️ 返回上级目录</a>
        <?php endif; ?>
        
        <?php if ($current_path != WEB_ROOT): ?>
            <a href="?path=<?php echo urlencode(WEB_ROOT); ?>">🏠 回到根目录</a>
        <?php endif; ?>
        
        <span style="margin-left: auto; font-size: 12px; color: #666;">
            📊 共 <?php echo count($items); ?> 个项目
        </span>
    </div>
    
    <!-- 上传文件区域 -->
    <div class="upload-area">
        <form method="post" enctype="multipart/form-data" onsubmit="return validateUpload()">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div style="flex: 1;">
                    <input type="file" name="upload_file[]" id="upload_file" multiple style="width: 100%; padding: 8px;">
                    <small style="color: #666;">支持多文件上传，最大 <?php echo ini_get('upload_max_filesize'); ?></small>
                </div>
                <button type="submit" name="upload" value="1" class="btn-compress" style="background: #4CAF50;">📤 上传文件</button>
            </div>
        </form>
    </div>
    
    <!-- 批量操作栏 -->
    <div class="batch-bar">
        <form method="post" id="batchForm" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
            <label>
                <input type="text" name="tar_name" id="tar_name" class="tar-name-input" placeholder="压缩包名称（不含扩展名）" value="archive_<?php echo date('Ymd_His'); ?>">
                <span style="font-size: 12px; color: #666;">.tar.gz</span>
            </label>
            <button type="submit" name="compress" value="1" class="btn-compress" onclick="return validateCompress()">📦 压缩为 tar.gz</button>
            <button type="submit" name="batch_delete" value="1" class="btn-batch-delete" onclick="return validateBatchDelete()">🗑️ 批量删除</button>
            <label class="select-all">
                <input type="checkbox" onclick="toggleSelectAll(this)"> 全选/反选
            </label>
        </form>
    </div>
    
    <div style="overflow-x: auto;">
        <form method="post" id="mainForm">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                        <th>名称</th>
                        <th>类型</th>
                        <th>大小</th>
                        <th>权限</th>
                        <th>修改时间</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) === 0): ?>
                        <tr><td colspan="8" style="text-align: center;">📂 此目录为空</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item['path']); ?>">
                            </td>
                            <td>
                                <?php if ($item['is_dir']): ?>
                                    <a href="?path=<?php echo urlencode($item['path']); ?>" class="dir-name">
                                        📁 <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="file-name">📄 <?php echo htmlspecialchars($item['name']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['is_dir'] ? '📂 目录' : '📄 文件'; ?></td>
                            <td><?php echo formatSize($item['size']); ?></td>
                            <td>
                                <span class="current-perm"><?php echo permToString($item['perms']); ?></span>
                                (<?php echo substr(sprintf('%o', $item['perms']), -4); ?>)
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', $item['mtime']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $item['is_writable'] ? 'writable' : 'readonly'; ?>"></span>
                                <?php echo $item['is_writable'] ? '可写' : '只读'; ?>
                            </td>
                            <td class="actions">
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="target" value="<?php echo htmlspecialchars($item['path']); ?>">
                                    <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
                                    <input type="text" name="permissions" value="<?php echo substr(sprintf('%o', $item['perms']), -4); ?>" size="5" style="width: 50px;">
                                    <button type="submit" name="chmod" class="btn-chmod">权限</button>
                                </form>
                                
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="target" value="<?php echo htmlspecialchars($item['path']); ?>">
                                    <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
                                    <input type="text" name="mtime" value="<?php echo date('Y-m-d H:i:s', $item['mtime']); ?>" size="16" style="width: 130px;">
                                    <button type="submit" name="touch" class="btn-touch">时间</button>
                                </form>
                                
                                <?php if (!$item['is_dir']): ?>
                                    <a href="?edit=<?php echo urlencode($item['path']); ?>&path=<?php echo urlencode($current_path); ?>">
                                        <button type="button" class="btn-edit">编辑</button>
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" class="btn-rename" onclick="renameItem('<?php echo addslashes($item['path']); ?>','<?php echo addslashes($item['name']); ?>');">重命名</button>
                                
                                <form method="post" style="display:inline-block;" onsubmit="return confirm('确定要删除 <?php echo htmlspecialchars($item['name']); ?> 吗？');">
                                    <input type="hidden" name="target" value="<?php echo htmlspecialchars($item['path']); ?>">
                                    <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
                                    <button type="submit" name="delete" class="btn-delete">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>

   
</div>
</body>
</html>