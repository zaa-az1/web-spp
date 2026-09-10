<?php
require '../config/koneksi.php';

$siswa = null;
$error = '';

$bulan_list = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis  = trim($_POST['nis']);
    $nama = trim($_POST['nama']);

    $q = mysqli_query($koneksi, "
        SELECT s.*, k.nama_kelas, k.tingkat, k.kompetensi_keahlian
        FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.nis = '$nis' AND s.nama = '$nama'
    ");

    $siswa = mysqli_fetch_assoc($q);

    if (!$siswa) {
        $error = 'Data tidak ditemukan. Pastikan NIS dan nama sudah benar.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Tagihan SPP</title>
</head>
<body>

<h2>Cek Tagihan SPP</h2>

<form method="POST" action="cek-tagihan.php">
    <label>NIS</label><br>
    <input type="text" name="nis" required><br><br>

    <label>Nama</label><br>
    <input type="text" name="nama" required><br><br>

    <button type="submit">Cek Tagihan</button>
</form>

<?php if ($error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($siswa): ?>

    <h3>Data Siswa</h3>
    <p>NIS: <?= htmlspecialchars($siswa['nis']) ?></p>
    <p>Nama: <?= htmlspecialchars($siswa['nama']) ?></p>
    <p>Kelas: <?= htmlspecialchars($siswa['tingkat']) ?> <?= htmlspecialchars($siswa['nama_kelas']) ?></p>
    <p>Jurusan: <?= htmlspecialchars($siswa['kompetensi_keahlian']) ?></p>

    <h3>Status Tagihan Tahun <?= date('Y') ?></h3>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr><th>Bulan</th><th>Status</th></tr>
        <?php
        $nis        = $siswa['nis'];
        $tahun_ini  = date('Y');
        $q_bayar    = mysqli_query($koneksi, "
            SELECT bulan_dibayar FROM pembayaran
            WHERE nis = '$nis' AND tahun_dibayar = '$tahun_ini'
        ");
        $bulan_lunas = [];
        while ($row = mysqli_fetch_assoc($q_bayar)) {
            $bulan_lunas[] = $row['bulan_dibayar'];
        }

        foreach ($bulan_list as $b):
            $status = in_array($b, $bulan_lunas) ? 'Lunas' : 'Belum Bayar';
        ?>
        <tr>
            <td><?= $b ?></td>
            <td><?= $status ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Riwayat Pembayaran</h3>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Tanggal Bayar</th>
            <th>Bulan Dibayar</th>
            <th>Tahun</th>
            <th>Jumlah Bayar</th>
        </tr>
        <?php
        $q_riwayat = mysqli_query($koneksi, "
            SELECT * FROM pembayaran
            WHERE nis = '$nis'
            ORDER BY tahun_dibayar DESC, tgl_bayar DESC
        ");
        if (mysqli_num_rows($q_riwayat) > 0):
            while ($row = mysqli_fetch_assoc($q_riwayat)):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['tgl_bayar']) ?></td>
            <td><?= htmlspecialchars($row['bulan_dibayar']) ?></td>
            <td><?= htmlspecialchars($row['tahun_dibayar']) ?></td>
            <td>Rp<?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr><td colspan="4" style="text-align:center;">Belum ada riwayat pembayaran.</td></tr>
        <?php endif; ?>
    </table>

<?php endif; ?>

</body>
</html>