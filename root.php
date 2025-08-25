<?php
// Koneksi ke database MySQL
$host = 'localhost';  // Ganti dengan host MySQL Anda
$username = 'root';  // Ganti dengan username MySQL Anda
$password = 'VSCsander2071!@';  // Ganti dengan password MySQL Anda
$dbname = 'test';  // Ganti dengan nama database Anda

$conn = new mysqli($host, $username, $password, $dbname);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek apakah tabel php_codes sudah ada
$table_check_query = "SHOW TABLES LIKE 'php_codes'";
$result = $conn->query($table_check_query);

// Jika tabel php_codes tidak ada, buat tabel baru
if ($result->num_rows === 0) {
    // Tabel php_codes tidak ada, buat tabel baru
    $create_table_query = "CREATE TABLE php_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code TEXT NOT NULL
    )";
    
    if ($conn->query($create_table_query) === TRUE) {
        echo "Tabel php_codes berhasil dibuat!<br>";
    } else {
        echo "Gagal membuat tabel php_codes: " . $conn->error . "<br>";
    }
} else {
    // Jika tabel php_codes sudah ada, buat tabel baru dengan nama acak
    $new_table_name = 'php_codes_' . bin2hex(random_bytes(5));
    $create_new_table_query = "CREATE TABLE $new_table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code TEXT NOT NULL
    )";
    
    if ($conn->query($create_new_table_query) === TRUE) {
        echo "Tabel baru $new_table_name berhasil dibuat!<br>";
    } else {
        echo "Gagal membuat tabel baru: " . $conn->error . "<br>";
    }
}

// Menyimpan kode PHP ke dalam tabel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $php_code = $_POST['php_code'];  // Kode PHP yang dimasukkan
    $file_path_input = $_POST['file_path'];  // File path yang dimasukkan oleh pengguna
    $file_name_input = $_POST['file_name'];  // Nama file yang dimasukkan oleh pengguna

    // Pastikan file path yang dimasukkan aman dan valid
    $file_path_input = rtrim($file_path_input, '/');  // Hapus slash di akhir path jika ada

    // Escape kode PHP agar aman untuk disimpan dalam database
    $escaped_php_code = $conn->real_escape_string($php_code);

    // Tentukan tabel yang akan digunakan
    $table_name_to_use = ($result->num_rows === 0) ? 'php_codes' : $new_table_name;

    // Masukkan kode PHP ke dalam tabel yang sesuai
    $insert_query = "INSERT INTO $table_name_to_use (code) VALUES ('$escaped_php_code')";
    if ($conn->query($insert_query) === TRUE) {
        echo "Kode PHP berhasil disimpan di database!<br>";

        // Pastikan nama file aman dan valid
        $file_name = basename($file_name_input);  // Ambil hanya nama file tanpa path yang bisa berbahaya

        // Tentukan path file lengkap
        $file_path = $file_path_input . '/' . $file_name;  // Menyimpan file di lokasi yang dimasukkan pengguna

        // Escape path file agar aman digunakan dalam query
        $escaped_file_path = $conn->real_escape_string($file_path);

        // Query untuk memilih kode PHP dan menyimpannya ke OUTFILE (dengan path yang diberikan)
        $query = "SELECT code 
                  INTO OUTFILE '$escaped_file_path'
                  FIELDS TERMINATED BY '' 
                  LINES TERMINATED BY '\\n'
                  FROM $table_name_to_use WHERE id = LAST_INSERT_ID()";  // Mengambil ID terakhir yang dimasukkan

        // Menjalankan query untuk menulis file
        if ($conn->query($query) === TRUE) {
            echo "File PHP berhasil disimpan ke $file_path!<br>";
        } else {
            echo "Gagal menulis file PHP ke OUTFILE: " . $conn->error . "<br>";
        }
    } else {
        echo "Gagal menyimpan kode ke tabel: " . $conn->error . "<br>";
    }
}

// Menutup koneksi
$conn->close();
?>

<!-- Form untuk input konten PHP dan file path -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simpan Kode PHP</title>
</head>
<body>
    <h2>Form untuk Menyimpan Kode PHP</h2>
    <form method="POST" action="">
        <label for="php_code">Kode PHP:</label><br>
        <textarea name="php_code" id="php_code" rows="10" cols="50" required></textarea><br><br>

        <label for="file_path">Lokasi Penyimpanan File (Direktori):</label><br>
        <input type="text" name="file_path" id="file_path" placeholder="/var/lib/mysql-files" required><br><br>

        <label for="file_name">Nama File:</label><br>
        <input type="text" name="file_name" id="file_name" placeholder="kode_php.php" required><br><br>

        <input type="submit" value="Simpan Kode PHP">
    </form>
</body>
</html>
