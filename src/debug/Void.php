<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Void.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl\Debug;

/**
 * A null diagnostic output class.
 *
 * @package AP5L
 * @subpackage Debug
 */
class Void implements \Apl\DebugProvider {
    static protected $_instance;

    function _write($data) {
    }

    function backtrace($handle = null, $options = array()) {
    }

    function flush() {
    }

    function freeHandle($handle) {
    }

    function getHandle($key = '') {
        return 1;
    }

    static function getInstance() {
        return self::$_instance;
    }

    function getState($key = null) {
        return false;
    }

    /**
     * See if debug output is enabled.
     *
     * This method is useful when there's significant computation required to
     * create a diagnostic string, by allowing a branch around it if it's not
     * required.
     *
     * @param int|string An optional debugger handle or scope identifier. See
     * {@see write()}.
     */
    function isEnabled($handle = null) {
        return false;
    }

    static function setInstance(&$instance) {
        self::$_instance = $instance;
    }

    function setState($key, $value) {
    }

    function unsetState($key) {
    }

    function write($data, $handle = null, $options = array()) {
    }

    function writeln($data, $handle = null, $options = array()) {
    }

}

?>
