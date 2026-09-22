<?php
require '../config/koneksi.php';

$siswa = null;
$error = '';

$bulan_list = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis  = trim($_POST['nis']);
    $nama = trim($_POST['nama']);

    $nis_aman  = mysqli_real_escape_string($koneksi, $nis);
    $nama_aman = mysqli_real_escape_string($koneksi, $nama);

    $q = mysqli_query($koneksi, "
        SELECT s.*, k.nama_kelas, k.tingkat, k.kompetensi_keahlian
        FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.nis = '$nis_aman' AND s.nama = '$nama_aman'
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
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

    <div class="cek-container">
        
        <!-- FORM CEK TAGIHAN -->
        <div class="form-card form-card-full">
            <h2 class="page-title-center">Cek Tagihan SPP Siswa</h2>
            
            <form method="POST" action="cek-tagihan.php">
                <label>NIS</label>
                <input type="text" name="nis" placeholder="Masukkan NIS..." required value="<?= isset($_POST['nis']) ? htmlspecialchars($_POST['nis']) : '' ?>">

                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan Nama Lengkap..." required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">

                <div class="form-actions form-actions-center">
                    <button type="submit" class="btn-submit-full">Cek Tagihan</button>
                </div>
            </form>

            <?php if ($error): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($siswa): ?>

            <!-- INFORMASI SISWA -->
            <div class="student-info-box">
                <p><strong>NIS:</strong> <?= htmlspecialchars($siswa['nis']) ?></p>
                <p><strong>Nama Siswa:</strong> <?= htmlspecialchars($siswa['nama']) ?></p>
                <p><strong>Kelas:</strong> <?= htmlspecialchars($siswa['tingkat']) ?> <?= htmlspecialchars($siswa['nama_kelas']) ?></p>
                <p><strong>Jurusan:</strong> <?= htmlspecialchars($siswa['kompetensi_keahlian']) ?></p>
            </div>

            <!-- STATUS TAGIHAN TAHUN INI -->
            <h3 class="section-title">Status Tagihan SPP Tahun <?= date('Y') ?></h3>
            <div class="table-card" style="margin-bottom: 25px;">
                <table class="tbl-tagihan">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="tbl-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $nis       = mysqli_real_escape_string($koneksi, $siswa['nis']);
                        $tahun_ini = date('Y');
                        $q_bayar   = mysqli_query($koneksi, "
                            SELECT bulan_dibayar FROM pembayaran
                            WHERE nis = '$nis' AND tahun_dibayar = '$tahun_ini'
                        ");
                        $bulan_lunas = [];
                        while ($row = mysqli_fetch_assoc($q_bayar)) {
                            $bulan_lunas[] = $row['bulan_dibayar'];
                        }

                        foreach ($bulan_list as $b):
                            $is_lunas = in_array($b, $bulan_lunas, true);
                        ?>
                        <tr>
                            <td><strong><?= $b ?></strong></td>
                            <td class="tbl-center">
                                <?php if ($is_lunas): ?>
                                    <span class="badge-lunas">Lunas</span>
                                <?php else: ?>
                                    <span class="badge-belum">Belum Bayar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- RIWAYAT PEMBAYARAN -->
            <h3 class="section-title">Riwayat Transaksi Pembayaran</h3>
            <div class="table-card">
                <table class="tbl-riwayat">
                    <thead>
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th class="tbl-center">Bulan Dibayar</th>
                            <th class="tbl-center">Tahun</th>
                            <th class="tbl-right">Jumlah Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
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
                            <td><?= date('d/m/Y', strtotime($row['tgl_bayar'])) ?></td>
                            <td class="tbl-center"><span class="badge-bulan"><?= htmlspecialchars($row['bulan_dibayar']) ?></span></td>
                            <td class="tbl-center"><?= htmlspecialchars($row['tahun_dibayar']) ?></td>
                            <td class="tbl-right"><strong>Rp<?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></strong></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="4" class="tbl-empty">Belum ada riwayat pembayaran.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

</body>
</html>