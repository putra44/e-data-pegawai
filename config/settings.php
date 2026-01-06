<?php

$settings = [];

$q = mysqli_query($conn, "SELECT * FROM settings");
while ($row = mysqli_fetch_assoc($q)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

?>