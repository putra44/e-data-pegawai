<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/admin_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";
require_once "../helpers/date.php";


$active = 'admin-panel';
$subactive = 'pengumuman';

if (isset($_POST['simpan'])) {

    token_check();

    $judul  = trim($_POST['judul']);
    $isi = strip_tags($_POST['isi'],'<p><br><b><strong><i><u><ul><ol><li><a><span>');
    $pinned = isset($_POST['pinned']) ? 1 : 0;

    if ($judul === '' || $isi === '') {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Judul dan isi pengumuman wajib diisi.'
        ];
    } else {
        mysqli_query($conn, "
            INSERT INTO pengumuman (judul, isi, pinned, status, dibuat_oleh)
            VALUES ('$judul', '$isi', '$pinned', 'aktif', '{$_SESSION['id_user']}')
        ");

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => '<i class="fa fa-check mr-1"></i> Pengumuman berhasil ditambahkan.'
        ];
        header("Location: kelola_pengumuman.php");
        exit;
    }
}

if (isset($_POST['aksi']) && $_POST['aksi'] === 'toggle') {

    token_check();

    $id = (int) $_POST['id'];

    $cek = mysqli_query($conn, "
        SELECT status FROM pengumuman
        WHERE id_pengumuman = $id
        LIMIT 1
    ");
    $row = mysqli_fetch_assoc($cek);

    if ($row) {
        $newStatus = ($row['status'] === 'aktif') ? 'nonaktif' : 'aktif';

        mysqli_query($conn, "
            UPDATE pengumuman
            SET status = '$newStatus'
            WHERE id_pengumuman = $id
        ");

        $_SESSION['flash'] = [
            'type' => 'info',
            'message' => '<i class="fa fa-info-circle mr-1"></i> Status pengumuman berhasil diubah menjadi ' . strtoupper($newStatus)
        ];
    }

    header("Location: kelola_pengumuman.php");
    exit;
}

if (isset($_POST['aksi']) && $_POST['aksi'] === 'pin') {

    token_check();

    $id = (int) $_POST['id'];

    $cek = mysqli_query($conn, "
        SELECT pinned FROM pengumuman
        WHERE id_pengumuman = $id
        LIMIT 1
    ");
    $row = mysqli_fetch_assoc($cek);

    if ($row) {
        $newPin = ($row['pinned'] == 1) ? 0 : 1;

        mysqli_query($conn, "
            UPDATE pengumuman
            SET pinned = $newPin
            WHERE id_pengumuman = $id
        ");

        $_SESSION['flash'] = [
            'type' => $newPin ? 'success' : 'success',
            'message' => $newPin
            ? '<i class="fa fa-thumb-tack mr-1"></i> Pengumuman berhasil disematkan.'
            : '<i class="fa fa-times mr-1"></i> Sematan pengumuman berhasil dilepas.'
        ];
    }

    header("Location: kelola_pengumuman.php");
    exit;
}

if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {

    token_check();

    $id = (int) $_POST['id'];

    $cek = mysqli_query($conn, "
        SELECT status FROM pengumuman
        WHERE id_pengumuman = $id
        LIMIT 1
    ");
    $row = mysqli_fetch_assoc($cek);

    if ($row && $row['status'] === 'nonaktif') {

        mysqli_query($conn, "
            DELETE FROM pengumuman
            WHERE id_pengumuman = $id
        ");

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => '<i class="fa fa-trash mr-1"></i> Pengumuman berhasil dihapus permanen.'
        ];
    } else {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => '<i class="fa fa-warning mr-1"></i> Pengumuman aktif tidak boleh dihapus. Nonaktifkan dulu.'
        ];
    }

    header("Location: kelola_pengumuman.php");
    exit;
}

$q = mysqli_query($conn, "
    SELECT p.*, u.nama
    FROM pengumuman p
    LEFT JOIN users u ON p.dibuat_oleh = u.id_user
    ORDER BY p.pinned DESC, p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengumuman | e-DATA Pegawai</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <link rel="stylesheet" href="../assets/style.css">

</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">
   
    <div class="row mb-3">
        <div class="col-12 text-center">
            <h4 class="mb-0">
            <i class="fa fa-bell"></i> Kelola Pengumuman</h4>
        </div>
    </div>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
    <?= $_SESSION['flash']['message']; ?>
    <button class="close" data-dismiss="alert">&times;</button>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<div class="card mb-4">
<div class="card-body">
<form method="POST">
    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
    <div class="form-group">
        <label>Judul Pengumuman</label>
        <input type="text" name="judul" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Isi Pengumuman</label>

        <div id="toolbar">
            <button class="ql-bold"></button>
            <button class="ql-italic"></button>
            <button class="ql-underline"></button>

            <select class="ql-align">
                <option selected></option>
                <option value="center"></option>
                <option value="right"></option>
                <option value="justify"></option>
            </select>

            <button class="ql-list" value="bullet"></button>
            <button class="ql-list" value="ordered"></button>
            <button class="ql-link"></button>
            <button class="ql-clean"></button>
        </div>

        <div id="editor" style="height:200px;"></div>
        <textarea name="isi" id="isi" hidden></textarea>
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" name="pinned" id="pinned">
        <label for="pinned" class="form-check-label">Sematkan (Pinned)</label>
    </div>
    <button name="simpan" class="btn btn-primary btn-sm">
        <i class="fa fa-save"></i> Simpan
    </button>
</form>
</div>
</div>

<div class="card">
<div class="card-body table-responsive">
<table class="table table-bordered table-sm">
<thead class="thead-light">
<tr>
    <th>No.</th>
    <th>Judul</th>
    <th>Dibuat Oleh</th>
    <th>Status</th>
    <th>Pin</th>
    <th>Tanggal</th>
    <th width="150">Aksi</th>
</tr>
</thead>
<tbody>
<?php $no=1; while ($row = mysqli_fetch_assoc($q)): ?>
<tr>
    <td class="text-center"><?= $no++; ?>.</td>
    <td><?= htmlspecialchars($row['judul']); ?></td>
    <td class="text-center"><?= $row['nama'] ?? 'Admin'; ?></td>
    <td class="text-center">
        <span class="badge badge-<?= $row['status']=='aktif'?'success':'secondary'; ?>">
            <?= ucfirst($row['status']); ?>
        </span>
    </td>
    <td class="text-center">
       <?php if ($row['pinned']): ?>
            <span class="badge badge-warning">
                <i class="fa fa-thumb-tack"></i>
            </span>
                <?php else: ?>
                <span class="text-muted">-</span>
        <?php endif; ?>
    </td>
    <td><?= tanggal($row['created_at']); ?></td>
    <td class="text-center">

    <a href="edit_pengumuman.php?id=<?= $row['id_pengumuman']; ?>"
       class="btn btn-warning btn-sm"
       title="Edit Pengumuman">
        <i class="fa fa-edit"></i>
    </a>

    <?php if ($row['status'] === 'aktif'): ?>

        <form method="POST" action="kelola_pengumuman.php" class="d-inline">
            <input type="hidden" name="aksi" value="pin">
            <input type="hidden" name="id" value="<?= $row['id_pengumuman']; ?>">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

            <?php if ($row['pinned']): ?>
                <button class="btn btn-secondary btn-sm"
                        title="Lepas Sematan">
                    <i class="fa fa-times"></i>
                </button>
            <?php else: ?>
                <button class="btn btn-info btn-sm"
                        title="Sematkan Pengumuman">
                    <i class="fa fa-thumb-tack"></i>
                </button>
            <?php endif; ?>
        </form>

        <form method="POST" action="kelola_pengumuman.php" class="d-inline">
            <input type="hidden" name="aksi" value="toggle">
            <input type="hidden" name="id" value="<?= $row['id_pengumuman']; ?>">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

            <button class="btn btn-danger btn-sm"
                    title="Nonaktifkan Pengumuman"
                    onclick="return confirm('Nonaktifkan pengumuman ini?')">
                <i class="fa fa-ban"></i>
            </button>
        </form>

    <?php else: ?>

        <form method="POST" action="kelola_pengumuman.php" class="d-inline">
            <input type="hidden" name="aksi" value="toggle">
            <input type="hidden" name="id" value="<?= $row['id_pengumuman']; ?>">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

            <button class="btn btn-success btn-sm"
                    title="Aktifkan Pengumuman">
                <i class="fa fa-check-circle"></i>
            </button>
        </form>

        <form method="POST" action="kelola_pengumuman.php" class="d-inline">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= $row['id_pengumuman']; ?>">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

            <button class="btn btn-danger btn-sm"
                    title="Hapus Permanen"
                    onclick="return confirm('HAPUS PERMANEN pengumuman ini?')">
                <i class="fa fa-trash"></i>
            </button>
        </form>

    <?php endif; ?>

</td>

</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>

<?php include "../templates/footer.php"; ?>
<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/quill.js"></script>
</body>
</html>
