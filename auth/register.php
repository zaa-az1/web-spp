<?php
session_start();
require '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username     = $_POST["username"];
    $password     = $_POST["password"];
    $nama_petugas = $_POST["nama_petugas"];
    $level        = $_POST["level"];

    
    $cek_user = mysqli_query($koneksi, "SELECT * FROM petugas WHERE username = '$username'");

    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username sudah digunakan, cari yang lain!');
        window.history.back();</script>";
    } else {
        $query = mysqli_query($koneksi, "INSERT INTO petugas (username, password, nama_petugas, level) VALUES ('$username', '$password', '$nama_petugas', '$level')");

        if ($query) {
            echo "<script>alert('Pendaftaran berhasil! Silahkan login.');
            window.location.href = 'login.php';</script>";
        } else {
            echo "<script>alert('Gagal mendaftar, coba lagi!');
            window.history.back();</script>";
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Register Petugas</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="login-container">
            <img src="../images/1.png" alt="Logo" class="logo">
            <h2>DAFTAR AKUN PETUGAS</h2>
            <form action="" method="post">
                <label for="username">Username</label>
                <input type="text" name="username" placeholder="Buat Username Baru" required class="input">

                <label for="nama_petugas">Nama Lengkap</label>
                <input type="text" name="nama_petugas" placeholder="Nama Lengkap Petugas" required class="input">

                <label for="password">Password</label>
                <input type="password" name="password" placeholder="Buat Password" required class="input">

                <label for="level">Hak Akses / Level</label>
                <select name="level" required class="input">
                    <option value="">-- Pilih Level --</option>
                    <option value="admin">Admin</option>
                    <option value="petugas">Petugas</option>
                </select>

                <input type="submit" value="Daftar Sekarang" class="button">
            </form>

            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>