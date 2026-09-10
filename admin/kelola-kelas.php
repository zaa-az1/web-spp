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

if ($level !== 'admin') {
    echo "<script>alert('Anda tidak punya akses ke halaman ini!'); window.location.href = '../auth/dashboard.php';</script>";
    exit;
}

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? 'list';

if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tingkat             = $_POST['tingkat'];
    $nama_kelas          = $_POST['nama_kelas'];
    $kompetensi_keahlian = $_POST['kompetensi_keahlian'];

    mysqli_query($koneksi, "INSERT INTO kelas (tingkat, nama_kelas, kompetensi_keahlian) VALUES ('$tingkat', '$nama_kelas', '$kompetensi_keahlian')");

    echo "<script>alert('Kelas berhasil ditambahkan!'); window.location.href = 'kelola-kelas.php';</script>";
    exit;
}

if ($aksi === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                  = $_POST['id_kelas'];
    $tingkat             = $_POST['tingkat'];
    $nama_kelas          = $_POST['nama_kelas'];
    $kompetensi_keahlian = $_POST['kompetensi_keahlian'];

    mysqli_query($koneksi, "UPDATE kelas SET tingkat='$tingkat', nama_kelas='$nama_kelas', kompetensi_keahlian='$kompetensi_keahlian' WHERE id_kelas='$id'");

    echo "<script>alert('Kelas berhasil diperbarui!'); window.location.href = 'kelola-kelas.php';</script>";
    exit;
}

if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        mysqli_query($koneksi, "DELETE FROM kelas WHERE id_kelas = '$id'");
        echo "<script>alert('Kelas berhasil dihapus!'); window.location.href = 'kelola-kelas.php';</script>";
    } catch (mysqli_sql_exception $e) {
        echo "<script>alert('Gagal hapus, kelas ini masih punya data siswa!'); window.location.href = 'kelola-kelas.php';</script>";
    }
    exit;
}

$kelas_edit = null;
if ($aksi === 'edit' && isset($_GET['id'])) {
    $id     = $_GET['id'];
    $result = mysqli_query($koneksi, "SELECT * FROM kelas WHERE id_kelas = '$id'");
    $kelas_edit = mysqli_fetch_assoc($result);

    if (!$kelas_edit) {
        die('Data tidak ditemukan.');
    }
}

$search  = $_GET['search'] ?? '';
$filter_tingkat = $_GET['filter_tingkat'] ?? '';

$where = [];
if ($search !== '') {
    $where[] = "(nama_kelas LIKE '%$search%' OR kompetensi_keahlian LIKE '%$search%')";
}
if ($filter_tingkat !== '') {
    $where[] = "tingkat = '$filter_tingkat'";
}

$query = "SELECT * FROM kelas";
if (count($where) > 0) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY tingkat, nama_kelas ASC";

$hasil_list = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas</title>
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
            <a href="../admin/kelola-kelas.php" class="active">Kelas</a>
            <a href="../admin/kelola-siswa.php">Siswa</a>
            <a href="../admin/kelola-spp.php">SPP</a>
            <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>
        <?php else: ?>
            <a href="../auth/dashboard.php">Dashboard</a>
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
    <div class="form-card">
        <h2>Kelola Data Kelas</h2>

        <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>

            <h3><?= $aksi === 'edit' ? 'Edit Kelas' : 'Tambah Kelas Baru' ?></h3>

            <form method="POST" action="kelola-kelas.php">
                <input type="hidden" name="aksi" value="<?= $aksi === 'edit' ? 'update' : 'tambah' ?>">
                <?php if ($aksi === 'edit'): ?>
                    <input type="hidden" name="id_kelas" value="<?= $kelas_edit['id_kelas'] ?>">
                <?php endif; ?>

                <label>Tingkat</label><br>
                <select name="tingkat" required>
                    <option value="">-- Pilih Tingkat --</option>
                    <option value="X" <?= ($aksi === 'edit' && $kelas_edit['tingkat'] === 'X') ? 'selected' : '' ?>>X</option>
                    <option value="XI" <?= ($aksi === 'edit' && $kelas_edit['tingkat'] === 'XI') ? 'selected' : '' ?>>XI</option>
                    <option value="XII" <?= ($aksi === 'edit' && $kelas_edit['tingkat'] === 'XII') ? 'selected' : '' ?>>XII</option>
                </select><br><br>

                <label>Nama Kelas</label><br>
                <input type="text" name="nama_kelas" value="<?= $aksi === 'edit' ? htmlspecialchars($kelas_edit['nama_kelas']) : '' ?>" placeholder="Contoh: RPL 1" required><br><br>

                <label>Kompetensi Keahlian</label><br>
                <select name="kompetensi_keahlian" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <?php
                    $jurusan_list = [
                        'Rekayasa Perangkat Lunak',
                        'Teknik Kendaraan Ringan',
                        'Teknik Instalasi Tenaga Listrik',
                        'Teknik Audio Video',
                    ];
                    foreach ($jurusan_list as $j):
                        $selected = ($aksi === 'edit' && $kelas_edit['kompetensi_keahlian'] === $j) ? 'selected' : '';
                    ?>
                        <option value="<?= $j ?>" <?= $selected ?>><?= $j ?></option>
                    <?php endforeach; ?>
                </select><br><br>

                <button type="submit">Simpan</button>
                <a href="kelola-kelas.php">Batal</a>
            </form>

        <?php else: ?>

            <div style="margin-bottom: 15px;">
                <a href="kelola-kelas.php?aksi=tambah">Tambah Kelas</a>
            </div>

            <form method="GET" action="kelola-kelas.php" style="margin-bottom: 15px;">
                <input type="text" name="search" placeholder="Cari nama kelas / jurusan..." value="<?= htmlspecialchars($search) ?>">

                <select name="filter_tingkat">
                    <option value="">Semua Tingkat</option>
                    <option value="X" <?= $filter_tingkat === 'X' ? 'selected' : '' ?>>X</option>
                    <option value="XI" <?= $filter_tingkat === 'XI' ? 'selected' : '' ?>>XI</option>
                    <option value="XII" <?= $filter_tingkat === 'XII' ? 'selected' : '' ?>>XII</option>
                </select>

                <button type="submit">Cari / Filter</button>
                <a href="kelola-kelas.php"><button type="button">Reset</button></a>
            </form>

            <table border="1" cellpadding="8" cellspacing="0">
                <tr>
                    <th>Tingkat</th>
                    <th>Nama Kelas</th>
                    <th>Kompetensi Keahlian</th>
                    <th>Aksi</th>
                </tr>
                <?php if (mysqli_num_rows($hasil_list) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($hasil_list)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['tingkat']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td><?= htmlspecialchars($row['kompetensi_keahlian']) ?></td>
                        <td>
                            <a href="kelola-kelas.php?aksi=edit&id=<?= $row['id_kelas'] ?>">Edit</a> |
                            <a href="kelola-kelas.php?aksi=hapus&id=<?= $row['id_kelas'] ?>"
                               onclick="return confirm('Yakin hapus kelas ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">Data kelas tidak ditemukan.</td></tr>
                <?php endif; ?>
            </table>

        <?php endif; ?>
    </div>
</main>

</body>
</html>