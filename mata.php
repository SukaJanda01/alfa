<?php

$BOT_TOKEN = "7234146330:AAFiKxHozgBK-SBZRZcmtjWQTH6K77aDCmg";
$CHAT_ID = "7234146330";

// Pastikan pengguna memberikan path direktori
if ($argc < 2) {
    echo "Usage: php start.php <path_to_directory>\n";
    exit(1);
}

// Konversi path ke absolut dan validasi
$baseDirectory = realpath($argv[1]);

if (!is_dir($baseDirectory)) {
    echo "Direktori tidak ditemukan: $baseDirectory\n";
    exit(1);
}

echo "🔍 Memantau direktori: $baseDirectory...\n";

// Menyimpan daftar file yang sudah gagal dihapus agar tidak spam
$failedDeletes = [];

// Fungsi mengirim pesan ke Telegram
function sendTextToTelegram($message)
{
    global $BOT_TOKEN, $CHAT_ID;
    $url = "https://api.telegram.org/bot$BOT_TOKEN/sendMessage";

    $postData = [
        'chat_id' => $CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    file_get_contents($url . "?" . http_build_query($postData));
}

// Fungsi mencari file dalam direktori & subdirektori
function scanDirectory($dir)
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->isFile() && is_readable($file->getRealPath())) {
            $files[] = $file->getRealPath();
        }
    }
    return $files;
}

// Fungsi mengecek perintah /delete
function checkDeleteCommand()
{
    global $BOT_TOKEN, $CHAT_ID, $baseDirectory, $failedDeletes;

    $url = "https://api.telegram.org/bot$BOT_TOKEN/getUpdates";
    $response = file_get_contents($url);
    $updates = json_decode($response, true);

    if (!isset($updates["result"])) return;

    foreach ($updates["result"] as $update) {
        if (!isset($update["message"]["text"])) continue;

        $messageText = $update["message"]["text"];
        $chatId = $update["message"]["chat"]["id"];

        if ($chatId != $CHAT_ID) continue; // Hanya pemilik bot

        if (preg_match('/^\/delete (.+)$/', $messageText, $matches)) {
            $relativePath = trim($matches[1], "/");
            $fileToDelete = realpath($baseDirectory . "/" . $relativePath);

            // Pastikan file dalam direktori utama
            if (!$fileToDelete || strpos($fileToDelete, $baseDirectory) !== 0 || !file_exists($fileToDelete) || !is_file($fileToDelete)) {
                if (!in_array($relativePath, $failedDeletes)) { // Cek apakah sudah gagal sebelumnya
                    sendTextToTelegram("❌ *File tidak ditemukan atau tidak valid:*\n📂 `$relativePath`");
                    $failedDeletes[] = $relativePath; // Simpan ke daftar gagal
                }
                echo "Gagal menghapus, file tidak ditemukan: $relativePath\n";
                continue;
            }

            unlink($fileToDelete);
            sendTextToTelegram("✅ *File berhasil dihapus:*\n📂 `$relativePath`");
            echo "File dihapus: $fileToDelete\n";

            // Jika berhasil dihapus, hapus dari daftar gagal
            if (($key = array_search($relativePath, $failedDeletes)) !== false) {
                unset($failedDeletes[$key]);
            }
        }
    }
}

// Simpan daftar awal file dalam folder & subfolder
$knownFiles = scanDirectory($baseDirectory);

while (true) {
    sleep(5);

    // Cek perintah /delete dari Telegram
    checkDeleteCommand();

    // Cek file baru dalam direktori & subdirektori
    $currentFiles = scanDirectory($baseDirectory);
    $newFiles = array_diff($currentFiles, $knownFiles);

    foreach ($newFiles as $filePath) {
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        $fileTime = date("Y-m-d H:i:s", filemtime($filePath));
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $relativePath = str_replace($baseDirectory . "/", "", realpath($filePath));

        $message = "🚀 *File Baru Telah Diupload!*\n\n"
            . "📂 *Nama:* `$relativePath`\n"
            . "📏 *Ukuran:* " . round($fileSize / 1024, 2) . " KB\n"
            . "📁 *Lokasi:* `$filePath`\n"
            . "📄 *Tipe:* `$fileExtension`\n"
            . "⏰ *Waktu:* `$fileTime`\n\n"
            . "🗑 *Hapus file ini?*\nKirim perintah:\n`/delete $relativePath`";

        echo "File baru: $relativePath\n";
        sendTextToTelegram($message);
    }

    // Update daftar file yang sudah diketahui
    $knownFiles = $currentFiles;
}

?>
