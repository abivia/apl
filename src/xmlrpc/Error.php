<?php
/**
 * XML-RPC Error result
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Error.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Representation of an error result
 */
class AP5L_XmlRpc_Error {
    var $code;
    var $message;
    
    function __construct($code, $message) {
        $this -> code = $code;
        $this -> message = $message;
    }
    
    function __toString() {
        return $this -> code . ': ' . $this -> message;
    }
    
    function getXml() {
        $msg = AP5L_XmlRpc_Value::xmlString($this -> message);
        $xml = '<methodResponse><fault><value><struct><member><name>faultCode</name><value><int>'
            . $this -> code . '</int></value></member><member><name>faultString</name><value><string>'
            . $msg . '</string></value></member></struct></value></fault></methodResponse>' . chr(10);
        return $xml;
    }
    
}

?>