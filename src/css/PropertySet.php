<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: PropertySet.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A CSS property set.
 * 
 * @package AP5L
 * @subpackage Css
 */
class AP5L_Css_PropertySet {
    public $properties;                    // Array of style properties

    function getProperties() {
        return $this -> properties;
    }

    function setProperty($name, $value = '') {
        if ($name instanceof AP5L_Css_PropertySet) {
            $this -> setProperty($name -> properties);
        } else if (is_array($name)) {
            foreach ($name as $pname => $pval) {
                $this -> setProperty($pname, $pval);
            }
        } else {
            // Here we should incorporate some intelligence about the properties.
            // For example if the border is 3 and we set border-left to 10, then
            // we should be set up to generate border: 3, 3, 10, 3 or some such.
            $this -> properties[$name] = $value;
        }
    }

}
