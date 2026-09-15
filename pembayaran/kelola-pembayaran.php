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

// --- PROCESS: TAMBAH PEMBAYARAN ---
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

// --- PROCESS: UPDATE PEMBAYARAN ---
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

// --- PROCESS: HAPUS PEMBAYARAN ---
if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id = $_GET['id'];

    mysqli_query($koneksi, "DELETE FROM pembayaran WHERE id_pembayaran = '$id'");

    echo "<script>alert('Pembayaran berhasil dihapus!'); window.location.href = 'kelola-pembayaran.php';</script>";
    exit;
}

// --- DATA: EDIT ---
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

// --- DATA: FORM TAMBAH (JIKA SISWA SUDAH DIPILIH) ---
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

// --- DATA: TAMPILKAN SISWA BERDASARKAN KELAS DIPILIH ---
$id_kelas_pilihan = $_GET['id_kelas'] ?? null;
$daftar_siswa_kelas = null;
if ($aksi === 'tambah' && $id_kelas_pilihan) {
    $id_kelas_aman = mysqli_real_escape_string($koneksi, $id_kelas_pilihan);
    $daftar_siswa_kelas = mysqli_query($koneksi, "
        SELECT s.nis, s.nama, k.nama_kelas, k.tingkat
        FROM siswa s 
        JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.id_kelas = '$id_kelas_aman'
        ORDER BY s.nama ASC
    ");
}

$q_spp = mysqli_query($koneksi, "SELECT * FROM spp ORDER BY tahun DESC");
$daftar_kelas_filter = mysqli_query($koneksi, "SELECT DISTINCT tingkat, nama_kelas FROM kelas ORDER BY tingkat, nama_kelas");

// --- DATA: LIST PEMBAYARAN ---
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

                <div class="form-card">
                    <div class="form-header-back">
                        <h3>Form Transaksi Pembayaran</h3>
                        <a href="kelola-pembayaran.php?aksi=tambah&id_kelas=<?= $siswa_dipilih['id_kelas'] ?>" class="btn-back">&laquo; Pilih Siswa Lain</a>
                    </div>
                    
                    <div class="student-info-box">
                        <p><strong>Nama Siswa:</strong> <?= htmlspecialchars($siswa_dipilih['nama']) ?> (NIS: <?= htmlspecialchars($siswa_dipilih['nis']) ?>)</p>
                        <p><strong>Kelas:</strong> <?= htmlspecialchars($siswa_dipilih['tingkat']) ?> <?= htmlspecialchars($siswa_dipilih['nama_kelas']) ?> — <?= htmlspecialchars($siswa_dipilih['kompetensi_keahlian']) ?></p>
                    </div>

                    <form method="POST" action="kelola-pembayaran.php">
                        <input type="hidden" name="aksi" value="tambah">
                        <input type="hidden" name="nis" value="<?= htmlspecialchars($siswa_dipilih['nis']) ?>">
                        <input type="hidden" name="tahun_dibayar" value="<?= htmlspecialchars($tahun_form) ?>">

                        <label>Tanggal Bayar</label>
                        <input type="date" name="tgl_bayar" value="<?= date('Y-m-d') ?>" required>

                        <label>Bulan yang Dibayar (Dapat memilih lebih dari 1 bulan)</label>
                        <div class="checkbox-grid">
                            <?php foreach ($bulan_list as $b): ?>
                                <?php $sudah_dibayar = in_array($b, $bulan_sudah_dibayar, true); ?>
                                <label class="checkbox-item <?= $sudah_dibayar ? 'disabled' : '' ?>">
                                    <input type="checkbox" name="bulan_dibayar[]" value="<?= htmlspecialchars($b) ?>" <?= $sudah_dibayar ? 'disabled' : '' ?>>
                                    <span><?= htmlspecialchars($b) ?><?= $sudah_dibayar ? ' (Lunas)' : '' ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <label>Tahun Pembayaran</label>
                        <div class="tahun-selector">
                            <input type="number" value="<?= htmlspecialchars($tahun_form) ?>" readonly class="input-tahun-readonly">
                            <a href="kelola-pembayaran.php?aksi=tambah&nis=<?= urlencode($siswa_dipilih['nis']) ?>&tahun_dibayar=<?= urlencode($tahun_form - 1) ?>" class="btn-tahun">&laquo; Tahun Sebelumnya</a>
                            <a href="kelola-pembayaran.php?aksi=tambah&nis=<?= urlencode($siswa_dipilih['nis']) ?>&tahun_dibayar=<?= urlencode($tahun_form + 1) ?>" class="btn-tahun">Tahun Berikutnya &raquo;</a>
                        </div>

                        <label>SPP (Tahun — Nominal per Bulan)</label>
                        <select name="id_spp" required>
                            <?php while ($s = mysqli_fetch_assoc($q_spp)): ?>
                                <option value="<?= $s['id_spp'] ?>"><?= $s['tahun'] ?> — Rp<?= number_format($s['nominal'], 0, ',', '.') ?></option>
                            <?php endwhile; ?>
                        </select>

                        <label>Jumlah Bayar per Bulan</label>
                        <input type="number" name="jumlah_bayar" placeholder="Masukkan nominal per bulan..." required>
                        <small class="helper-text">*Nominal ini akan dicatat sama untuk setiap bulan yang dicentang.</small>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Simpan Pembayaran</button>
                            <a href="kelola-pembayaran.php" class="batal-link">Batal</a>
                        </div>
                    </form>
                </div>

            <?php else: ?>

                <div class="form-header-back">
                    <h3>Pilih Kelas & Siswa</h3>
                    <a href="kelola-pembayaran.php" class="btn-back">&laquo; Kembali ke Daftar Pembayaran</a>
                </div>

                <div class="tingkat-container">
                    <?php 
                    $tingkat_array = ['X', 'XI', 'XII'];
                    foreach ($tingkat_array as $t): 
                    ?>
                        <details class="tingkat-accordion" <?= (isset($_GET['id_kelas']) && strpos($_GET['id_kelas'], $t) !== false) ? 'open' : '' ?>>
                            <summary class="tingkat-summary">
                                Kelas <?= $t ?>
                                <span class="arrow-icon">▼</span>
                            </summary>
                            <div class="kelas-grid">
                                <?php 
                                $q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas WHERE tingkat = '$t' ORDER BY nama_kelas ASC");
                                if (mysqli_num_rows($q_kelas) > 0):
                                    while ($k = mysqli_fetch_assoc($q_kelas)):
                                        $active_class = ($id_kelas_pilihan == $k['id_kelas']) ? 'active-kelas' : '';
                                ?>
                                    <a href="kelola-pembayaran.php?aksi=tambah&id_kelas=<?= $k['id_kelas'] ?>" class="kelas-card <?= $active_class ?>">
                                        <div class="kelas-title"><?= htmlspecialchars($k['tingkat']) ?> <?= htmlspecialchars($k['nama_kelas']) ?></div>
                                        <div class="kelas-sub"><?= htmlspecialchars($k['kompetensi_keahlian']) ?></div>
                                    </a>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <p class="empty-text">Belum ada kelas di tingkat ini.</p>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>

                <?php if ($daftar_siswa_kelas): ?>
                    <div class="table-card">
                        <div class="table-card-header">
                            <h4>Daftar Siswa di Kelas Terpilih</h4>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($daftar_siswa_kelas) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($daftar_siswa_kelas)): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($row['nis']) ?></code></td>
                                        <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                                        <td><span class="badge-kelas"><?= htmlspecialchars($row['tingkat']) ?> <?= htmlspecialchars($row['nama_kelas']) ?></span></td>
                                        <td style="text-align: center;">
                                            <a href="kelola-pembayaran.php?aksi=tambah&nis=<?= $row['nis'] ?>" class="btn-pilih">Pilih untuk Bayar</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center;">Belum ada data siswa di kelas ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        <?php elseif ($aksi === 'edit'): ?>

            <div class="form-card form-mini">
                <h3>Edit Pembayaran</h3>
                <div class="student-info-box">
                    <p><strong>Nama Siswa:</strong> <?= htmlspecialchars($pembayaran_edit['nama']) ?> (NIS: <?= htmlspecialchars($pembayaran_edit['nis']) ?>)</p>
                </div>

                <form method="POST" action="kelola-pembayaran.php">
                    <input type="hidden" name="aksi" value="update">
                    <input type="hidden" name="id_pembayaran" value="<?= $pembayaran_edit['id_pembayaran'] ?>">

                    <label>Tanggal Bayar</label>
                    <input type="date" name="tgl_bayar" value="<?= $pembayaran_edit['tgl_bayar'] ?>" required>

                    <label>Bulan Dibayar</label>
                    <select name="bulan_dibayar" required>
                        <?php foreach ($bulan_list as $b):
                            $selected = ($pembayaran_edit['bulan_dibayar'] === $b) ? 'selected' : '';
                        ?>
                            <option value="<?= $b ?>" <?= $selected ?>><?= $b ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Tahun Dibayar</label>
                    <input type="number" name="tahun_dibayar" value="<?= $pembayaran_edit['tahun_dibayar'] ?>" required>

                    <label>SPP (Tahun — Nominal)</label>
                    <select name="id_spp" required>
                        <?php
                        mysqli_data_seek($q_spp, 0);
                        while ($s = mysqli_fetch_assoc($q_spp)):
                            $selected = ($pembayaran_edit['id_spp'] == $s['id_spp']) ? 'selected' : '';
                        ?>
                            <option value="<?= $s['id_spp'] ?>" <?= $selected ?>><?= $s['tahun'] ?> — Rp<?= number_format($s['nominal'], 0, ',', '.') ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Jumlah Bayar</label>
                    <input type="number" name="jumlah_bayar" value="<?= $pembayaran_edit['jumlah_bayar'] ?>" required>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Simpan Perubahan</button>
                        <a href="kelola-pembayaran.php" class="batal-link">Batal</a>
                    </div>
                </form>
            </div>

        <?php else: ?>

            <!-- TOOLBAR: TOMBOL TAMBAH DI KIRI, FILTER & PENCARIAN DI KANAN -->
            <div class="toolbar-container">
                <a href="kelola-pembayaran.php?aksi=tambah" class="btn-tambah">+ Input Pembayaran Baru</a>

                <form method="GET" action="kelola-pembayaran.php" class="filter-form">
                    <input type="text" name="search" placeholder="Cari NIS atau Nama..." value="<?= htmlspecialchars($search) ?>" class="filter-input">

                    <select name="filter_kelas" class="filter-select">
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

                    <button type="submit" class="btn-cari">Cari</button>
                    <?php if ($search !== '' || $filter_kelas !== ''): ?>
                        <a href="kelola-pembayaran.php" class="btn-reset">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABEL HASIL PEMBAYARAN -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Tanggal Bayar</th>
                            <th>Bulan</th>
                            <th>Tahun</th>
                            <th>Jumlah Bayar</th>
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
                                <td><?= htmlspecialchars($row['tgl_bayar']) ?></td>
                                <td><span class="badge-bulan"><?= htmlspecialchars($row['bulan_dibayar']) ?></span></td>
                                <td><?= htmlspecialchars($row['tahun_dibayar']) ?></td>
                                <td><strong>Rp<?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></strong></td>
                                <td style="text-align: center;">
                                    <a href="kelola-pembayaran.php?aksi=edit&id=<?= $row['id_pembayaran'] ?>" class="btn-action-edit">Edit</a>
                                    <a href="kelola-pembayaran.php?aksi=hapus&id=<?= $row['id_pembayaran'] ?>"
                                       onclick="return confirm('Yakin hapus data pembayaran ini?')" class="btn-action-delete">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; padding: 25px; color: #888;">Belum ada data pembayaran yang cocok.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </main>
</body>
</html>