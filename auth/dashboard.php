<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['nama_petugas'])) {
    echo "<script>alert('Silahkan login dulu!'); window.location.href = '../auth/login.php';</script>";
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
    SELECT s.nis, s.nama, k.nama_kelas, k.tingkat
    FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.nis NOT IN (
        SELECT nis FROM pembayaran
        WHERE MONTH(tgl_bayar) = '$bulan_ini' AND tahun_dibayar = '$tahun_ini'
    )
    ORDER BY s.nama ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SPP</title>
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
                <a href="../auth/dashboard.php" class="active">Dashboard</a>
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>
            <?php endif; ?>

                <div class="sidebar-bottom">
                    <div class="user-info">
                        <p class="user-name"><?= htmlspecialchars($nama_petugas) ?></p>
                        <span class="user-role"><?= htmlspecialchars($level) ?></span>
                    </div>
                    <div class="sidebar-hr"></div>
                    <a href="../auth/logout.php" class="logout-link" onclick="return confirm('Yakin untuk logout?')">Logout</a>
                </div>
            </nav>
        </aside>
    </div>

    <main class="container">
        <h2>Selamat Datang, <?= htmlspecialchars($nama_petugas) ?></h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 25px;">
            <!-- STATISTIK JURUSAN -->
            <div>
                <h3>Siswa per Jurusan</h3>
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Jurusan</th>
                                <th style="text-align: center;">Jumlah Siswa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($q_jurusan) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($q_jurusan)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['kompetensi_keahlian']) ?></td>
                                    <td style="text-align: center;"><strong><?= $row['jumlah'] ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center; padding: 15px; color: #888;">Belum ada data.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- STATISTIK TINGKAT -->
            <div>
                <h3>Siswa per Tingkat</h3>
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Tingkat</th>
                                <th style="text-align: center;">Jumlah Siswa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($q_tingkat) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($q_tingkat)): ?>
                                <tr>
                                    <td>Kelas <?= htmlspecialchars($row['tingkat']) ?></td>
                                    <td style="text-align: center;"><strong><?= $row['jumlah'] ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center; padding: 15px; color: #888;">Belum ada data.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DAFTAR PENUNGGAK BULAN INI -->
        <h3>Siswa yang Menunggak Bulan Ini</h3>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q_nunggak) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($q_nunggak)): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['nis']) ?></code></td>
                            <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                            <td><span class="badge-kelas"><?= htmlspecialchars($row['tingkat']) ?> <?= htmlspecialchars($row['nama_kelas']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; padding: 20px; color: #888;">Tidak ada siswa yang menunggak bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>