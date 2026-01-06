<?php
session_start();
require_once "../config/database.php";
require_once "../config/settings.php";

// Jika sudah login, jangan balik ke login
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: ../halaman/dashboard.php");
    exit;
}

// PROSES LOGIN
// PROSES LOGIN
if (isset($_POST['login'])) {

    // =========================
    // VALIDASI CAPTCHA
    // =========================
    $captcha_input   = trim($_POST['captcha'] ?? '');
    $captcha_session = $_SESSION['captcha_code'] ?? '';

    if ($captcha_input === '' || $captcha_input !== $captcha_session) {

        // ❗ PAKAI FLASH MESSAGE
        $_SESSION['error'] = "Kode keamanan (captcha) salah!";
        unset($_SESSION['captcha_code']); // reset captcha

        header("Location: login.php");
        exit;

    } else {

        // captcha benar → hapus agar tidak bisa dipakai ulang
        unset($_SESSION['captcha_code']);

        // =========================
        // VALIDASI LOGIN
        // =========================
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // pastikan username selalu pakai @
        if ($username !== '' && $username[0] !== '@') {
            $username = '@' . $username;
        }

        $safeUsername = mysqli_real_escape_string($conn, $username);

        $query = mysqli_query($conn, "
            SELECT * FROM users
            WHERE username = '$safeUsername'
            AND status = 'aktif'
            LIMIT 1
        ");

        if (mysqli_num_rows($query) === 1) {

            $user = mysqli_fetch_assoc($query);

            if (password_verify($password, $user['password'])) {

                // update last login & reset force logout
                mysqli_query($conn, "
                    UPDATE users 
                    SET last_login = NOW(), force_logout = 0 
                    WHERE id_user = '{$user['id_user']}'
                ");

                $_SESSION['login']    = true;
                $_SESSION['id_user']  = $user['id_user'];
                $_SESSION['welcome']  = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['foto']     = $user['foto'];

                header("Location: ../halaman/dashboard.php");
                exit;

            } else {

                // ❗ FLASH MESSAGE
                $_SESSION['error'] = "Password salah!";
                header("Location: login.php");
                exit;
            }

        } else {

            // ❗ FLASH MESSAGE
            $_SESSION['error'] = "Username tidak ditemukan atau akun nonaktif!";
            header("Location: login.php");
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login | e-DATA Pegawai</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">

    <!-- Font Awesome 4 -->
    <link rel="stylesheet" href="../assets/font-awesome-4.7.0/css/font-awesome.min.css">

    <style>
/* ===============================
   RESET DASAR
================================ */
* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    padding: 0;
    height: 100%;
}

/* ===============================
   BODY (LOGIN PAGE)
================================ */
body {
    font-family: "Segoe UI", sans-serif;
    background: #f8f9fa;
}

/* ===============================
   LOGIN FULLSCREEN
================================ */
.login-wrapper {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;

    background:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url("../assets/background/bg.jpg") center center no-repeat;
    background-size: cover;
}

/* ===============================
   LOGIN CARD
================================ */
.login-card {
    width: 100%;
    max-width: 420px;
    border: none;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(6px);
    box-shadow: 0 12px 40px rgba(0,0,0,.25);
}

.login-card .card-body {
    padding: 26px; /* lebih pendek */
}

/* ===============================
   HEADER LOGIN
================================ */
.login-card img {
    max-height: 90px; /* logo diperkecil */
}

/* ===============================
   LOGO LOGIN (NORMAL / ASLI)
================================ */
.login-logo {
    max-width: 140px;
    max-height: 120px;
    width: auto;
    height: auto;

    object-fit: contain; /* ⬅️ PENTING: logo tidak terpotong */
    display: block;
    margin: 0 auto 14px auto;
}

.login-title {
    font-weight: 700;
    margin-top: 6px;
    margin-bottom: 4px;
}

.login-subtitle {
    font-size: 13px;
    color: #6c757d;
}

/* ===============================
   FORM & INPUT
================================ */
.form-group {
    margin-bottom: 14px;
}

.form-control {
    height: 42px;
    border-radius: 8px;
}

.form-control:focus {
    box-shadow: 0 0 0 0.15rem rgba(13,110,253,.25);
    border-color: #0d6efd;
}

.input-group-text {
    background: #fff;
    border-radius: 8px 0 0 8px;
}

/* ===============================
   BUTTON LOGIN
================================ */
.btn-login {
    height: 42px;
    font-weight: 600;
    border-radius: 8px;
    letter-spacing: 0.5px;
}

.btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}

/* ===============================
   FOOTER LOGIN (DALAM CARD)
================================ */
hr.my-4 {
    margin-top: 16px;
    margin-bottom: 12px;
}

small.text-muted {
    font-size: 12px;
}

/* ===============================
   RESPONSIVE (LAYAR PENDEK)
================================ */
@media (max-height: 700px) {
    .login-card {
        transform: scale(0.95);
    }
}

    </style>
</head>

<body>
<div class="login-bg">
    <div class="container-fluid login-wrapper d-flex align-items-center justify-content-center">
        <div class="card login-card shadow-sm">
            <div class="card-body">

                <!-- HEADER -->
                <div class="text-center mb-4">

                    <?php if (!empty($settings['app_logo'])) : ?>
                        <img src="../assets/uploads/logo/<?= htmlspecialchars($settings['app_logo']); ?>"
                        alt="Logo" class="login-logo">
                    <?php else : ?>
                        <i class="fa fa-id-card fa-3x text-primary mb-3"></i>
                    <?php endif; ?>

                    <h4 class="login-title">
                        <?= htmlspecialchars($settings['app_name'] ?? 'e-DATA Pegawai'); ?>
                    </h4>

                    <div class="login-subtitle">
                        <?= htmlspecialchars($settings['app_description'] ?? 'Sistem Informasi Pegawai'); ?>
                    </div>
                </div>

                <!-- LOGOUT SUCCESS MESSAGE -->
                <?php if (isset($_SESSION['logout_success'])) : ?>
                    <div class="alert alert-success text-center">
                        <i class="fa fa-check-circle"></i>
                        Anda berhasil keluar.
                    </div>
                    <?php unset($_SESSION['logout_success']); ?>
                <?php endif; ?>


                <!-- ERROR MESSAGE -->
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger text-center">
                        <?= $error; ?>
                    </div>
                <?php endif; ?>

                <!-- FLASH ERROR MESSAGE -->
                    <?php if (isset($_SESSION['error'])) : ?>
                        <div class="alert alert-danger text-center">
                            <i class="fa fa-times-circle"></i>
                            <?= $_SESSION['error']; ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                <!-- FORM LOGIN -->
                <form method="POST" autocomplete="off">

                    <!-- USERNAME -->
                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fa fa-user"></i>
                                </span>
                            </div>
                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                required
                            >
                        </div>
                    </div>

                    <!-- PASSWORD -->
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fa fa-lock"></i>
                                </span>
                            </div>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Masukkan password"
                                required
                            >
                        </div>
                    </div>

                    <!-- CAPTCHA -->
                    <div class="form-group">
                        <label>Kode Keamanan</label>
                        <div class="d-flex align-items-center mb-2">
                            <img
                                src="../helpers/captcha.php"
                                alt="Captcha"
                                style="border-radius:6px; height:40px;"
                                id="captcha-img"
                            >
                            <button type="button"
                                    class="btn btn-light btn-sm ml-2"
                                    onclick="refreshCaptcha()">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>

                        <input
                            type="text"
                            name="captcha"
                            class="form-control text-center"
                            placeholder="Masukkan kode di atas"
                            maxlength="5"
                            required
                        >
                    </div>

                    <!-- BUTTON -->
                    <button type="submit" name="login" class="btn btn-primary btn-block btn-login">
                        <i class="fa fa-sign-in mr-1"></i> LOGIN
                    </button>
                </form>

                <!-- FOOTER LOGIN -->
                <hr class="my-4">

                <small class="text-muted d-block text-center">
                    © <?= date('Y'); ?>
                    <b><?= htmlspecialchars($settings['app_name'] ?? 'e-DATA Pegawai'); ?></b>

                    <?php if (!empty($settings['institution_name'])) : ?>
                        – <?= htmlspecialchars($settings['institution_name']); ?>
                    <?php endif; ?>
                </small>

                <small class="text-muted d-block text-center mt-1">
                    <?= htmlspecialchars($settings['footer_text'] ?? 'All Right Reverse'); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="../assets/bootstrap/js/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.min.js"></script>
<script>
function refreshCaptcha() {
    document.getElementById('captcha-img').src =
        '../helpers/captcha.php?' + Date.now();
}
</script>

</body>
</html>