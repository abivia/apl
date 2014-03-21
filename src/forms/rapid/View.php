<?php
/**
 * Abivia PHP5 Library
 * 
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: View.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 *
 */

/**
 * Base view class
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Rapid_View extends AP5L_Php_InflexibleObject {
    /**
     * A stack of control names for identifier generation.
     * 
     * @var array 
     */
    protected $controlPath;

    function idPath($name, $index = '') {
        $pathBits = $this -> controlPath;
        // Consume an empty ID prefix.
        if ($pathBits[0] == '') {
            array_shift($pathBits);
        }
        $path = implode(':', $pathBits);
        return ($path ? $path . ':' : '') . ($index === '' ? $name : $index);
    }

}
