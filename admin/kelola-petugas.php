<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['nama_petugas'])) {
    echo "<script>alert('Silahkan login dulu!'); window.location.href = '../auth/login.php';</script>";
    exit;
}

if ($_SESSION['level'] !== 'admin') {
    echo "<script>alert('Anda bukan admin!'); window.location.href = '../auth/dashboard.php';</script>";
    exit;
}

$level        = $_SESSION['level'];
$username     = $_SESSION['username'];
$nama_petugas = $_SESSION['nama_petugas'];

$aksi = $_GET['aksi'] ?? 'list';

if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $cek_diri  = mysqli_query($koneksi, "SELECT username FROM petugas WHERE id_petugas = '$id'");
    $data_diri = mysqli_fetch_assoc($cek_diri);

    if ($data_diri && $data_diri['username'] === $username) {
        echo "<script>alert('Tidak bisa menghapus akun sendiri!'); window.location.href = 'kelola-petugas.php';</script>";
        exit;
    }

    try {
        mysqli_query($koneksi, "DELETE FROM petugas WHERE id_petugas = '$id'");
        echo "<script>alert('Petugas berhasil dihapus!'); window.location.href = 'kelola-petugas.php';</script>";
    } catch (mysqli_sql_exception $e) {
        echo "<script>alert('Gagal menghapus, petugas ini masih punya data pembayaran!'); window.location.href = 'kelola-petugas.php';</script>";
    }
    exit;
}

$petugas_edit = null;
if ($aksi === 'edit' && isset($_GET['id'])) {
    $id     = $_GET['id'];
    $result = mysqli_query($koneksi, "SELECT * FROM petugas WHERE id_petugas = '$id'");
    $petugas_edit = mysqli_fetch_assoc($result);

    if (!$petugas_edit) {
        die('Data tidak ditemukan.');
    }
}

$search = $_GET['search'] ?? '';

if ($search !== '') {
    $query = "SELECT * FROM petugas WHERE nama_petugas LIKE '%$search%' ORDER BY nama_petugas ASC";
} else {
    $query = "SELECT * FROM petugas ORDER BY nama_petugas ASC";
}

$hasil_list = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Petugas</title>
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
                <a href="../admin/kelola-petugas.php" class="active">Petugas</a>
                <a href="../admin/kelola-kelas.php">Kelas</a>
                <a href="../admin/kelola-siswa.php">Siswa</a>
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>

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
        <h2>Kelola Data Petugas</h2>
        <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>

            <h3 class="tmbh"><?= $aksi === 'edit' ? 'Edit Petugas' : 'Tambah Petugas Baru' ?></h3>

            <div class="form-card">
                <form method="POST" action="proses-petugas.php">
                    <input type="hidden" name="aksi" value="<?= $aksi === 'edit' ? 'update' : 'tambah' ?>">
                    <?php if ($aksi === 'edit'): ?>
                        <input type="hidden" name="id_petugas" value="<?= $petugas_edit['id_petugas'] ?>">
                    <?php endif; ?>

                    <label>Username</label>
                    <input type="text" name="username" value="<?= $aksi === 'edit' ? htmlspecialchars($petugas_edit['username']) : '' ?>" required>

                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_petugas" value="<?= $aksi === 'edit' ? htmlspecialchars($petugas_edit['nama_petugas']) : '' ?>" required>

                    <label>Level</label>
                    <select name="level" required>
                        <option value="petugas" <?= ($aksi === 'edit' && $petugas_edit['level'] === 'petugas') ? 'selected' : '' ?>>Petugas</option>
                        <option value="admin" <?= ($aksi === 'edit' && $petugas_edit['level'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>

                    <label>Password <?= $aksi === 'edit' ? '(kosongkan jika tidak diubah)' : '' ?></label>
                    <input type="password" name="password" <?= $aksi === 'edit' ? '' : 'required' ?>>

                    <div class="form-actions">
                        <button type="submit">Simpan</button>
                        <a href="kelola-petugas.php" class="batal-link">Batal</a>
                    </div>
                </form>
            </div>

        <?php else: ?>

            <div style="margin-bottom: 15px;">
                <a href="kelola-petugas.php?aksi=tambah" class="tmbh">Tambah Petugas</a>
            </div>

            <form method="GET" action="kelola-petugas.php" style="margin-bottom: 15px;">
                <input type="text" name="search" placeholder="Cari nama petugas..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Cari</button>
                <?php if ($search !== ''): ?>
                    <a href="kelola-petugas.php"><button type="button">Reset</button></a>
                <?php endif; ?>
            </form>

            <table border="1" cellpadding="8" cellspacing="0">
                <tr>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Level</th>
                    <th>Aksi</th>
                </tr>
                <?php if (mysqli_num_rows($hasil_list) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($hasil_list)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['nama_petugas']) ?></td>
                        <td><?= htmlspecialchars($row['level']) ?></td>
                        <td>
                            <a href="kelola-petugas.php?aksi=edit&id=<?= $row['id_petugas'] ?>">Edit</a> |
                            <a href="kelola-petugas.php?aksi=hapus&id=<?= $row['id_petugas'] ?>" onclick="return confirm('Yakin hapus petugas ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">Data petugas tidak ditemukan.</td></tr>
                <?php endif; ?>
            </table>

        <?php endif; ?>
    </main>
</body>
</html>