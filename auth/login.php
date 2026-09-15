<?php
session_start();
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query  = "SELECT * FROM petugas WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        $petugas = mysqli_fetch_assoc($result);

        if ($petugas && $password === $petugas['password']) {
            $_SESSION['id_petugas']   = $petugas['id_petugas'];
            $_SESSION['username'] = $petugas['username'];
            $_SESSION['nama_petugas'] = $petugas['nama_petugas'];
            $_SESSION['level'] = $petugas['level'];

            echo "<script>alert('Login berhasil! Selamat datang, {$petugas['nama_petugas']}');
            window.location.href = 'dashboard.php';</script>";
            exit;
        } else {
            echo "<script>alert('Username atau password salah!');
            window.history.back();</script>";
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Petugas</title>
    <link rel="stylesheet" href="../style/style.css">
    <style>
        body {
            background-image: url('../images/landing-page.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 1);
            min-height: 100vh;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
    <img src="../images/1.png" alt="Logo" class="logo">
    <h2>Login Petugas</h2>

    <form method="POST" action="login.php">
        <label>Username</label><br>
        <input type="text" name="username" required  class="input"><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required  class="input"><br><br>

        <button type="submit" class="button">Login</button>
    </form>

    <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
</div>

</body>
</html>