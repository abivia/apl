<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Stdout.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Diagnostic output to stdout (via echo).
 * 
 * @package AP5L
 * @subpackage Debug
 */
class AP5L_Debug_Stdout extends AP5L_Debug {
    protected $_buffer = '';

    function _write($data) {
        if (PHP_SAPI == 'cli' || headers_sent()) {
            echo $this -> _buffer, $data;
            $this -> _buffer = '';
        } else {
            $this -> _buffer .= $data;
        }
    }

    static function getInstance() {
        if (! self::$_instance) {
            self::$_instance = new AP5L_Debug_Stdout;
        }
        return self::$_instance;
    }

}
