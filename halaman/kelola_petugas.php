<?php
session_start();
require_once "../config/database.php";
require_once "../config/auth_guard.php";
require_once "../config/admin_guard.php";
require_once "../config/maintenance_guard.php";
require_once "../config/settings.php";
require_once "../config/token.php";

$active    = 'admin-panel';
$subactive = 'petugas';

$query = mysqli_query($conn, "
    SELECT id_user, nama, username, no_petugas, shift, status
    FROM users
    WHERE role = 'petugas'
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Petugas | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include "../templates/topbar.php"; ?>

<div class="main-content">
<div class="container mt-4">

    <div class="row mb-3">
        <div class="col-12 text-center">
            <h4 class="mb-0">
            <i class="fa fa-id-card-o"></i> Kelola Petugas</h4>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type']; ?> alert-dismissible fade show">
            <i class="fa <?= $_SESSION['flash']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            <?= $_SESSION['flash']['message']; ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="tambah_petugas.php" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> Tambah Petugas
        </a>
    </div>

    <div class="card">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-light text-center">
                    <tr>
                        <th width="4%">No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>No Petugas</th>
                        <th>Shift</th>
                        <th>Status</th>
                        <th width="18%">Aksi</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama']); ?></td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['no_petugas']); ?></td>
                        <td class="text-center text-capitalize"><?= htmlspecialchars($row['shift']); ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= $row['status'] === 'aktif' ? 'success' : 'danger'; ?>">
                                <?= ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="edit_petugas.php?id=<?= $row['id_user']; ?>" class="btn btn-warning btn-sm">
                                <i class="fa fa-edit"></i>
                            </a>

                            <?php if ($row['status'] === 'aktif'): ?>
                            <form method="POST"
                                action="nonaktif_petugas.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $row['id_user']; ?>">
                                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Nonaktifkan petugas ini secara permanen?')">
                                        <i class="fa fa-ban"></i>
                                    </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            Belum ada data petugas
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>
</div>

<?php include "../templates/footer.php"; ?>
<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
