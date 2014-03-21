<?php
/**
 * XML-RPC multi-call client.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ClientMulticall.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Multi-call client
 */
class AP5L_XmlRpc_ClientMulticall extends AP5L_XmlRpc_Client {
    var $calls = array();
    
    function __construct($server, $path = false, $port = 80) {
        parent::AP5L_XmlRpc_Client($server, $path, $port);
        $this -> userAgent = XMLRPC_USER_AGENT . ' (multicall)' . $this -> getVersion();
    }
    
    function addCall() {
        $args = func_get_args();
        $methodName = array_shift($args);
        $struct = array(
            'methodName' => $methodName,
            'params' => $args
        );
        $this -> calls[] = $struct;
    }
    
    function query() {
        // Prepare multicall, then call the parent::query() method
        return parent::query('system.multicall', $this -> calls);
    }
}

?>