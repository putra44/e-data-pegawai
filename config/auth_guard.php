<?php
// auth_guard.php
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_SESSION['id_user'];

$cek = mysqli_query($conn, "
    SELECT force_logout, status 
    FROM users 
    WHERE id_user='$id'
    LIMIT 1
");

if ($cek && mysqli_num_rows($cek) === 1) {
    $user = mysqli_fetch_assoc($cek);

    // force logout ATAU akun nonaktif
    if ($user['force_logout'] == 1 || $user['status'] !== 'aktif') {
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }
}
?>