<?php
// Fungsi untuk mendapatkan semua folder dari direktori tertentu
function listDirectories($base) {
    $folders = [];
    if (!is_dir($base)) return $folders;

    $items = scandir($base);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $base . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath)) {
            $folders[] = $fullPath;
        }
    }
    return $folders;
}

// Fungsi chmod rekursif
function chmodRecursive($dir) {
    $log = "";
    if (@chmod($dir, 0755)) {
        $log .= "Folder: $dir => CHMOD 755<br>";
    } else {
        $log .= "Gagal chmod folder: $dir<br>";
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_file($fullPath)) {
            if (@chmod($fullPath, 0644)) {
                $log .= "File: $fullPath => CHMOD 644<br>";
            } else {
                $log .= "Gagal chmod file: $fullPath<br>";
            }
        } elseif (is_dir($fullPath)) {
            $log .= chmodRecursive($fullPath);
        }
    }
    return $log;
}

// Eksekusi saat form disubmit
$output = "";
$baseDir = __DIR__;
$folders = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customPath = $_POST['path'] ?? '';
    if (!empty($customPath) && is_dir($customPath)) {
        $baseDir = realpath($customPath);
        $folders = listDirectories($baseDir);

        if (!empty($_POST['dirs'])) {
            foreach ($_POST['dirs'] as $folder) {
                $output .= chmodRecursive($folder);
            }
        }
    } else {
        $output = "Direktori tidak valid atau tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CHMOD Tool by SukaJanda01</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 20px; }
        .box { background: #fff; padding: 20px; border-radius: 10px; max-width: 800px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; }
        input[type="submit"] { padding: 10px 20px; }
        .log { background: #eee; padding: 10px; margin-top: 20px; white-space: pre-wrap; }
        label { display: block; margin: 4px 0; }
    </style>
    <script>
        function toggleAll(source) {
            checkboxes = document.querySelectorAll('input[type="checkbox"][name="dirs[]"]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>
<body>
<div class="box">
    <h2>CHMOD Folder to 755 & Files to 644</h2>
    <form method="post">
        <p><strong>Masukkan path direktori (contoh: `/var/www/html`):</strong></p>
        <input type="text" name="path" value="<?= htmlspecialchars($baseDir) ?>" style="width:100%;padding:8px;"><br><br>

        <?php if (!empty($folders)): ?>
            <label><input type="checkbox" onclick="toggleAll(this)"> <strong>Centang Semua Folder</strong></label><br><br>
            <?php foreach ($folders as $dir): ?>
                <label><input type="checkbox" name="dirs[]" value="<?= htmlspecialchars($dir) ?>"> <?= htmlspecialchars($dir) ?></label>
            <?php endforeach; ?>
            <br>
        <?php endif; ?>

        <input type="submit" value="Ubah CHMOD">
    </form>

    <?php if (!empty($output)): ?>
        <div class="log">
            <h3>Log Hasil:</h3>
            <?= $output ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
