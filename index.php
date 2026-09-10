<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Pembayaran SPP</title>
    <link rel="stylesheet" href="style/landing-page.css">
</head>
<body>

    <div class="container">
        
        <h1>Sistem Informasi Pembayaran SPP</h1>
        <p class="welcome-text">Selamat datang. Silakan pilih menu sesuai kebutuhan Anda.</p>

        <div class="menu-box">
            <h3>Untuk Petugas / Admin Sekolah</h3>
            <p>Kelola data siswa, kelas, SPP, dan catat pembayaran.</p>
            <a href="auth/login.php" class="btn btn-primary">Login Petugas</a>
        </div>

        <div class="menu-box">
            <h3>Untuk Siswa / Orang Tua</h3>
            <p>Cek status dan riwayat pembayaran SPP tanpa perlu login.</p>
            <a href="siswa/cek-tagihan.php" class="btn btn-secondary">Cek Tagihan SPP</a>
        </div>

    </div>

</body>
</html>