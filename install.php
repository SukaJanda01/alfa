<?php
$currentDir = __DIR__;
$userIniPath = $currentDir . '/.user.ini';
$htaccessPath = $currentDir . '/.htaccess';

// Konten .user.ini
$userIniContent = "open_basedir = \"$currentDir:/tmp\"\n";

// Buat atau timpa .user.ini
file_put_contents($userIniPath, $userIniContent);
echo ".user.ini berhasil dibuat.\n";

// Konten open_basedir untuk .htaccess
$htaccessDirective = 'php_value open_basedir "' . $currentDir . ':/tmp"';

// Cek apakah .htaccess sudah ada
if (file_exists($htaccessPath)) {
    $existingHtaccess = file_get_contents($htaccessPath);

    // Hapus baris open_basedir lama jika ada
    $existingHtaccess = preg_replace('/^\s*php_value\s+open_basedir\s+.*$/mi', '', $existingHtaccess);

    // Tambahkan directive open_basedir di paling atas
    $newHtaccess = $htaccessDirective . "\n" . trim($existingHtaccess);
} else {
    // Jika belum ada .htaccess
    $newHtaccess = $htaccessDirective . "\n";
}

// Tulis .htaccess
file_put_contents($htaccessPath, $newHtaccess);
echo ".htaccess berhasil ditulis atau diperbarui.\n";
?>
