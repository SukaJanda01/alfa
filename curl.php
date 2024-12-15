<?php
// Fungsi untuk mendownload file dari URL menggunakan cURL
function download_file_from_url($url, $destination) {
    // Inisialisasi cURL
    $ch = curl_init($url);

    // Set opsi cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Menyimpan output cURL ke variabel
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Mengikuti redirect jika ada
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);            // Waktu timeout 30 detik

    // Ambil data dari URL
    $data = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Cek jika cURL berhasil
    if ($http_code == 200) {
        // Simpan file ke tujuan
        file_put_contents($destination, $data);
        curl_close($ch);
        return true; // Berhasil
    } else {
        curl_close($ch);
        return false; // Gagal
    }
}

// Menangani form upload file dari URL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['url']) && isset($_POST['filename'])) {
    $url = $_POST['url'];  // URL dari input form
    $filename = $_POST['filename'];  // Nama file yang akan disimpan

    // Tentukan direktori tempat file disimpan
    $current_dir = getcwd();  // Menggunakan direktori kerja saat ini (bisa diubah sesuai kebutuhan)
    $file_path = $current_dir . '/' . $filename;

    // Download file dari URL dan simpan dengan nama yang diberikan
    if (download_file_from_url($url, $file_path)) {
        $message = "File downloaded and saved successfully!";
    } else {
        $message = "Failed to download the file.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File from URL</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .container { width: 80%; margin: 0 auto; }
        header { background-color: #4CAF50; color: white; padding: 10px 0; text-align: center; }
        .form-container { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .form-container input, .form-container button { padding: 10px; width: 100%; margin: 10px 0; }
        .form-container button { background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .form-container button:hover { background-color: #45a049; }
        .message { background-color: #f1f1f1; padding: 10px; margin-top: 20px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Upload File from URL</h1>
    </header>

    <div class="form-container">
        <h2>Enter URL and File Name</h2>
        <form action="" method="POST">
            <input type="text" name="url" placeholder="Enter URL" required><br>
            <input type="text" name="filename" placeholder="Enter file name" required><br>
            <button type="submit">Download and Save</button>
        </form>
    </div>

    <?php if (isset($message)): ?>
        <div class="message">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
