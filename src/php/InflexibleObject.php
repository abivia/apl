<?php
/**
 * Inflexible object: throw exceptions on attempts to access dynamic methods,
 * properties.
 * 
 * @package AP5L
 * @subpackage Php
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: InflexibleObject.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Inflexible object.
 * 
 * This class traps attempts to access methods and members that aren't in the
 * class definition and to throw an exception when this occurs. This is very
 * useful in trapping typographical errors and other unplanned uses of the
 * class.
 */
class AP5L_Php_InflexibleObject {
    /**
     * Type of exception to throw.
     *
     * We use a big long name for this to avoid conflict with members of the
     * classes that use it.
     *
     * @var string
     */
    static $inflexibleObjectExceptionType = 'Exception';

    function __call($name, $args) {
        $ec = self::$inflexibleObjectExceptionType;
        throw new $ec('Attempt to call undeclared method '
            . get_class($this) . '::' . $name);
    }

    function __get($name) {
        $ec = self::$inflexibleObjectExceptionType;
        throw new $ec('Attempt to access undeclared member '
            . get_class($this) . '::' . $name);
    }

    function __set($name, $value) {
        $ec = self::$inflexibleObjectExceptionType;
        throw new $ec('Attempt to assign undeclared member '
            . get_class($this) . '::' . $name);
    }

}


?>