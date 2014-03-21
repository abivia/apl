<?php
/**
 * Flat map, uniform fill.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Flat.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Generate a single color uniform fill.
 */
class AP5L_Gfx_FillMap_Flat extends AP5L_Gfx_FillMap {

    function getColor($x, $y, $colors) {
        return $colors[0];
    }

    function getColorInt($x, $y, $colors) {
        return $colors[0] -> getRgbaInt(true);
    }

    function getRatio($x, $y) {
        return 1.0;
    }

    function setup($area) {
        $this -> iterateMode = 'r';
        $this -> startX = 0;
        $this -> endX = floor($area -> getSize() -> x);
        $this -> incrX = floor($area -> getSize() -> x);
        $this -> startY = 0;
        $this -> endY = floor($area -> getSize() -> y);
        $this -> incrY = floor($area -> getSize() -> y);
    }
}

