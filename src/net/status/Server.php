<?php
/**
 * Status server
 *
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Server.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Status server
 */
class AP5L_Net_Status_Server {
    // address="204.178.232.5" listen="1" query="10:00" querytimeout="10"
    var $address;
    var $errMsg;
    var $listen;
    var $query;
    var $queryTimeout;

    function genError($msg) {
        $this -> errMsg = __CLASS__ . '::' . $msg;
    }

    function readXmlTree($node) {
        $root = 'server';
        if ($node -> element != $root) {
            $this -> genError('readXmlTree: node must be a ' . $root . ' element (found ' . $node -> element . ')');
            return false;
        }
        if (($this -> address = $node -> getAttribute('address', false)) === false) {
            $this -> genError('readXmlTree: ' . $root . ' is missing required address attribute.');
            return false;
        }
        if (($this -> listen = $node -> getAttribute('listen', false)) === false) {
            $this -> genError('readXmlTree: ' . $root . ' is missing required listen attribute.');
            return false;
        }
        $timeStr = $node -> getAttribute('query', 0);
        $this -> query = AP5L_Net_Status_Monitor::parseTime($timeStr);
        if ($this -> query === false) {
            $this -> genError('readXmlTree: query attribute must be a valid time.');
            return false;
        }
        $this -> queryTimeout = $node -> getAttribute('querytimeout', 0);
        return true;
    }

    function toXML($indent = 2, $depth = 0) {
    }

}

?>
