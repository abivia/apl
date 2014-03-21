<?php
/**
 * Abivia PHP5 Library
 *
 * @package AP5L
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Exception.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * AP5L Exception
 */
class AP5L_Exception extends Exception {
    /**
     * Language independent exception details.
     *
     * @var array
     */
    public $details = array();
    
    function __toString() {
        $str = parent::__toString();
        foreach ($this -> details as $info) {
            $str .= ' ' . $info;
        }
        return $str;
    }

    /**
     * Default factory method
     */
    static function &factory($subType = '', $message = '', $code = 0, $details = array()) {
        $subType = ($subType == '') ? 'AP5L_Exception' : 'AP5L_' . $subType . 'Exception';
        $e = new $subType($message, $code);
        $e -> details = $details;
        return $e;
    }

    /**
     * Factory method to throw a PEAR error as an exception
     */
    static function &fromPEAR($pearError) {
        $e = self::factory(
            $pearError -> getMessage(),
            $pearError -> getCode()
        );
        return $e;
    }

}

?>
