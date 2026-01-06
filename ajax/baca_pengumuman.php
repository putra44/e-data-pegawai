<?php
session_start();
require_once "../config/database.php";

$id = $_GET['id'] ?? null;
$id_user = $_SESSION['id_user'] ?? null;

if ($id && $id_user) {
    mysqli_query($conn, "
        INSERT IGNORE INTO pengumuman_read (id_pengumuman, id_user)
        VALUES ('$id', '$id_user')
    ");
}
