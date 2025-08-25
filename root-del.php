<?php
// Koneksi ke database MySQL
$host = 'localhost'; // Ganti dengan host MySQL Anda
$username = 'root'; // Ganti dengan username MySQL Anda
$password = 'VSCsander2071!@'; // Ganti dengan password MySQL Anda
$dbname = 'test'; // Ganti dengan nama database Anda

// Membuat koneksi ke MySQL
$conn = new mysqli($host, $username, $password, $dbname);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Memeriksa apakah form untuk menghapus file telah disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_file'])) {
    // Ambil nama file yang akan dihapus
    $dir = $_POST['dir']; // Direktori tempat file berada
    $fileName = $_POST['delete_file']; // Nama file yang akan dihapus

    // Tentukan path lengkap file yang akan dihapus
    $filePath = rtrim($dir, '/') . '/' . $fileName;

    // Menghapus file dari sistem file server
    if (file_exists($filePath)) {
        // Menghapus file dari sistem file
        if (unlink($filePath)) {
            echo "File '$fileName' berhasil dihapus dari sistem file!<br>";

            // Menghapus record file dari database
            $stmt = $conn->prepare("DELETE FROM files WHERE dir = ? AND fileName = ?");
            $stmt->bind_param("ss", $dir, $fileName);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo "Record file '$fileName' berhasil dihapus dari database!<br>";
            } else {
                echo "Gagal menghapus record file '$fileName' dari database.<br>";
            }

            // Menutup statement
            $stmt->close();
        } else {
            echo "Gagal menghapus file '$fileName' dari sistem file.<br>";
        }
    } else {
        echo "File '$fileName' tidak ditemukan di direktori '$dir'.<br>";
    }
}
?>

<!-- Form untuk menghapus file -->
<h2>Hapus File</h2>
<form method="POST" action="">
    <label for="dir">Direktori:</label><br>
    <input type="text" id="dir" name="dir" required><br><br>
    
    <label for="delete_file">Nama File yang akan dihapus (misalnya file.php):</label><br>
    <input type="text" id="delete_file" name="delete_file" required><br><br>
    
    <input type="submit" value="Hapus File">
</form>