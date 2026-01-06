<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active = 'dokumen';

$qKategori = mysqli_query($conn, "
    SELECT id_kategori, nama_kategori
    FROM kategori_dokumen
    WHERE is_active = 1
    ORDER BY nama_kategori ASC
");

if (isset($_POST['simpan'])) {

    token_check();

    $no_dokumen = trim($_POST['no_dokumen']);

    if (!preg_match('/^[0-9\/\-]+$/', $no_dokumen)) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'No Dokumen hanya boleh berisi angka, tanda - dan /'
        ];
        header("Location: tambah_dokumen.php");
        exit;
    }

    $nama_dokumen  = trim($_POST['nama_dokumen']);
    $deskripsi     = trim($_POST['deskripsi']);
    $nama_pemilik  = trim($_POST['nama_pemilik']);
    $id_kategori   = (int)$_POST['id_kategori'];
    $status_dok    = $_POST['status_dok'];
    $diunggah_oleh = $_SESSION['nama'] ?? 'system';

    if ($no_dokumen === '' || $nama_dokumen === '' || $nama_pemilik === '') {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Field wajib tidak boleh kosong'
        ];
        header("Location: tambah_dokumen.php");
        exit;
    }

    $cek = mysqli_query($conn, "
        SELECT id_dokumen FROM dokumen
        WHERE no_dokumen = '".mysqli_real_escape_string($conn, $no_dokumen)."'
    ");
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => '<i class="fa fa-exclamation-circle mr-1"></i> No Dokumen sudah digunakan'
        ];
        header("Location: tambah_dokumen.php");
        exit;
    }

    if (!empty($_FILES['file_dokumen']['name'][0])) {

        $total_size = array_sum($_FILES['file_dokumen']['size']);
        $max_total  = 10 * 1024 * 1024; // 10MB TOTAL

        if ($total_size > $max_total) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Total ukuran semua file maksimal 10MB'
            ];
            header("Location: tambah_dokumen.php");
            exit;
        }
    }

    mysqli_query($conn, "
        INSERT INTO dokumen
        (no_dokumen, nama_dokumen, deskripsi, nama_pemilik,
         id_kategori, jumlah_file, status_dok, diunggah_oleh)
        VALUES (
            '".mysqli_real_escape_string($conn, $no_dokumen)."',
            '".mysqli_real_escape_string($conn, $nama_dokumen)."',
            '".mysqli_real_escape_string($conn, $deskripsi)."',
            '".mysqli_real_escape_string($conn, $nama_pemilik)."',
            $id_kategori,
            0,
            '$status_dok',
            '".mysqli_real_escape_string($conn, $diunggah_oleh)."'
        )
    ");

    $id_dokumen = mysqli_insert_id($conn);

    $upload_dir = "../assets/uploads/dokumen/$id_dokumen/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_ext  = ['pdf','doc','docx','jpg','jpeg','png'];
    $allowed_mime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $jumlah_file = 0;

    if (!empty($_FILES['file_dokumen']['name'][0])) {
        foreach ($_FILES['file_dokumen']['name'] as $i => $name) {

            if ($_FILES['file_dokumen']['error'][$i] !== 0) continue;

            $tmp  = $_FILES['file_dokumen']['tmp_name'][$i];
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = finfo_file($finfo, $tmp);

            if (!in_array($ext, $allowed_ext)) continue;
            if (!in_array($mime, $allowed_mime)) continue;

            $original = pathinfo($name, PATHINFO_FILENAME);
            $original = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $original);
            $original = trim($original);
            $original = str_replace(' ', '_', $original);

            $new_name = $original . '_' . time() . '.' . $ext;

            if (move_uploaded_file($tmp, $upload_dir . $new_name)) {
                $jumlah_file++;
            }
        }
    }

    finfo_close($finfo);

    mysqli_query($conn, "
        UPDATE dokumen
        SET jumlah_file = $jumlah_file
        WHERE id_dokumen = $id_dokumen
    ");

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Dokumen berhasil ditambahkan'
    ];

    header("Location: dokumen.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Dokumen | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

<h4 class="mb-3">Tambah Dokumen</h4>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
        <?= $_SESSION['flash']['message']; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php unset($_SESSION['flash']); endif; ?>

<div class="card">
<div class="card-body">

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

<div class="form-group">
    <label>Upload File (boleh lebih dari satu)</label>
    <input type="file"
           name="file_dokumen[]"
           class="form-control"
           multiple
           accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
    <small class="text-muted">
        Maksimal 10MB. PDF, Word, JPG, PNG.
    </small>
</div>

<div class="form-group">
    <label>No Dokumen</label>
    <input type="text"
           name="no_dokumen"
           class="form-control"
           pattern="[0-9\/\-]+"
           oninput="this.value = this.value.replace(/[^0-9\/\-]/g, '')"
           placeholder="Hanya angka, tanda - dan /">
</div>

<div class="form-group">
    <label>Nama Dokumen</label>
    <input type="text" name="nama_dokumen" class="form-control" required>
</div>

<div class="form-group">
    <label>Nama Pemilik</label>
    <input type="text" name="nama_pemilik" class="form-control" required>
</div>

<div class="form-group">
    <label>Kategori</label>
    <select name="id_kategori" class="form-control" required>
        <option value="">-- Pilih Kategori --</option>
        <?php while ($k = mysqli_fetch_assoc($qKategori)): ?>
            <option value="<?= $k['id_kategori']; ?>">
                <?= htmlspecialchars($k['nama_kategori']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Status Dokumen</label>
    <select name="status_dok" class="form-control">
        <option value="berlaku">Berlaku</option>
        <option value="kadaluarsa">Kadaluarsa</option>
    </select>
</div>

<div class="form-group">
    <label>Deskripsi</label>
    <textarea name="deskripsi" class="form-control" rows="3"></textarea>
</div>

<div class="text-right mt-3">
    <a href="dokumen.php" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Kembali
    </a>
    <button type="submit" name="simpan" class="btn btn-primary btn-sm">
        <i class="fa fa-save"></i> Simpan
    </button>
</div>

</form>

</div>
</div>

</div>
</div>

<?php include "../templates/footer.php"; ?>
<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
