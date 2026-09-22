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
    $nis           = $_POST['nis'];
    $nama          = $_POST['nama'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $id_kelas      = $_POST['id_kelas'];
    $alamat        = $_POST['alamat'];
    $no_telp       = $_POST['no_telp'];

    $cek = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nis = '$nis'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('NIS sudah terdaftar!'); window.history.back();</script>";
        exit;
    }

    mysqli_query($koneksi, "INSERT INTO siswa (nis, nama, tanggal_lahir, id_kelas, alamat, no_telp) VALUES ('$nis', '$nama', '$tanggal_lahir', '$id_kelas', '$alamat', '$no_telp')");

    echo "<script>alert('Siswa berhasil ditambahkan!'); window.location.href = 'kelola-siswa.php';</script>";
    exit;
}

if ($aksi === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis           = $_POST['nis'];
    $nama          = $_POST['nama'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $id_kelas      = $_POST['id_kelas'];
    $alamat        = $_POST['alamat'];
    $no_telp       = $_POST['no_telp'];

    mysqli_query($koneksi, "UPDATE siswa SET nama='$nama', tanggal_lahir='$tanggal_lahir', id_kelas='$id_kelas', alamat='$alamat', no_telp='$no_telp' WHERE nis='$nis'");

    echo "<script>alert('Data siswa berhasil diubah!'); window.location.href = 'kelola-siswa.php';</script>";
    exit;
}

if ($aksi === 'hapus' && isset($_GET['nis'])) {
    $nis = $_GET['nis'];

    try {
        mysqli_query($koneksi, "DELETE FROM siswa WHERE nis = '$nis'");
        echo "<script>alert('Siswa berhasil dihapus!'); window.location.href = 'kelola-siswa.php';</script>";
    } catch (mysqli_sql_exception $e) {
        echo "<script>alert('Gagal hapus, siswa ini masih punya riwayat pembayaran!'); window.location.href = 'kelola-siswa.php';</script>";
    }
    exit;
}

$siswa_edit = null;
if ($aksi === 'edit' && isset($_GET['nis'])) {
    $nis    = $_GET['nis'];
    $result = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nis = '$nis'");
    $siswa_edit = mysqli_fetch_assoc($result);

    if (!$siswa_edit) {
        die('Data tidak ditemukan.');
    }
}

$daftar_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas ASC");

$search          = $_GET['search'] ?? '';
$filter_tingkat  = $_GET['filter_tingkat'] ?? '';
$filter_jurusan  = $_GET['filter_jurusan'] ?? '';

$where = [];
if ($search !== '') {
    $where[] = "s.nama LIKE '%$search%'";
}
if ($filter_tingkat !== '') {
    $where[] = "k.tingkat = '$filter_tingkat'";
}
if ($filter_jurusan !== '') {
    $where[] = "k.kompetensi_keahlian = '$filter_jurusan'";
}

$query = "
    SELECT s.*, k.nama_kelas, k.kompetensi_keahlian, k.tingkat
    FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas
";
if (count($where) > 0) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY s.nama ASC";

$hasil_list = mysqli_query($koneksi, $query);

$jurusan_list = [
    'Rekayasa Perangkat Lunak',
    'Teknik Kendaraan Ringan',
    'Teknik Instalasi Tenaga Listrik',
    'Teknik Audio Video',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa</title>
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
                <a href="../admin/kelola-siswa.php" class="active">Siswa</a>
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php">Pembayaran</a>
                <a href="../admin/laporan-pemasukan.php">Laporan Pemasukan</a>
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
        <h2>Kelola Data Siswa</h2>

        <!-- BAGIAN FORM TAMBAH / EDIT -->
        <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>

            <h3 class="tmbh"><?= $aksi === 'edit' ? 'Edit Siswa' : 'Tambah Siswa Baru' ?></h3>

            <div class="form-card form-mini" style="margin-bottom: 30px;">
                <form method="POST" action="kelola-siswa.php">
                    <input type="hidden" name="aksi" value="<?= $aksi === 'edit' ? 'update' : 'tambah' ?>">

                    <label>NIS</label>
                    <input type="text" name="nis"
                           value="<?= $aksi === 'edit' ? htmlspecialchars($siswa_edit['nis']) : '' ?>"
                           <?= $aksi === 'edit' ? 'readonly' : 'required' ?>>

                    <label>Nama</label>
                    <input type="text" name="nama" value="<?= $aksi === 'edit' ? htmlspecialchars($siswa_edit['nama']) : '' ?>" required>

                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?= $aksi === 'edit' ? $siswa_edit['tanggal_lahir'] : '' ?>" required>

                    <label>Kelas</label>
                    <select name="id_kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php
                        mysqli_data_seek($daftar_kelas, 0);
                        while ($k = mysqli_fetch_assoc($daftar_kelas)):
                            $selected = ($aksi === 'edit' && $siswa_edit['id_kelas'] == $k['id_kelas']) ? 'selected' : '';
                        ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?> - <?= htmlspecialchars($k['kompetensi_keahlian']) ?> (<?= htmlspecialchars($k['tingkat']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label>Alamat</label>
                    <textarea name="alamat" style="width: 100%; border: 1px solid #ccc; border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: Arial, sans-serif; margin-bottom: 18px; box-sizing: border-box; resize: vertical; min-height: 80px;"><?= $aksi === 'edit' ? htmlspecialchars($siswa_edit['alamat']) : '' ?></textarea>

                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" value="<?= $aksi === 'edit' ? htmlspecialchars($siswa_edit['no_telp']) : '' ?>">

                    <div class="form-actions">
                        <button type="submit">Simpan</button>
                        <a href="kelola-siswa.php" class="batal-link">Batal</a>
                    </div>
                </form>
            </div>

        <?php else: ?>

            <div style="margin-bottom: 15px;">
                <a href="kelola-siswa.php?aksi=tambah" class="tmbh">Tambah Siswa</a>
            </div>

        <?php endif; ?>

        <!-- BAGIAN PENCARIAN & FILTER -->
        <div class="toolbar-container" style="justify-content: flex-end;">
            <form method="GET" action="kelola-siswa.php" class="filter-form">
                <input type="text" name="search" placeholder="Cari nama siswa..." value="<?= htmlspecialchars($search) ?>" class="filter-input">

                <select name="filter_tingkat" class="filter-select">
                    <option value="">Semua Tingkat</option>
                    <option value="X" <?= $filter_tingkat === 'X' ? 'selected' : '' ?>>X</option>
                    <option value="XI" <?= $filter_tingkat === 'XI' ? 'selected' : '' ?>>XI</option>
                    <option value="XII" <?= $filter_tingkat === 'XII' ? 'selected' : '' ?>>XII</option>
                </select>

                <select name="filter_jurusan" class="filter-select">
                    <option value="">Semua Jurusan</option>
                    <?php foreach ($jurusan_list as $j): ?>
                        <option value="<?= $j ?>" <?= $filter_jurusan === $j ? 'selected' : '' ?>><?= $j ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-cari">Cari / Filter</button>
                <?php if ($search !== '' || $filter_tingkat !== '' || $filter_jurusan !== ''): ?>
                    <a href="kelola-siswa.php" class="btn-reset">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- TABEL DATA SISWA -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($hasil_list) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($hasil_list)): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['nis']) ?></code></td>
                            <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                            <td><span class="badge-kelas"><?= htmlspecialchars($row['tingkat']) ?> <?= htmlspecialchars($row['nama_kelas']) ?></span></td>
                            <td><?= htmlspecialchars($row['kompetensi_keahlian']) ?></td>
                            <td style="text-align: center;">
                                <a href="kelola-siswa.php?aksi=edit&nis=<?= $row['nis'] ?>" class="btn-action-edit">Edit</a>
                                <a href="kelola-siswa.php?aksi=hapus&nis=<?= $row['nis'] ?>"
                                   onclick="return confirm('Yakin hapus siswa ini?')" class="btn-action-delete">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 25px; color: #888;">Data siswa tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>