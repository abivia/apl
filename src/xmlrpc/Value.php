<?php
/**
 * XML-RPC Value
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Value.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Representation of a value
 */
class AP5L_XmlRpc_Value {
    var $className;
    var $data;
    var $type;

    function __construct ($data, $type = false) {
        $this -> data = $data;
        if (!$type) {
            $type = $this -> calculateType();
        }
        $this -> type = $type;
        if ($type == 'struct') {
            // Turn all the values in the array in to new AP5L_XmlRpc_Value objects
            foreach ($this -> data as $key => $value) {
                $this -> data[$key] = new AP5L_XmlRpc_Value($value);
            }
        }
        if ($type == 'array') {
            for ($i = 0, $j = count($this -> data); $i < $j; $i++) {
                $this -> data[$i] = new AP5L_XmlRpc_Value($this -> data[$i]);
            }
        }
    }

    function calculateType() {
        if (is_null($this -> data)) {
            return 'nil';
        }
        if ($this -> data === true || $this -> data === false) {
            return 'boolean';
        }
        if (is_integer($this -> data)) {
            return 'int';
        }
        if (is_double($this -> data)) {
            return 'double';
        }
        // Deal with IXR object types base64 and date
        if (is_object($this -> data) && ($this -> data instanceof AP5L_XmlRpc_Date)) {
            return 'date';
        }
        if (is_object($this -> data) && ($this -> data instanceof AP5L_XmlRpc_Base64)) {
            return 'base64';
        }
        // If it is a normal PHP object, pass as a class
        if (is_object($this -> data)) {
            $this -> className = get_class($this -> data);
            $this -> data = get_object_vars($this -> data);
            return 'struct';
        }
        if (!is_array($this -> data)) {
            return 'string';
        }
        // We have an array - is it an array or a struct ?
        if ($this -> isStruct($this -> data)) {
            $this -> className = null;
            return 'struct';
        } else {
            return 'array';
        }
    }

    function getXml($eol = '') {
        // Return XML for this value
        switch ($this -> type) {
            case 'boolean':
                $result = '<boolean>' . (($this -> data) ? '1' : '0') . '</boolean>';
                break;
            case 'int':
                $result = '<int>' . $this -> data . '</int>';
                break;
            case 'double':
                $result = '<double>' . $this -> data . '</double>';
                break;
            case 'string':
                $result = '<string>' . $this -> xmlString($this -> data) . '</string>';
                break;
            case 'nil':
                $result = '<nil/>';
                break;
            case 'array':
                $result = '<array><data>' . $eol;
                $rLen = 0;
                foreach ($this -> data as $item) {
                    $subXml = '<value>' . $item -> getXml($eol) . '</value>' . $eol;
                    $result .= $subXml;
                }
                $result .= '</data></array>';
                break;
            case 'struct':
                $result = '<struct';
                if ($this -> className) {
                    $result .= ' class="php:' . $this -> className . '"';
                }
                $result .= '>' . $eol;
                $rLen = 0;
                foreach ($this -> data as $name => $value) {
                    $subXml = '<member>' . $eol . '<name>' . $name . '</name>'
                        . $eol . '<value>' . $eol . $value -> getXml($eol) . '</value>'
                        . $eol . '</member>' . $eol;
                    $result .= $subXml;
                }
                $result .= '</struct>';
                break;
            case 'date':
            case 'base64':
                $result = $this -> data -> getXml();
                break;
            default:
                $result = false;
                break;
        }
        if ($result !== false) {
            $result .= $eol;
        }
        return $result;
    }

    function isStruct($array) {
        // Nasty function to check if an array is a struct or not
        $expected = 0;
        foreach ($array as $key => $value) {
            if ((string)$key != (string)$expected) {
                return true;
            }
            $expected++;
        }
        return false;
    }

    static function xmlString($data) {
        // We just escape the characters that can cause problems with the parser.
        // Less than (<) is obvious; & causes "Hi&amp;Lo" to be sent as "Hi&amp;amp;Lo",
        // thus pushing entity resolution to the receiver application.
        // Note: the order of translation is important or < gets translated to &amp;lt;
        return str_replace(array('&', '<'), array('&amp;', '&lt;'), $data);
    }
}

?>