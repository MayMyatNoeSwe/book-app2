<?php
// generate_cover.php - Dynamic book cover generator with solid colors
// Usage: generate_cover.php?color=2e8a40&w=400&h=600

// Get parameters
$color = $_GET['color'] ?? '2e8a40';
$width = isset($_GET['w']) ? min(800, max(100, (int)$_GET['w'])) : 400;
$height = isset($_GET['h']) ? min(1200, max(150, (int)$_GET['h'])) : 600;

// Convert hex color to RGB
$r = hexdec(substr($color, 0, 2));
$g = hexdec(substr($color, 2, 2));
$b = hexdec(substr($color, 4, 2));

// Create image
$image = imagecreatetruecolor($width, $height);

// Allocate colors
$bgColor = imagecolorallocate($image, $r, $g, $b);
$textColor = imagecolorallocate($image, 255, 255, 255);
$borderColor = imagecolorallocate($image, max(0, $r-30), max(0, $g-30), max(0, $b-30));
$accentColor = imagecolorallocate($image, min(255, $r+30), min(255, $g+30), min(255, $b+30));

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// Draw decorative border
imagerectangle($image, 10, 10, $width-11, $height-11, $borderColor);
imagerectangle($image, 12, 12, $width-13, $height-13, $accentColor);

// Draw book icon in center - adjusted to fit properly
$iconWidth = (int)(min($width, $height) / 3.5);
$iconHeight = (int)($iconWidth * 1.3); // Book height is 1.3x width
$iconX = (int)(($width - $iconWidth) / 2);
$iconY = (int)(($height - $iconHeight) / 2);

// Ensure icon fits within bounds
if ($iconY + $iconHeight > $height - 20) {
    $iconHeight = $height - $iconY - 20;
}

// Book shape (main rectangle)
imagefilledrectangle($image, $iconX, $iconY, $iconX + $iconWidth, $iconY + $iconHeight, $textColor);

// Book spine line (left side)
$spineX = (int)($iconX + ($iconWidth * 0.15));
imagefilledrectangle($image, $spineX, $iconY, $spineX + 3, $iconY + $iconHeight, $bgColor);

// Book pages lines (horizontal lines)
$numLines = 3;
for ($i = 1; $i <= $numLines; $i++) {
    $lineY = (int)($iconY + ($iconHeight * $i / ($numLines + 1)));
    $lineStartX = (int)($iconX + ($iconWidth * 0.2));
    $lineEndX = (int)($iconX + ($iconWidth * 0.85));
    imageline($image, $lineStartX, $lineY, $lineEndX, $lineY, $accentColor);
}

// Output image
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day
imagepng($image, null, 9);
imagedestroy($image);
