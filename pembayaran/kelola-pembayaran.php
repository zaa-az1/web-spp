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
$id_petugas   = $_SESSION['id_petugas'];

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? 'list';

$bulan_list = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis           = $_POST['nis'];
    $tgl_bayar     = $_POST['tgl_bayar'];
    $tahun_dibayar = $_POST['tahun_dibayar'];
    $id_spp        = $_POST['id_spp'];
    $jumlah_bayar  = $_POST['jumlah_bayar'];
    $bulan_dipilih = $_POST['bulan_dibayar'] ?? [];

    if (count($bulan_dipilih) === 0) {
        echo "<script>alert('Pilih minimal satu bulan!'); window.history.back();</script>";
        exit;
    }

    $nis_aman = mysqli_real_escape_string($koneksi, $nis);
    $tahun_aman = mysqli_real_escape_string($koneksi, $tahun_dibayar);
    $bulan_sudah_dibayar = [];
    $q_bulan_sudah_dibayar = mysqli_query($koneksi, "
        SELECT bulan_dibayar
        FROM pembayaran
        WHERE nis = '$nis_aman' AND tahun_dibayar = '$tahun_aman'
    ");
    while ($row = mysqli_fetch_assoc($q_bulan_sudah_dibayar)) {
        $bulan_sudah_dibayar[] = $row['bulan_dibayar'];
    }

    $bulan_dipilih = array_values(array_diff($bulan_dipilih, $bulan_sudah_dibayar));
    if (count($bulan_dipilih) === 0) {
        echo "<script>alert('Bulan yang dipilih sudah dibayar untuk tahun tersebut!'); window.history.back();</script>";
        exit;
    }

    foreach ($bulan_dipilih as $bulan) {
        mysqli_query($koneksi, "INSERT INTO pembayaran (id_petugas, nis, tgl_bayar, bulan_dibayar, tahun_dibayar, id_spp, jumlah_bayar) VALUES ('$id_petugas', '$nis', '$tgl_bayar', '$bulan', '$tahun_dibayar', '$id_spp', '$jumlah_bayar')");
    }

    $jumlah_bulan = count($bulan_dipilih);
    echo "<script>alert('Pembayaran untuk $jumlah_bulan bulan berhasil disimpan!'); window.location.href = 'kelola-pembayaran.php';</script>";
    exit;
}

if ($aksi === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = $_POST['id_pembayaran'];
    $tgl_bayar     = $_POST['tgl_bayar'];
    $bulan_dibayar = $_POST['bulan_dibayar'];
    $tahun_dibayar = $_POST['tahun_dibayar'];
    $id_spp        = $_POST['id_spp'];
    $jumlah_bayar  = $_POST['jumlah_bayar'];

    mysqli_query($koneksi, "UPDATE pembayaran SET tgl_bayar='$tgl_bayar', bulan_dibayar='$bulan_dibayar', tahun_dibayar='$tahun_dibayar', id_spp='$id_spp', jumlah_bayar='$jumlah_bayar' WHERE id_pembayaran='$id'");

    echo "<script>alert('Pembayaran berhasil diperbarui!'); window.location.href = 'kelola-pembayaran.php';</script>";
    exit;
}

if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id = $_GET['id'];

    mysqli_query($koneksi, "DELETE FROM pembayaran WHERE id_pembayaran = '$id'");

    echo "<script>alert('Pembayaran berhasil dihapus!'); window.location.href = 'kelola-pembayaran.php';</script>";
    exit;
}

