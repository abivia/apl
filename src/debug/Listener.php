<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Listener.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A near-trivial diagnostic information listener and reporter.
 *
 * @package AP5L
 * @subpackage Debug
 */
class AP5L_Debug_Listener {
    public $headerSecs = 30;

    function dump($port = 2552) {
        $en = $em = $addr = 0;  // Just to kill parse warnings
        $lastAddr = '';
        $lastTime = 0;
        $socket = stream_socket_server('udp://0.0.0.0:' . $port, $en, $em, STREAM_SERVER_BIND);
        while (true) {
            $buf = stream_socket_recvfrom($socket, 16384, 0, $addr);
            $msgTime = time();
            $hdr = '';
            $glue = '---- ';
            if ($msgTime - $lastTime > $this -> headerSecs) {
                $hdr .= $glue . date('Y-m-d H:i:s', $msgTime);
                $glue = ' ';
            }
            $lastTime = $msgTime;
            if ($lastAddr != $addr) {
                $hdr .= $glue . $addr;
                $lastAddr = $addr;
            }
            if ($hdr) {
                echo $hdr . AP5L::NL;
            }
            echo $buf;
        }
    }

}
