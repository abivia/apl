<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Basic.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A reasonably simple CAPTCHA generator.
 *
 * @package AP5L
 * @subpackage Forms
 */ 
class AP5L_Forms_UserVerifier_Basic extends AP5L_Forms_UserVerifier {

    function __construct($tempPath = null) {
        parent::__construct($tempPath);
    }

    function _create($len) {
        $this -> _pickAnswer($len);
        //
        // Calculate the size and create the image
        //
        $this -> _marginX = ($this -> _marginX < 0.5) ? 0.5 : $this -> _marginX;
        // scale X to 75% since the characters are near overlapped.
        $px = $this -> _scale * ceil((0.75 * $len + 2 * $this -> _marginX) * $this -> _charX);
        $this -> _marginY = ($this -> _marginY < 0.5) ? 0.5 : $this -> _marginY;
        $py = $this -> _scale * ceil((2 * $this -> _marginY + 1) * $this -> _charY);
        $this -> _image = @imagecreate($px, $py);
        if (! $this -> _image) return false;
        //
        // Write a black background
        //
        $black = imagecolorallocate($this -> _image, 0, 0, 0);
        //
        // Write the auth code in white
        //
        $white = array(imagecolorallocate($this -> _image, 255, 255, 255));
        $addX = 0;
        $auth = array();
        $yBias = 0;
        $dx = (3 * $this -> _charX) / 4;
        $sx = $dx;
        $sy = $this -> _charY / 4;
        $dy = $this -> _charY / 2;
        for ($indx = 0; $indx < count($this -> _answerPos); ++$indx) {
            //
            // Write character to the image
            //
            $this -> _writeChar($sx, $sy + rand(0, $dy), $this -> _answerPos[$indx], $white);
            $sx += $dx;
        }
        return true;
    }

}
