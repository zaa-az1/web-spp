<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['nama_petugas'])) {
    echo "<script>alert('Silahkan login dulu!'); window.location.href = '../auth/login.php';</script>";
    exit;
}

$level        = $_SESSION['level'];
$nama_petugas = $_SESSION['nama_petugas'];

if ($level !== 'admin') {
    echo "<script>alert('Anda tidak punya akses ke halaman ini!'); window.location.href = '../auth/dashboard.php';</script>";
    exit;
}

$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

$tgl_mulai_aman   = mysqli_real_escape_string($koneksi, $tgl_mulai);
$tgl_selesai_aman = mysqli_real_escape_string($koneksi, $tgl_selesai);

$query = "
    SELECT p.*, s.nama, k.nama_kelas, k.tingkat, pt.nama_petugas
    FROM pembayaran p
    JOIN siswa s ON p.nis = s.nis
    JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN petugas pt ON p.id_petugas = pt.id_petugas
    WHERE p.tgl_bayar BETWEEN '$tgl_mulai_aman' AND '$tgl_selesai_aman'
    ORDER BY p.tgl_bayar DESC, p.id_pembayaran DESC
";
$hasil_list = mysqli_query($koneksi, $query);

$q_total = mysqli_query($koneksi, "
    SELECT SUM(jumlah_bayar) AS total_pemasukan 
    FROM pembayaran 
    WHERE tgl_bayar BETWEEN '$tgl_mulai_aman' AND '$tgl_selesai_aman'
");
$data_total = mysqli_fetch_assoc($q_total);
$grand_total = $data_total['total_pemasukan'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemasukan SPP</title>
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
                <a href="../auth/dashboard.php">Dashboard</a>
                <a href="../admin/kelola-petugas.php">Petugas</a>
                <a href="../admin/kelola-kelas.php">Kelas</a>
                <a href="../admin/kelola-siswa.php">Siswa</a>
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>
                <a href="../admin/laporan-pemasukan.php" class="active">Laporan Pemasukan</a>

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
        <h2>Laporan Pemasukan SPP</h2>

        <div class="toolbar-container">
            <form method="GET" action="laporan-pemasukan.php" class="filter-form">
                <label class="filter-label">Dari:</label>
                <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai) ?>" class="filter-input filter-date-input" required>

                <label class="filter-label">Sampai:</label>
                <input type="date" name="tgl_selesai" value="<?= htmlspecialchars($tgl_selesai) ?>" class="filter-input filter-date-input" required>

                <button type="submit" class="btn-cari">Filter Tanggal</button>
                <a href="laporan-pemasukan.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <div class="summary-card">
            <p class="summary-label">Total Pemasukan Periode <strong><?= date('d/m/Y', strtotime($tgl_mulai)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($tgl_selesai)) ?></strong>:</p>
            <h2 class="summary-value">Rp<?= number_format($grand_total, 0, ',', '.') ?></h2>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tgl Bayar</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Bulan / Tahun</th>
                        <th class="tbl-right">Jumlah Bayar</th>
                        <th>Petugas Penerima</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($hasil_list) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil_list)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tgl_bayar'])) ?></td>
                            <td><code><?= htmlspecialchars($row['nis']) ?></code></td>
                            <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                            <td><span class="badge-kelas"><?= htmlspecialchars($row['tingkat']) ?> <?= htmlspecialchars($row['nama_kelas']) ?></span></td>
                            <td><span class="badge-bulan"><?= htmlspecialchars($row['bulan_dibayar']) ?> <?= htmlspecialchars($row['tahun_dibayar']) ?></span></td>
                            <td class="tbl-right"><strong>Rp<?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></strong></td>
                            <td><?= htmlspecialchars($row['nama_petugas'] ?? 'System') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="tbl-empty">Tidak ada transaksi pembayaran pada rentang tanggal ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>