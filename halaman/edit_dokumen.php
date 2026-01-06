<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'dokumen';

$id = $_GET['id'] ?? null;
$id = is_numeric($id) ? (int)$id : 0;

if ($id <= 0) {
    header("Location: dokumen.php");
    exit;
}

$qDok = mysqli_query($conn, "
    SELECT * FROM dokumen
    WHERE id_dokumen = $id
      AND deleted_at IS NULL
");
$data = mysqli_fetch_assoc($qDok);

if (!$data) {
    header("Location: dokumen.php");
    exit;
}

$qKategori = mysqli_query($conn, "
    SELECT id_kategori, nama_kategori
    FROM kategori_dokumen
    WHERE is_active = 1
    ORDER BY nama_kategori ASC
");

$folder = "../assets/uploads/dokumen/$id/";
if (!is_dir($folder)) {
    mkdir($folder, 0755, true);
}

if (isset($_POST['simpan'])) {

    token_check();
    
    $no_dokumen = trim($_POST['no_dokumen']);

    if (!preg_match('/^[0-9\/\-]+$/', $no_dokumen)) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'No Dokumen hanya boleh berisi angka, tanda - dan /'
        ];
        header("Location: edit_dokumen.php?id=$id");
        exit;
    }

    $nama_dokumen = trim($_POST['nama_dokumen']);
    $deskripsi    = trim($_POST['deskripsi']);
    $nama_pemilik = trim($_POST['nama_pemilik']);
    $id_kategori  = (int)$_POST['id_kategori'];
    $status_dok   = $_POST['status_dok'];

    if ($no_dokumen === '' || $nama_dokumen === '' || $nama_pemilik === '') {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Field wajib tidak boleh kosong'
        ];
        header("Location: edit_dokumen.php?id=$id");
        exit;
    }

    $cek = mysqli_query($conn, "
        SELECT id_dokumen FROM dokumen
        WHERE no_dokumen = '".mysqli_real_escape_string($conn, $no_dokumen)."'
          AND id_dokumen != $id
    ");
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'No Dokumen sudah digunakan'
        ];
        header("Location: edit_dokumen.php?id=$id");
        exit;
    }

    $total_size = 0;

    // hitung file lama
    foreach (scandir($folder) as $f) {
        if ($f !== '.' && $f !== '..') {
            $total_size += filesize($folder . $f);
        }
    }

    // tambah ukuran file baru
    if (!empty($_FILES['file_dokumen']['name'][0])) {
        $total_size += array_sum($_FILES['file_dokumen']['size']);
    }

    if ($total_size > (10 * 1024 * 1024)) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Total ukuran semua file (lama + baru) maksimal 10MB'
        ];
        header("Location: edit_dokumen.php?id=$id");
        exit;
    }

    mysqli_query($conn, "
        UPDATE dokumen SET
            no_dokumen   = '".mysqli_real_escape_string($conn, $no_dokumen)."',
            nama_dokumen = '".mysqli_real_escape_string($conn, $nama_dokumen)."',
            deskripsi    = '".mysqli_real_escape_string($conn, $deskripsi)."',
            nama_pemilik = '".mysqli_real_escape_string($conn, $nama_pemilik)."',
            id_kategori  = $id_kategori,
            status_dok   = '$status_dok'
        WHERE id_dokumen = $id
    ");

    $allowed_ext  = ['pdf','doc','docx','jpg','jpeg','png'];
    $allowed_mime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if (!empty($_FILES['file_dokumen']['name'][0])) {
        foreach ($_FILES['file_dokumen']['name'] as $i => $name) {

            if ($_FILES['file_dokumen']['error'][$i] !== 0) continue;

            $tmp  = $_FILES['file_dokumen']['tmp_name'][$i];
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = finfo_file($finfo, $tmp);

            if (!in_array($ext, $allowed_ext)) continue;
            if (!in_array($mime, $allowed_mime)) continue;

            $new_name = uniqid('dok_', true) . '.' . $ext;
            move_uploaded_file($tmp, $folder . $new_name);
        }
    }

    finfo_close($finfo);
    
    $jumlah_file = count(scandir($folder)) - 2;
    mysqli_query($conn, "
        UPDATE dokumen
        SET jumlah_file = $jumlah_file
        WHERE id_dokumen = $id
    ");

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Dokumen berhasil diperbarui'
    ];

    header("Location: detail_dokumen.php?id=$id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Dokumen | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

<h4 class="text-center mb-4">Edit Dokumen</h4>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['type']; ?>">
    <?= $_SESSION['flash']['message']; ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
<div class="card mb-3">
<div class="card-body">

<div class="form-group">
    <label>Tambah File Baru (opsional)</label>
    <input type="file" name="file_dokumen[]" class="form-control" multiple
           accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
    <small class="text-muted">Maksimal 10MB</small>
</div>

<div class="form-group">
    <label>No Dokumen</label>
    <input type="text"
           name="no_dokumen"
           class="form-control"
           value="<?= htmlspecialchars($data['no_dokumen']); ?>"
           required
           pattern="[0-9\/\-]+"
           oninput="this.value = this.value.replace(/[^0-9\/\-]/g, '')"
           placeholder="Hanya angka, tanda - dan /">
</div>

<div class="form-group">
    <label>Nama Dokumen</label>
    <input type="text" name="nama_dokumen" class="form-control"
           value="<?= htmlspecialchars($data['nama_dokumen']); ?>" required>
</div>

<div class="form-group">
    <label>Nama Pemilik</label>
    <input type="text" name="nama_pemilik" class="form-control"
           value="<?= htmlspecialchars($data['nama_pemilik']); ?>" required>
</div>

<div class="form-group">
    <label>Kategori</label>
    <select name="id_kategori" class="form-control" required>
        <?php while ($k = mysqli_fetch_assoc($qKategori)): ?>
            <option value="<?= $k['id_kategori']; ?>"
                <?= ($data['id_kategori'] == $k['id_kategori']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($k['nama_kategori']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Status Dokumen</label>
    <select name="status_dok" class="form-control">
        <option value="berlaku" <?= ($data['status_dok']=='berlaku')?'selected':''; ?>>Berlaku</option>
        <option value="kadaluarsa" <?= ($data['status_dok']=='kadaluarsa')?'selected':''; ?>>Kadaluarsa</option>
    </select>
</div>

<div class="form-group">
    <label>Deskripsi</label>
    <textarea name="deskripsi" class="form-control"><?= htmlspecialchars($data['deskripsi']); ?></textarea>
</div>

</div>

<div class="card-footer text-right">
    <a href="dokumen.php?id=<?= $id; ?>" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Batal
    </a>
    <button type="submit" name="simpan" class="btn btn-primary btn-sm">
        <i class="fa fa-save"></i> Simpan Perubahan
    </button>
</div>

</div>
</form>

</div>
</div>

<?php include "../templates/footer.php"; ?>
<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
