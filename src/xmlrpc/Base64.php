<?php
/**
 * Base 64 encoder.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Base64.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Encode a Base 64 data element.
 */
class AP5L_XmlRpc_Base64 {
    var $data;
    
    /**
     * Constructor.
     * 
     * @param string Binary string data
     */
    function __construct($data) {
        $this -> data = $data;
    }
    
    /**
     * Return data element as XML
     * 
     * @return string Data encoded as a Base-64 string.
     */
    function getXml() {
        return '<base64>' . base64_encode($this -> data) . '</base64>';
    }
    
}

?>