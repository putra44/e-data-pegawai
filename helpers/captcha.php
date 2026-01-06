<?php
session_start();

$length = 5;

$captcha_code = '';
for ($i = 0; $i < $length; $i++) {
    $captcha_code .= rand(0, 9);
}

$_SESSION['captcha_code'] = $captcha_code;

$width  = 130;
$height = 40;

$image = imagecreatetruecolor($width, $height);

$bg_color   = imagecolorallocate($image, 245, 247, 250);
$text_color = imagecolorallocate($image, 40, 40, 40);
$noise_color = imagecolorallocate($image, 180, 180, 180);

imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

for ($i = 0; $i < 5; $i++) {
    imageline(
        $image,
        rand(0, $width),
        rand(0, $height),
        rand(0, $width),
        rand(0, $height),
        $noise_color
    );
}

for ($i = 0; $i < 200; $i++) {
    imagesetpixel(
        $image,
        rand(0, $width),
        rand(0, $height),
        $noise_color
    );
}

$font_size = 5;
$x = ($width - imagefontwidth($font_size) * strlen($captcha_code)) / 2;
$y = ($height - imagefontheight($font_size)) / 2;

imagestring($image, $font_size, $x, $y, $captcha_code, $text_color);

header("Content-Type: image/png");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

imagepng($image);
imagedestroy($image);
exit;
