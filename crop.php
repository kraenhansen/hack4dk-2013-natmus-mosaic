<?php
require 'lib/hsv2rgb.php';
$id = intval($_GET['id']);
$size = intval($_GET['size']);
$url = 'http://samlinger.natmus.dk/CIP/preview/image/DNT/' . $id . '?maxsize=1000';

function getImage($url) {
	$file = './cache/'.md5($url).'.jpg';
	if(!file_exists($file)) {
		// Download and cache ..
		$content = file_get_contents($url);
		$fh = fopen($file, 'w');
		fwrite($fh, $content);
		fclose($fh);
	}
	return imagecreatefromjpeg($file);
}

$img = getImage($url);

$cols = 100;
$rows = 100;

const BIAS = 0.05;

$width = imagesx($img);
$height = imagesy($img);

$vertical_average = array();
$horizontal_average = array();

function getX($c) {
	global $width, $cols;
	return floor($width / ($cols + 2) * ($c + 1));
}
function getY($r) {
	global $height, $rows;
	return floor($height / ($rows + 2) * ($r + 1));
}


for($c = 0; $c < $cols; $c++) {
	$vertical_average[$c] = 0;
	for($r = 0; $r < $rows; $r++) {
		$x = getX($c);
		$y = getY($r);
		
		$rgb = imagecolorat($img, $x, $y);
		$red = ($rgb >> 16) & 0xFF;
		$green = ($rgb >> 8) & 0xFF;
		$blue = $rgb & 0xFF;
		
		$gray = floor(($red + $green + $blue) / 3);
		
		$vertical_average[$c] += $gray;
	}
	$vertical_average[$c] /= $cols;
}

// Focussing on the horizontal bounds.
$prev_average = $vertical_average[0];
$max_diff_value = 0;
$max_diff_index = 0;
$min_diff_value = 0;
$min_diff_index = 0;
for($c = 0; $c < $cols; $c++) {
	$diff = $prev_average - $vertical_average[$c];
	if($max_diff_value < $diff) {
		$max_diff_value = $diff;
		$max_diff_index = $c;
	}
	if($min_diff_value > $diff) {
		$min_diff_value = $diff;
		$min_diff_index = $c;
	}
	$prev_average = $vertical_average[$c];
}
$minx = getX($max_diff_index);
$maxx = getX($min_diff_index);

//imageline($img, $minx, 0, $minx, $height, 1);
//imageline($img, $maxx, 0, $maxx, $height, 1);

// Now look at the average color horizontal inside and outside the vertical bounds.
for($r = 0; $r < $rows; $r++) {
	$insides = 0;
	$outsides = 0;
	$horizontal_average_inside[$r] = 0;
	$horizontal_average_outside[$r] = 0;
	for($c = 0; $c < $cols; $c++) {
		$x = getX($c);
		$y = getY($r);

		$rgb = imagecolorat($img, $x, $y);
		$red = ($rgb >> 16) & 0xFF;
		$green = ($rgb >> 8) & 0xFF;
		$blue = $rgb & 0xFF;
		
		$gray = floor(($red + $green + $blue) / 3);
		
		if($x > $minx && $x < $maxx) {
			$horizontal_average_inside[$r] += $gray;
			$insides++;
		} else {
			$horizontal_average_outside[$r] += $gray;
			$outsides++;
		}
	}
	if($insides > 0) {
		$horizontal_average_inside[$r] /= $insides;
	}
	if($outsides > 0) {
		$horizontal_average_outside[$r] /= $outsides;
	}
}

// Focussing on the horizontal bounds.
$prev_average = $horizontal_average[0];
$max_diff_value = 0;
$max_diff_index = 0;
$min_diff_value = 0;
$min_diff_index = 0;
for($r = 0; $r < $rows; $r++) {
	$horizontal_average = $horizontal_average_inside[$r] - $horizontal_average_outside[$r];
	$diff = $prev_average - $horizontal_average;
	if($max_diff_value < $diff) {
		$max_diff_value = $diff;
		$max_diff_index = $r;
	}
	if($min_diff_value > $diff) {
		$min_diff_value = $diff;
		$min_diff_index = $r;
	}
	$prev_average = $horizontal_average;
}
$miny = getY($max_diff_index);
$maxy = getY($min_diff_index);

//imageline($img, 0, $miny, $width, $miny, 1);
//imageline($img, 0, $maxy, $width, $maxy, 1);


$new_width = $maxx - $minx;
$horizontal_bias = round($new_width * BIAS);
$minx += $horizontal_bias;
$maxx -= $horizontal_bias;
$new_width -= 2 * $horizontal_bias;

$new_height = $maxy - $miny;
$vertical_bias = round($new_height * BIAS);
$miny += $vertical_bias;
$maxy -= $vertical_bias;
$new_height -= 2 * $vertical_bias;

if( ($new_width / $width < 0.2) || ($new_height / $height < 0.2) ) {
	// This is very small, just ignore
	$new_img = imagecreatetruecolor ( 1, 1 );
} else {
	
	$horizontal_ratio = $new_width / $size;
	$vertical_ratio = $new_height / $size;
	$src_size = min($horizontal_ratio, $vertical_ratio) * $size;
	
	$new_img = imagecreatetruecolor ( $size, $size );
	imagecopyresized($new_img, $img, 0, 0, $minx, $miny, $size, $size, $src_size, $src_size);
	list($r, $g, $b) = HSVtoRGB(array(mt_rand() / mt_getrandmax(), 1.0, 0.5));
	imagefilter($new_img, IMG_FILTER_COLORIZE, floor($r*255), floor($g*255), floor($b*255), 100);
}

header('Content-Type: image/jpeg');
imagejpeg($new_img);
//imagejpeg($img);
imagedestroy($new_img);
?>
