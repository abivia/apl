<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Udp.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Remote diagnostic output via UDP.
 *
 * @package AP5L
 * @subpackage Debug
 */
class AP5L_Debug_Udp extends AP5L_Debug {
    protected $_dest = '127.0.0.1:2552';
    protected $_socket;

    function __construct($addrPort = '') {
        $this -> newLine = AP5L::LF;
        $this -> connect($addrPort);
    }

    function __destruct() {
        $this -> _close();
    }

    function _close() {
        if ($this -> _socket) {
            $this -> _write(AP5L::LF . 'Socket closed.' . AP5L::LF);
            fclose($this -> _socket);
        }
    }

    function _open() {
        if ($this -> _socket = stream_socket_client('udp://' . $this -> _dest)) {
            $this -> _write('Socket open. ' . date('Y-m-d H:i:s') . AP5L::LF);
            return $this -> _socket;
        }
        return false;
    }

    function _write($data) {
        if (! $this -> _socket) {
            $this -> _open();
        }
        if ($this -> _socket) {
            fwrite($this -> _socket, $data);
        }
    }

    /**
     * Connect to a remote via UDP
     *
     * @param type $addrPort string Address:port to connect to
     */
    function connect($addrPort = '') {
        $this -> _close();
        if ($addrPort != '') {
            $this -> _dest = $addrPort;
        }
        $this -> _open();
    }

    static function getInstance($addrPort = '') {
        if (! self::$_instance) {
            self::$_instance = new AP5L_Debug_Udp($addrPort);
        }
        return self::$_instance;
    }

}
