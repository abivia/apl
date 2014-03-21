<?php
/**
 * Abivia PHP5 Library
 *
 * @package AP5L
 * @subpackage Php
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: HashMap.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A simple hashed collection class, very loosely based on the Java utility
 * class of the same name.
 */
class AP5L_Php_HashMap extends ArrayObject {
    /**
     * Constructor. Like ArrayObject except default is an empty array.
     */
    function __construct($source = array(), $flags = 0, $iClass = 'ArrayIterator') {
        parent::__construct($source, $flags, $iClass);
    }

    /**
     * Empty the map.
     */
    function clear() {
        $this -> exchangeArray(array());
    }

    /**
     * Determine if element exists with specified key.
     *
     */
    function exists($key) {
        return $this -> offsetExists($key);
    }

    /**
     * Get element with specified key.
     *
     */
    function get($key) {
        return $this -> offsetGet($key);
    }

    /**
     * Remove element with specified key.
     *
     */
    function remove($key) {
        $this -> offsetUnset($key);
    }

    /**
     * Set element with specified key.
     *
     */
    function set($key, $value) {
        $this -> offsetSet($key, $value);
    }

}

?>
