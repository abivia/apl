<?php
/**
 * Abivia PHP5 Library
 * 
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Complex.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A very complex CAPTCHA generator.
 *
 * @package AP5L
 * @subpackage Forms
 */ 
class AP5L_Forms_UserVerifier_Complex {
    //var $_bands = array(24, 40, 40, 40, 40, 24, 24, 24);
    var $_bands = array(40, 40, 24, 24, 24, 24, 40, 40);
    var $_bandB;
    var $_bandE;
    var $_charRef;
    var $_charX;
    var $_charY;
    var $_inBand;
    var $_jitter;
    var $_lockB;
    var $_lockE;
    
    function __construct() {
        $this -> _bandB = array();
        $this -> _bandE = array();
        $base = 0;
        foreach ($this -> _bands as $step) {
            $this -> _bandB[] = $base;
            $base += $step;
            if ($base > 256) $base = 256;
            $this -> _bandE[] = $base - 1;
        }
        $this -> _charRef = imagecreatefrompng(dirname(__FILE__) . '/chars.png');
        $this -> _charX = (int) (imagesx($this -> _charRef) / 62);
        $this -> _charY = imagesy($this -> _charRef);
        // Set jitter to 25% of the average char size
        $this -> _jitter = (int)(($this -> _charX + $this -> _charY) / 8);
        if ($this -> _jitter < 4) $this -> _jitter = 4;
    }
    
    function AP5L_Forms_UserVerifier() {
        $this -> __construct();
    }
    
    function _create($len) {
        $this -> _pickAnswer($len);
        //
        // Pick RGB bands for this image
        //
        $this -> _inBand = array();
        $this -> _lockB = array();
        $this -> _lockE = array();
        for ($indx = 0; $indx < 3; ++$indx) {
            $band = rand(-2, 1);
            if ($band < 0) {
                $band += count($this -> _bands);
                $this -> _lockB[$indx] = $this -> _bandB[$band - 1];
                $this -> _lockE[$indx] = 255;
            } else {
                $this -> _lockB[$indx] = 0;
                $this -> _lockE[$indx] = $this -> _bandE[$band + 1];
            }
            $this -> _inBand[$indx] = $band;
        }
//        echo 'inband<pre>'; print_r($this -> _inBand); echo '</pre>';
//        echo 'lockB<pre>'; print_r($this -> _lockB); echo '</pre>';
//        echo 'lockE<pre>'; print_r($this -> _lockE); echo '</pre>';
//        exit();
        //
        // Calculate the size and create the image
        //
        $this -> _marginX = ($this -> _marginX < 2) ? 2.0 : $this -> _marginX;
        $px = $this -> _scale * ceil($len + 2 * $this -> _marginX) * $this -> _charX;
        $this -> _marginY = ($this -> _marginY < 1) ? 1.0 : $this -> _marginY;
        $py = $this -> _scale * ceil((2 * $this -> _marginY + 1) * $this -> _charY);
        $this -> _image = @imagecreatetruecolor($px, $py);
        if (! $this -> _image) return false;
        //
        // Pick background and foreground colours
        //
        $inPal = array();
        $outPal = array();
        for ($indx = 0; $indx < 128; ++$indx) {
            $outPal[$indx] = $this -> _pickOut();
        }
        for ($indx = 0; $indx < 64; ++$indx) {
            $inPal[$indx] = $this -> _pickIn();
        }
//        echo 'picked';
//        exit();
        //
        // Write a random background
        //
        if (false) {
        for ($x = 0; $x < $px; $x += 2) {
            for ($y = 0; $y < $py; $y += 2) {
                $c = $outPal[rand(0, 191)];
                imagesetpixel($this -> _image, $x, $y, $c);
                imagesetpixel($this -> _image, $x, $y + 1, $c);
                imagesetpixel($this -> _image, $x + 1, $y, $c);
                imagesetpixel($this -> _image, $x + 1, $y + 1, $c);
            }
        }
        }
        //
        // Write random characters in the background palette
        //
        for ($x = 0; false && $x < $px; $x += $this -> _charX) {
            for ($y = 0; $y < $py; $y += $this -> _charY) {
                $c = $outPal[rand(0, count($outPal) - 1)];
                $this -> _writeChar($x, $y, rand(0, 61), array($c));
            }
        }
        //
        // Write the auth code in foreground colours
        //
        $addX = 0;
        $gapX = array();
        for ($indx = 0; $indx < $len; ++$indx) {
            $gapX[$indx] = 0; //rand(0,1);
            if ($gapX[$indx]) ++$addX;
        }
        $sx = $this -> _scale * $this -> _marginX * $this -> _charX;
        $sy = $this -> _scale * $this -> _marginY * $this -> _charY;
        $auth = array();
        $yBias = 0;
        $pal = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        for ($indx = 0; $indx < count($this -> _answerPos); ++$indx) {
            $pal[0] = $outPal[rand(0, count($outPal) - 1)];
            $pal[1] = $outPal[rand(0, count($outPal) - 1)];
            for ($pi = 2; $pi < count($pal); ++$pi) {
                $pal[$pi] = $inPal[rand(0, count($inPal) - 1)];
            }
            $yBias += rand(-1, 1);
            if ($yBias > 1) $yBias = 1;
            if ($yBias < -1) $yBias = -1;
            $this -> _writeChar($sx, $sy + $yBias * $this -> _charY, $this -> _answerPos[$indx], $pal);
            $sx += (2 + $gapX[$indx]) * $this -> _charX;
        }
        return true;
    }
    
    function _pickIn() {
        $band = $this -> _inBand[0];
        $r = rand($this -> _bandB[$band], $this -> _bandE[$band]);
        $band = $this -> _inBand[1];
        $g = rand($this -> _bandB[$band], $this -> _bandE[$band]);
        $band = $this -> _inBand[2];
        $b = rand($this -> _bandB[$band], $this -> _bandE[$band]);
        $c = imagecolorexact($this -> _image, $r, $g, $b);
        if ($c == -1) {
            $c = imagecolorallocate($this -> _image, $r, $g, $b);
        } else {
            $c = $this -> _pickIn();
        }
        return $c;
    }
    
    function _pickOut() {
        do {
            $r = rand(0, 255);
        } while ($r >= $this -> _lockB[0] && $r <= $this -> _lockE[0]);
        do {
            $g = rand(0, 255);
        } while ($g >= $this -> _lockB[1] && $g <= $this -> _lockE[1]);
        do {
            $b = rand(0, 255);
        } while ($b >= $this -> _lockB[2] && $b <= $this -> _lockE[2]);
        $c = imagecolorexact($this -> _image, $r, $g, $b);
        if ($c == -1) {
            $c = imagecolorallocate($this -> _image, $r, $g, $b);
        } else {
            $c = $this -> _pickOut();
        }
        return $c;
    }
    
    function _writeChar($x, $y, $char, &$colors) {
        $x +=  rand(0, $this -> _jitter);
        $y +=  rand(0, $this -> _jitter);
        parent::_writeChar($x, $y, $char, $colors);
    }
    
}
