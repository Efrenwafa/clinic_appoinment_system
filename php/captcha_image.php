<?php
/**
 * PHP GD Image CAPTCHA Generator
 * IT 107 - Information Assurance and Security 1
 * Implements Handout Section 7.1 Option 2
 */

require_once __DIR__ . '/security.php';
start_secure_session();

// Prevent caching of CAPTCHA image
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Generate 5-6 unambiguous random alphanumeric characters
$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
$length = 6;
$code = '';
$maxIndex = strlen($chars) - 1;

for ($i = 0; $i < $length; $i++) {
    $code .= $chars[random_int(0, $maxIndex)];
}

// Store code in session for server-side verification
$_SESSION['captcha_code'] = $code;

// Image dimensions
$width = 150;
$height = 45;
$image = imagecreatetruecolor($width, $height);

// Colors
$bgColor = imagecolorallocate($image, 245, 247, 250);
imagefill($image, 0, 0, $bgColor);

// Draw random noise lines
for ($i = 0; $i < 6; $i++) {
    $lineColor = imagecolorallocate($image, random_int(150, 210), random_int(150, 210), random_int(180, 220));
    imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
}

// Draw random noise dots
for ($i = 0; $i < 150; $i++) {
    $dotColor = imagecolorallocate($image, random_int(160, 220), random_int(160, 220), random_int(160, 220));
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dotColor);
}

// Draw the text
$textColor = imagecolorallocate($image, 26, 82, 118); // Deep navy primary color
$font = 5; // Built-in GD font 5 (large)
$charWidth = 22;
$startX = 12;

for ($i = 0; $i < $length; $i++) {
    $char = $code[$i];
    $y = random_int(10, 16);
    imagechar($image, $font, $startX + ($i * $charWidth), $y, $char, $textColor);
}

// Output PNG
imagepng($image);
imagedestroy($image);
