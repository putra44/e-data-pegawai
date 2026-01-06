<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/admin_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: kelola_pengumuman.php");
    exit;
}

/* =========================
   AMBIL DATA PENGUMUMAN
========================= */
$q = mysqli_query($conn, "
    SELECT * FROM pengumuman 
    WHERE id_pengumuman='$id'
");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    header("Location: kelola_pengumuman.php");
    exit;
}

/* =========================
   UPDATE PENGUMUMAN
========================= */
if (isset($_POST['update'])) {

    token_check();

    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = strip_tags($_POST['isi'],'<p><br><b><strong><i><u><ul><ol><li><a><span>');

    if (trim($judul) === '' || trim($isi) === '') {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Judul dan isi tidak boleh kosong.'
        ];
    } else {

        mysqli_query($conn, "
            UPDATE pengumuman 
            SET judul='$judul', isi='$isi'
            WHERE id_pengumuman='$id'
        ");

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Pengumuman berhasil diperbarui.'
        ];

        header("Location: kelola_pengumuman.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pengumuman</title>

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>


</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

<h4 class="mb-3">
    <i class="fa fa-edit"></i> Edit Pengumuman
</h4>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['type']; ?>">
    <?= $_SESSION['flash']['message']; ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<form method="POST">
    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

    <div class="form-group">
        <label>Judul Pengumuman</label>
        <input type="text"
               name="judul"
               class="form-control"
               value="<?= htmlspecialchars($data['judul']); ?>"
               required>
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

        <div id="editor" style="height:220px;">
            <?= $data['isi']; ?>
        </div>

        <textarea name="isi" id="isi" hidden></textarea>
    </div>

    <button name="update" class="btn btn-primary">
        <i class="fa fa-save"></i> Simpan Perubahan
    </button>

    <a href="kelola_pengumuman.php" class="btn btn-secondary">
        Batal
    </a>

</form>

</div>
</div>

<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/quill.js"></script>
</body>
</html>