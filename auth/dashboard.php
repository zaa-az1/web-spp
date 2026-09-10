<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['nama_petugas'])) {
    echo "<script>alert('Silahkan login dulu!'); window.location.href = 'login.php';</script>";
    exit;
}

$level        = $_SESSION['level'];
$username     = $_SESSION['username'];
$nama_petugas = $_SESSION['nama_petugas'];

$bulan_ini = date('n');
$tahun_ini = date('Y');

$q_jurusan = mysqli_query($koneksi, "
    SELECT k.kompetensi_keahlian, COUNT(s.nis) AS jumlah
    FROM kelas k LEFT JOIN siswa s ON s.id_kelas = k.id_kelas
    GROUP BY k.kompetensi_keahlian
");

$q_tingkat = mysqli_query($koneksi, "
    SELECT k.tingkat, COUNT(s.nis) AS jumlah
    FROM kelas k LEFT JOIN siswa s ON s.id_kelas = k.id_kelas
    GROUP BY k.tingkat ORDER BY k.tingkat
");

$q_nunggak = mysqli_query($koneksi, "
    SELECT s.nis, s.nama, k.nama_kelas
    FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.nis NOT IN (
        SELECT nis FROM pembayaran
        WHERE MONTH(tgl_bayar) = '$bulan_ini' AND tahun_dibayar = '$tahun_ini'
    )
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="dashboard-container">

        <div class="brand">
            <img src="../images/1.png" alt="Logo" class="logo-db">
            <p class="nama-spp"> DASHBOARD <br >WEBSITE SPP</p>
        </div>

        <aside>
            <nav class="sidebar-menu">
            <?php if ($level == 'admin'): ?>
                <a href="../auth/dashboard.php" class="active">Dashboard</a>
                <a href="../admin/kelola-petugas.php">Petugas</a>
                <a href="../admin/kelola-kelas.php">Kelas</a>
                <a href="../admin/kelola-siswa.php">Siswa</a>
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>
            <?php else: ?>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>
            <?php endif; ?>

                <div class="sidebar-bottom">
                    <div class="user-info">
                        <p class="user-name"><?= htmlspecialchars($nama_petugas) ?></p>
                        <span class="user-role"><?= htmlspecialchars($level) ?></span>
                    </div>
                    <div class="sidebar-hr"></div>
                    <a href="logout.php" class="logout-link" onclick="return confirm('Yakin untuk logout?')">Logout</a>
                </div>
            </nav>
        </aside>
    </div>

    <main class="container">
        <h2>Selamat Datang, <?= htmlspecialchars($nama_petugas) ?></h2>
        <h3>Siswa per Jurusan</h3>
        <table border="1" cellpadding="6">
            <tr><th>Jurusan</th><th>Jumlah Siswa</th></tr>
            <?php while ($row = mysqli_fetch_assoc($q_jurusan)): ?>
            <tr><td><?= htmlspecialchars($row['kompetensi_keahlian']) ?></td><td><?= $row['jumlah'] ?></td></tr>
            <?php endwhile; ?>
        </table>
        <br>
        <h3>Siswa per Tingkat</h3>
        <table border="1" cellpadding="6">
            <tr><th>Tingkat</th><th>Jumlah Siswa</th></tr>
            <?php while ($row = mysqli_fetch_assoc($q_tingkat)): ?>
            <tr><td><?= htmlspecialchars($row['tingkat']) ?></td><td><?= $row['jumlah'] ?></td></tr>
            <?php endwhile; ?>
        </table>
        <br>
        <h3>Siswa yang Menunggak Bulan Ini</h3>
        <table border="1" cellpadding="6">
            <tr><th>NIS</th><th>Nama</th><th>Kelas</th></tr>
            <?php while ($row = mysqli_fetch_assoc($q_nunggak)): ?>
            <tr><td><?= htmlspecialchars($row['nis']) ?></td><td><?= htmlspecialchars($row['nama']) ?></td><td><?= htmlspecialchars($row['nama_kelas']) ?></td></tr>
            <?php endwhile; ?>
        </table>
    </main>
</body>
</html>