$pembayaran_edit = null;
if ($aksi === 'edit' && isset($_GET['id'])) {
    $id     = $_GET['id'];
    $result = mysqli_query($koneksi, "
        SELECT p.*, s.nama
        FROM pembayaran p JOIN siswa s ON p.nis = s.nis
        WHERE p.id_pembayaran = '$id'
    ");
    $pembayaran_edit = mysqli_fetch_assoc($result);

    if (!$pembayaran_edit) {
        die('Data tidak ditemukan.');
    }
}

$siswa_dipilih = null;
$tahun_form = isset($_GET['tahun_dibayar']) && is_string($_GET['tahun_dibayar']) && ctype_digit($_GET['tahun_dibayar'])
    ? $_GET['tahun_dibayar']
    : date('Y');
$bulan_sudah_dibayar = [];
if ($aksi === 'tambah' && isset($_GET['nis'])) {
    $nis = $_GET['nis'];
    $q = mysqli_query($koneksi, "
        SELECT s.*, k.nama_kelas, k.tingkat, k.kompetensi_keahlian
        FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.nis = '$nis'
    ");
    $siswa_dipilih = mysqli_fetch_assoc($q);

    if ($siswa_dipilih) {
        $nis_aman = mysqli_real_escape_string($koneksi, $siswa_dipilih['nis']);
        $tahun_aman = mysqli_real_escape_string($koneksi, $tahun_form);
        $q_bulan_sudah_dibayar = mysqli_query($koneksi, "
            SELECT bulan_dibayar
            FROM pembayaran
            WHERE nis = '$nis_aman' AND tahun_dibayar = '$tahun_aman'
        ");
        while ($row = mysqli_fetch_assoc($q_bulan_sudah_dibayar)) {
            $bulan_sudah_dibayar[] = $row['bulan_dibayar'];
        }
    }
}

$hasil_cari = null;
if ($aksi === 'tambah' && isset($_GET['cari']) && $_GET['cari'] !== '') {
    $cari = $_GET['cari'];
    $hasil_cari = mysqli_query($koneksi, "
        SELECT s.nis, s.nama, k.nama_kelas
        FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.nis LIKE '%$cari%' OR s.nama LIKE '%$cari%'
    ");
}

$q_spp = mysqli_query($koneksi, "SELECT * FROM spp ORDER BY tahun DESC");

$daftar_kelas_filter = mysqli_query($koneksi, "SELECT DISTINCT tingkat, nama_kelas FROM kelas ORDER BY tingkat, nama_kelas");

$search       = $_GET['search'] ?? '';
$filter_kelas = $_GET['filter_kelas'] ?? '';

if ($aksi === 'list') {
    $where = [];
    if ($search !== '') {
        $where[] = "(s.nama LIKE '%$search%' OR p.nis LIKE '%$search%')";
    }
    if ($filter_kelas !== '') {
        $where[] = "k.nama_kelas = '$filter_kelas'";
    }

    $query = "
        SELECT p.*, s.nama, k.nama_kelas, k.tingkat
        FROM pembayaran p
        JOIN siswa s ON p.nis = s.nis
        JOIN kelas k ON s.id_kelas = k.id_kelas
    ";
    if (count($where) > 0) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    $query .= " ORDER BY p.tgl_bayar DESC";

    $hasil_list = mysqli_query($koneksi, $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran</title>
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
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php" class="active">Pembayaran</a>
            <?php else: ?>
                <a href="../auth/dashboard.php">Dashboard</a>
                <a href="../admin/kelola-spp.php">SPP</a>
                <a href="../pembayaran/kelola-pembayaran.php" class="active">Pembayaran</a>
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
        <h2>Kelola Pembayaran SPP</h2>

        <?php if ($aksi === 'tambah'): ?>

            <?php if ($siswa_dipilih): ?>

                <h3>Form Pembayaran</h3>
                <p>Nama Siswa: <?= htmlspecialchars($siswa_dipilih['nama']) ?> (<?= htmlspecialchars($siswa_dipilih['nis']) ?>)</p>
                <p>Kelas: <?= htmlspecialchars($siswa_dipilih['tingkat']) ?> <?= htmlspecialchars($siswa_dipilih['nama_kelas']) ?> - <?= htmlspecialchars($siswa_dipilih['kompetensi_keahlian']) ?></p>

                <form method="POST" action="kelola-pembayaran.php">
                    <input type="hidden" name="aksi" value="tambah">
                    <input type="hidden" name="nis" value="<?= htmlspecialchars($siswa_dipilih['nis']) ?>">
                    <input type="hidden" name="tahun_dibayar" value="<?= htmlspecialchars($tahun_form) ?>">

                    <label>Tanggal Bayar</label><br>
                    <input type="date" name="tgl_bayar" value="<?= date('Y-m-d') ?>" required><br><br>

                    <label>Bulan yang Dibayar (bisa pilih lebih dari satu untuk bayar rangkap)</label><br>
                    <?php foreach ($bulan_list as $b): ?>
                        <?php $sudah_dibayar = in_array($b, $bulan_sudah_dibayar, true); ?>
                        <label>
                            <input type="checkbox" name="bulan_dibayar[]" value="<?= htmlspecialchars($b) ?>" <?= $sudah_dibayar ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($b) ?><?= $sudah_dibayar ? ' (Sudah dibayar)' : '' ?>
                        </label><br>
                    <?php endforeach; ?>
                    <br>

                    <label>Tahun Pembayaran</label><br>
                    <input type="number" value="<?= htmlspecialchars($tahun_form) ?>" readonly><br>
                    <a href="kelola-pembayaran.php?aksi=tambah&nis=<?= urlencode($siswa_dipilih['nis']) ?>&tahun_dibayar=<?= urlencode($tahun_form - 1) ?>">Tahun Sebelumnya</a>
                    |
                    <a href="kelola-pembayaran.php?aksi=tambah&nis=<?= urlencode($siswa_dipilih['nis']) ?>&tahun_dibayar=<?= urlencode($tahun_form + 1) ?>">Tahun Berikutnya</a>
                    <br><br>

                    <label>SPP (Tahun - Nominal per bulan)</label><br>
                    <select name="id_spp" required>
                        <?php while ($s = mysqli_fetch_assoc($q_spp)): ?>
                            <option value="<?= $s['id_spp'] ?>"><?= $s['tahun'] ?> - Rp<?= number_format($s['nominal'], 0, ',', '.') ?></option>
                        <?php endwhile; ?>
                    </select><br><br>

                    <label>Jumlah Bayar per Bulan</label><br>
                    <input type="number" name="jumlah_bayar" required>
                    <br><small>Nominal ini akan dicatat sama untuk setiap bulan yang dicentang di atas.</small><br><br>

                    <button type="submit">Simpan Pembayaran</button>
                    <a href="kelola-pembayaran.php">Batal</a>
                </form>

            <?php else: ?>

                <h3>Cari Siswa</h3>
                <form method="GET" action="kelola-pembayaran.php">
                    <input type="hidden" name="aksi" value="tambah">
                    <input type="text" name="cari" placeholder="NIS atau Nama Siswa" value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                    <button type="submit">Cari</button>
                </form>

                <?php if ($hasil_cari): ?>
                    <table border="1" cellpadding="8" cellspacing="0">
                        <tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Aksi</th></tr>
                        <?php while ($row = mysqli_fetch_assoc($hasil_cari)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nis']) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td><a href="kelola-pembayaran.php?aksi=tambah&nis=<?= $row['nis'] ?>">Pilih</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                <?php endif; ?>

            <?php endif; ?>

        <?php elseif ($aksi === 'edit'): ?>

            <h3>Edit Pembayaran</h3>
            <p>Nama Siswa: <?= htmlspecialchars($pembayaran_edit['nama']) ?> (<?= htmlspecialchars($pembayaran_edit['nis']) ?>)</p>

            <form method="POST" action="kelola-pembayaran.php">
                <input type="hidden" name="aksi" value="update">
                <input type="hidden" name="id_pembayaran" value="<?= $pembayaran_edit['id_pembayaran'] ?>">

                <label>Tanggal Bayar</label><br>
                <input type="date" name="tgl_bayar" value="<?= $pembayaran_edit['tgl_bayar'] ?>" required><br><br>

                <label>Bulan Dibayar</label><br>
                <select name="bulan_dibayar" required>
                    <?php foreach ($bulan_list as $b):
                        $selected = ($pembayaran_edit['bulan_dibayar'] === $b) ? 'selected' : '';
                    ?>
                        <option value="<?= $b ?>" <?= $selected ?>><?= $b ?></option>
                    <?php endforeach; ?>
                </select><br><br>

                <label>Tahun Dibayar</label><br>
                <input type="number" name="tahun_dibayar" value="<?= $pembayaran_edit['tahun_dibayar'] ?>" required><br><br>

                <label>SPP (Tahun - Nominal)</label><br>
                <select name="id_spp" required>
                    <?php
                    mysqli_data_seek($q_spp, 0);
                    while ($s = mysqli_fetch_assoc($q_spp)):
                        $selected = ($pembayaran_edit['id_spp'] == $s['id_spp']) ? 'selected' : '';
                    ?>
                        <option value="<?= $s['id_spp'] ?>" <?= $selected ?>><?= $s['tahun'] ?> - Rp<?= number_format($s['nominal'], 0, ',', '.') ?></option>
                    <?php endwhile; ?>
                </select><br><br>

                <label>Jumlah Bayar</label><br>
                <input type="number" name="jumlah_bayar" value="<?= $pembayaran_edit['jumlah_bayar'] ?>" required><br><br>

                <button type="submit">Simpan Perubahan</button>
                <a href="kelola-pembayaran.php">Batal</a>
            </form>

        <?php else: ?>

            <div style="margin-bottom: 15px;">
                <a href="kelola-pembayaran.php?aksi=tambah">Input Pembayaran Baru</a>
            </div>

            <form method="GET" action="kelola-pembayaran.php" style="margin-bottom: 15px;">
                <input type="text" name="search" placeholder="Cari nama siswa atau NIS..." value="<?= htmlspecialchars($search) ?>">

                <select name="filter_kelas">
                    <option value="">Semua Kelas</option>
                    <?php
                    mysqli_data_seek($daftar_kelas_filter, 0);
                    while ($k = mysqli_fetch_assoc($daftar_kelas_filter)):
                        $label = $k['tingkat'] . ' ' . $k['nama_kelas'];
                        $selected = $filter_kelas === $k['nama_kelas'] ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($k['nama_kelas']) ?>" <?= $selected ?>><?= htmlspecialchars($label) ?></option>
                    <?php endwhile; ?>
                </select>

                <button type="submit">Cari / Filter</button>
                <a href="kelola-pembayaran.php"><button type="button">Reset</button></a>
            </form>

            <table border="1" cellpadding="8" cellspacing="0">
                <tr>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Tanggal Bayar</th>
                    <th>Bulan</th>
                    <th>Tahun</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
                <?php if (mysqli_num_rows($hasil_list) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($hasil_list)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['tingkat']) ?> <?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td><?= htmlspecialchars($row['tgl_bayar']) ?></td>
                        <td><?= htmlspecialchars($row['bulan_dibayar']) ?></td>
                        <td><?= htmlspecialchars($row['tahun_dibayar']) ?></td>
                        <td>Rp<?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                        <td>
                            <a href="kelola-pembayaran.php?aksi=edit&id=<?= $row['id_pembayaran'] ?>">Edit</a> |
                            <a href="kelola-pembayaran.php?aksi=hapus&id=<?= $row['id_pembayaran'] ?>"
                               onclick="return confirm('Yakin hapus data pembayaran ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">Belum ada data pembayaran.</td></tr>
                <?php endif; ?>
            </table>

        <?php endif; ?>
    </main>
</div>
</body>
</html>