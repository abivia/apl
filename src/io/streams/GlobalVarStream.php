<?php
/**
 * Modify a global variable via stream.
 *
 * @package AP5L
 * @subpackage Io
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: GlobalVarStream.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A class for reading and writing a global variable. Based on the example in
 * the PHP reference manual. Use: streamid://_varName
 */
class GlobalVarStream {
    var $_posn;
    var $_varName;

    function open($path, $mode, $options, &$openedPath) {
        $url = parse_url($path);
        $this -> _varName = $url['host'];
        $this -> _posn = 0;
        return true;
    }

    function read($count) {
        $ret = substr($GLOBALS[$this -> _varName], $this -> _posn, $count);
        $this -> _posn += strlen($ret);
        return $ret;
    }

    function write($data) {
        $left = substr($GLOBALS[$this -> _varName], 0, $this -> _posn);
        $right = substr($GLOBALS[$this -> _varName], $this -> _posn + strlen($data));
        $GLOBALS[$this -> _varName] = $left . $data . $right;
        $this -> _posn += strlen($data);
        return strlen($data);
    }

    function tell() {
        return $this -> _posn;
    }

    function eof() {
        return $this -> _posn >= strlen($GLOBALS[$this -> _varName]);
    }

    function seek($offset, $whence) {
        switch ($whence) {
            case SEEK_SET: {
                if ($offset < strlen($GLOBALS[$this -> _varName]) && $offset >= 0) {
                     $this -> _posn = $offset;
                     return true;
                }
            } break;

            case SEEK_CUR: {
                if ($offset >= 0) {
                     $this -> _posn += $offset;
                     return true;
                }
            } break;

            case SEEK_END: {
                if (strlen($GLOBALS[$this -> _varName]) + $offset >= 0) {
                     $this -> _posn = strlen($GLOBALS[$this -> _varName]) + $offset;
                     return true;
                }
            } break;

        }
        return false;
    }
}

?>