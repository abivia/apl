<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: UserVerifier.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 *
 * @todo complete phpdocs
 */

/**
 * Abstract base class for user verifiers. Override the _create method to
 * implement.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_UserVerifier {
    var $_answer;
    var $_answerPos;
    var $_charMask = 'l1O0';            // These (confusing) chars never used
    var $_charRef;
    var $_charX;
    var $_charY;
    var $_fontFile;                     // File containing character image masters
    var $_image;                        // The image resource
    var $_imageString;                  // String representation of image
    var $_marginX = 1;                  // Margin on left and right, in uits of char width
    var $_marginY = 0.5;                // Margin on top and bottom, in units of char height
    var $_scale;
    var $_tempFilePath;

    function __construct($tempPath = null) {
        //
        // Try to derive a place to save the image temp file
        //
        if (is_null($tempPath)) {
            $this -> _tempFilePath = session_save_path();
        } else {
            $this -> _tempFilePath = $tempPath;
        }
        if ($tfpl = strlen($this -> _tempFilePath)) {
            if ($this -> _tempFilePath{$tfpl - 1} != '/') {
                $this -> _tempFilePath .= '/';
            }
        }
        //
        // Read the character file and derive the character size
        //
        $this -> _fontFile = dirname(__FILE__) . '/userverifier/chars.png';
        $this -> _scale = 1;
    }

    /**
     * This method to be implemented by derived classes. Return true on success.
     */
    function _create($len) {
        return false;
    }

    /**
     * Map a character position index to the corresponding character.
     */
    function _mapChar($ord) {
        if ($ord >= 52) {
            return chr(ord('0') + $ord - 52);
        } else if ($ord >= 26) {
            return chr(ord('a') + $ord - 26);
        }
        return chr(ord('A') + $ord);
    }

    function _pickAnswer($len) {
        $this -> _answer = '';
        $this -> _answerPos = array();
        for ($indx = 0; $indx < $len; ++$indx) {
            //
            // Pick characters not in the mask
            //
            do {
                $cIndx = rand(0, 61);
                $cChar = $this -> _mapChar($cIndx);
            } while (strpos('.' . $this -> _charMask, $cChar));
            $this -> _answerPos[] = $cIndx;
            $this -> _answer .= $cChar;
        }
    }

    /**
     * Rather ugly conversion to string by writing to temporary file and reading
     * it back in. Ugly, but this is the only method that works.
     */
    function _toString() {
        //
        // Write the image to a file and load it as a string so it
        // can persist in a session variable
        //
        $tfid = $this -> _tempFilePath . session_id() . '_uvb.png';
        imagepng($this -> _image, $tfid);
        $this -> _imageString = file_get_contents($tfid);
        unlink($tfid);
    }

    /**
     * Simple bitwise copy of the specified character (by index) to the output
     * image.
     */
    function _writeChar($x, $y, $char, &$colors) {
        $cBase = $this -> _charX * $char;
        for ($cx = 0; $cx < $this -> _charX; ++$cx) {
            for ($cy = 0; $cy < $this -> _charY; ++$cy) {
                // If the master pixel is set, write a pixel
                if (imagecolorat($this -> _charRef, $cBase + $cx, $cy)) {
                    $wx = $this -> _scale * ($x + $cx);
                    $wy = $this -> _scale * ($y + $cy);
                    if (($wx + 1 < imagesx($this -> _image)) && ($wy + 1 < imagesy($this -> _image))) {
                        $c = $colors[rand(0, count($colors) - 1)];
                        for ($sx = 0; $sx < $this -> _scale; ++$sx) {
                            for ($sy = 0; $sy < $this -> _scale; ++$sy) {
                                imagesetpixel($this -> _image, $wx + $sx, $wy + $sy, $c);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Create a rendom verification image
     */
    function create($len) {
        if (! $this -> _charRef) {
            $this -> loadFont();
        }
        $this -> _create($len);
        $this -> _toString();
    }

    /**
     * Create a URI for the image embedded in the page
     */
    function embed() {
        return 'data:image/png;base64,' . base64_encode($this -> _imageString);
    }

    /**
     * Return the image via HTTP
     */
    function generate() {
        // Date in the past
        header('Expires: Thu, 28 Aug 1997 05:00:00 GMT');

        // always modified
        $timestamp = gmdate('D, d M Y H:i:s');
        header('Last-Modified: ' . $timestamp . ' GMT');

        // HTTP/1.1
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);

        // HTTP/1.0
        header('Pragma: no-cache');

        // dump out the image
        header('Content-type: image/png');
        echo $this -> _imageString;
    }

    /**
     * Return the correct answer for the current image.
     *
     * @return string The correct answer.
     */
    function getAnswer() {
        return $this -> _answer;
    }

    function loadFont() {
        $this -> _charRef = @imagecreatefrompng($this -> _fontFile);
        if (! $this -> _charRef) {
            // file didn't load; create a trivial placeholder
            $this -> _charRef = imagecreate(62 * 3, 3);
            $c = imagecolorallocate($this -> _charRef, 0, 0, 0);
            $w = imagecolorallocate($this -> _charRef, 255, 255, 255);
            for ($ind = 0; $ind < 62; ++$ind) {
                imagesetpixel($this -> _charRef, 3 * $ind + 1, 1, $w);
            }
        }
        $this -> _charX = (int) (imagesx($this -> _charRef) / 62);
        $this -> _charY = imagesy($this -> _charRef);
    }

    function setFontFile($fontPath) {
        $this -> _fontFile = $fontPath;
    }

    function setMargins($charWidth, $charHeight) {
        $this -> _marginX = $charWidth;
        $this -> _marginY = $charHeight;
    }

}

?>