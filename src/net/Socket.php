<?php
/**
 * AP5L The Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Store.php 61 2008-06-01 17:20:30Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A simple wrapper for fsock functions.
 * 
 * This not particularly inspired class primarily exists so it can be mocked
 * when testing other code.
 */
class AP5L_Net_Socket {
    /**
     * File handle, when socket is open.
     * 
     * @var resource
     */
    protected $_fh;
    
    function __destroy() {
        if ($this -> _fh) {
            fclose($this -> _fh);
        }
        $this -> _fh = false;
    }
    
    /**
     * Create a socket object and connect it to a target.
     * @param string Address of the system to connect to.
     * @param int The port to use.
     * @param float The maximum number of seconds to wait for a connection.
     */
    static function &factory($target, $port, $timeout = null) {
        $sock = new AP5L_Net_Socket();
        $sock -> open($target, $port, $timeout);
        return $sock;
    }
    
    /**
     * Open a socket.
     *
     * @param string Address of the system to connect to.
     * @param int The port to use.
     * @param float The maximum number of seconds to wait for a connection.
     */
    function open($target, $port, $timeout = null) {
        $this -> _fh = @fsockopen(
            $target, $port,
            $errNum, $errMsg,
            $timeout
        );
        if (! $this -> _fh) {
            if ($errNum == 0) {
                $errMsg = 'Probable socket initialization error.';
            }
            throw AP5L_Exception::factory('Net', $errMsg, $errNum, array($target, $port));
        } 
    }

}
