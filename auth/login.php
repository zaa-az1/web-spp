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