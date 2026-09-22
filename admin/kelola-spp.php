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

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? 'list';

if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun   = $_POST['tahun'];
    $nominal = $_POST['nominal'];

    mysqli_query($koneksi, "INSERT INTO spp (tahun, nominal) VALUES ('$tahun', '$nominal')");

    echo "<script>alert('Data SPP berhasil ditambahkan!'); window.location.href = 'kelola-spp.php';</script>";
    exit;
}

if ($aksi === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = $_POST['id_spp'];
    $tahun   = $_POST['tahun'];
    $nominal = $_POST['nominal'];

    mysqli_query($koneksi, "UPDATE spp SET tahun='$tahun', nominal='$nominal' WHERE id_spp='$id'");

    echo "<script>alert('Data SPP berhasil diperbarui!'); window.location.href = 'kelola-spp.php';</script>";
    exit;
}

if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        mysqli_query($koneksi, "DELETE FROM spp WHERE id_spp = '$id'");
        echo "<script>alert('Data SPP berhasil dihapus!'); window.location.href = 'kelola-spp.php';</script>";
    } catch (mysqli_sql_exception $e) {
        echo "<script>alert('Gagal hapus, data ini masih dipakai di riwayat pembayaran!'); window.location.href = 'kelola-spp.php';</script>";
    }
    exit;
}

$spp_edit = null;
if ($aksi === 'edit' && isset($_GET['id'])) {
    $id     = $_GET['id'];
    $result = mysqli_query($koneksi, "SELECT * FROM spp WHERE id_spp = '$id'");
    $spp_edit = mysqli_fetch_assoc($result);

    if (!$spp_edit) {
        die('Data tidak ditemukan.');
    }
}

$hasil_list = mysqli_query($koneksi, "SELECT * FROM spp ORDER BY tahun DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola SPP</title>
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
                <a href="../auth/dashboard.php">Dashboard</a>
                <a href="../admin/kelola-petugas.php">Petugas</a>
                <a href="../admin/kelola-kelas.php">Kelas</a>
                <a href="../admin/kelola-siswa.php">Siswa</a>
                <a href="../admin/kelola-spp.php" class="active">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>
            <?php else: ?>
                <a href="../auth/dashboard.php">Dashboard</a>
                <a href="../admin/kelola-spp.php" class="active">SPP</a>
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
        <h2>Kelola Nominal SPP</h2>

        <!-- FORM TAMBAH / EDIT -->
        <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>

            <h3 class="tmbh"><?= $aksi === 'edit' ? 'Edit Data SPP' : 'Tambah Data SPP' ?></h3>

            <div class="form-card form-mini" style="margin-bottom: 30px;">
                <form method="POST" action="kelola-spp.php">
                    <input type="hidden" name="aksi" value="<?= $aksi === 'edit' ? 'update' : 'tambah' ?>">
                    <?php if ($aksi === 'edit'): ?>
                        <input type="hidden" name="id_spp" value="<?= $spp_edit['id_spp'] ?>">
                    <?php endif; ?>

                    <label>Tahun</label>
                    <input type="number" name="tahun" value="<?= $aksi === 'edit' ? htmlspecialchars($spp_edit['tahun']) : date('Y') ?>" required>

                    <label>Nominal</label>
                    <input type="number" name="nominal" value="<?= $aksi === 'edit' ? htmlspecialchars($spp_edit['nominal']) : '' ?>" placeholder="Masukkan nominal SPP..." required>

                    <div class="form-actions">
                        <button type="submit">Simpan</button>
                        <a href="kelola-spp.php" class="batal-link">Batal</a>
                    </div>
                </form>
            </div>

        <?php else: ?>

            <div style="margin-bottom: 15px;">
                <a href="kelola-spp.php?aksi=tambah" class="tmbh">Tambah Data SPP</a>
            </div>

            <!-- TABEL DATA SPP -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Tahun</th>
                            <th>Nominal</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($hasil_list) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($hasil_list)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['tahun']) ?></strong></td>
                                <td>Rp<?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                <td style="text-align: center;">
                                    <a href="kelola-spp.php?aksi=edit&id=<?= $row['id_spp'] ?>" class="btn-action-edit">Edit</a>
                                    <a href="kelola-spp.php?aksi=hapus&id=<?= $row['id_spp'] ?>"
                                       onclick="return confirm('Yakin hapus data SPP ini?')" class="btn-action-delete">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; padding: 25px; color: #888;">Data SPP belum ada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </main>
</body>
</html>