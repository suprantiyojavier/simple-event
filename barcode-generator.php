<?php
if (!isset($_GET['code'])) {
    die('No code provided.');
}

$code = $_GET['code'];
$width = strlen($code) * 10 + 20;
$height = 60;

header('Content-Type: image/png');

$image = imagecreate($width, $height);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Garis dasar barcode
$x = 10;
for ($i = 0; $i < strlen($code); $i++) {
    $digit = ord($code[$i]);
    for ($j = 0; $j < 7; $j++) {
        if (($digit >> $j) & 1) {
            imagefilledrectangle($image, $x, 10, $x + 2, 50, $black);
        }
        $x += 3;
    }
}

// Tambahkan teks di bawah barcode
imagestring($image, 3, 10, 52, $code, $black);

imagepng($image);
imagedestroy($image);
