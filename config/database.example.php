<?php
$conn = mysqli_connect(
    "localhost",
    "db_user",
    "db_password",
    "db_name"
);

if (!$conn) {
    die("Database connection failed");
}
