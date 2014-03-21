<?php
/**
 * XML-RPC Request
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Request.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Representation of a request.
 */
class AP5L_XmlRpc_Request {
    var $method;
    var $args;
    var $xml;
    
    function __construct($method, $args, $eol = '') {
        $this -> method = $method;
        $this -> args = $args;
        $this -> xml = '<?xml version="1.0"?>' . chr(10) . '<methodCall>' . $eol 
            . '<methodName>'
            . $this -> method . '</methodName>' . $eol . '<params>' . $eol;
        foreach ($this -> args as $arg) {
            $v = new AP5L_XmlRpc_Value($arg);
            $this -> xml .= '<param><value>' . $eol . $v -> getXml($eol) . '</value></param>' . $eol;
        }
        $this -> xml .= '</params>' . $eol . '</methodCall>' . $eol;
    }
    
    function getLength() {
        return strlen($this -> xml);
    }
    
    function getXml() {
        return $this -> xml;
    }
}

?>