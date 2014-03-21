<?php
/**
 * Scratchpad for debugging.

 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ColorSpaceDebug.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('../ColorSpace.php');

$cs = new ColorSpace();

$image = imagecreatetruecolor(500, 500);
//
// Set the first colour to black
//
$bkgCol = imagecolorallocate($image, 0, 0, 0);

//
// Write 50 10x10 squares across the top in a range of hues,
// drop a row and decrease the value
//
$cs -> setSaturation(1.0);
$steps = 100;
$stepSize = floor(500 / $steps);
for ($baseRow = 0; $baseRow < $steps; $baseRow++) {
    $bri = 1.0 - $baseRow / $steps;
    //echo 'bri=' . $bri . '<br/>';
    $cs -> setBright($bri);
    $ro = $baseRow * $stepSize;
    for ($baseCol = 0; $baseCol < $steps; $baseCol++) {
        $cs -> setHue($baseCol / $steps);
        //$cs -> setHue($baseCol / $steps);
        $co = $baseCol * $stepSize;
        $color = $cs -> imageColorAllocate($image);
        if (false && $ro == 0) {
            print_r($cs);
            echo '<br/>r=' . $baseRow . ' c=' . $baseCol
                . ' rgb=(' . $cs -> getRedInt()
                . ', ' . $cs -> getGreenInt()
                . ', ' . $cs -> getBlueInt() . ')'
                . ' color=' . $color . '<br/>';
        }
        for ($r = 0; $r < $stepSize; $r++) {
            for ($c = 0; $c < $stepSize; $c++) {
                imagesetpixel($image, $co + $c, $ro + $r, $color);
            }
        }
    }
}
$cs -> setAlpha(0.75);
$cs -> setBright(0.65);
$cs -> setSaturation(0.65);
$cs -> setHue(0.7);
$cs -> setRgb(1.0, 1.0, 1.0);
$color = $cs -> imageColorAllocate($image);
imagefttext($image, 50, 15, 50, 250, $color, 'frkgoth', 'Demo / Sample');

// Date in the past
header("Expires: Thu, 28 Aug 1997 05:00:00 GMT");

// always modified
$timestamp = gmdate("D, d M Y H:i:s");
header("Last-Modified: " . $timestamp . " GMT");

// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// HTTP/1.0
header("Pragma: no-cache");

// dump out the image
header("Content-type: image/png");
imagepng($image);
imagedestroy($image);
