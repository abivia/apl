<?php
/**
 * XML-RPC Message.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Message.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Representation of a message.
 */
class AP5L_XmlRpc_Message {
    // Current variable stacks
    var $_arraystructs = array();       // The stack used to keep track of the current array/struct
    var $_arraystructstypes = array();  // Stack keeping track of if things are structs or array
    var $_currentStructName = array();  // A stack as well
    var $_structsClass = array();       // Stack used to keep track of the current struct class name
    var $_typed = array();              // Stack: track untyped values
    var $_value;
    var $_currentTag;
    var $_currentTagContents;
    // The XML parser
    var $_parser;
    var $debug;
    var $faultCode;
    var $faultString;
    var $message;
    var $messageType;  // methodCall / methodResponse / fault
    var $methodName;
    var $params;

    function __construct($message) {
        $this -> message = $message;
    }

    function parse() {
        // first remove the XML declaration
        $this -> message = preg_replace('/<\?xml.*?\?'.'>/', '', $this -> message, 1);
        if (trim($this -> message) == '') {
            $this -> faultCode = -1;
            $this -> faultString = 'Empty message.';
            return false;
        }
        $this -> _parser = xml_parser_create();
        // Set XML parser to take the case of tags in to account
        xml_parser_set_option($this -> _parser, XML_OPTION_CASE_FOLDING, false);
        // Set XML parser callback functions
        xml_set_object($this -> _parser, $this);
        xml_set_element_handler($this -> _parser, 'tag_open', 'tag_close');
        xml_set_character_data_handler($this -> _parser, 'cdata');
        if (! xml_parse($this -> _parser, $this -> message)) {
            $near = xml_get_current_byte_index($this -> _parser) - 32;
            $nearStr = substr($this -> message, $near, 64);
            if ($near < 0) $near = 0;
            $this -> faultCode = -1;
            $this -> faultString = 'XML error: '
                . xml_error_string(xml_get_error_code($this -> _parser))
                . ' line ' . xml_get_current_line_number($this -> _parser)
                . ' col ' . xml_get_current_column_number($this -> _parser)
                . ' near "' . $nearStr . '" (' . urlencode($nearStr) . ')';

            return false;
        }
        xml_parser_free($this -> _parser);
        // Grab the error messages, if any
        if ($this -> messageType == 'fault') {
            $this -> faultCode = $this -> params[0]['faultCode'];
            $this -> faultString = $this -> params[0]['faultString'];
        }
        return true;
    }

    function tag_open($parser, $tag, $attr) {
        $this -> currentTag = $tag;
        $this -> _currentTagContents = '';
        switch($tag) {
            case 'methodCall':
            case 'methodResponse':
            case 'fault': {
                $this -> messageType = $tag;
            } break;
            // Deal with stacks of arrays and structs
            case 'data': {   // data is to all intents and puposes more interesting than array
                $this -> _arraystructstypes[] = 'array';
                $this -> _arraystructs[] = array();
            } break;
            case 'struct': {
                $this -> _arraystructstypes[] = 'struct';
                $this -> _arraystructs[] = array();
                $this -> _structsClass[] = isset($attr['class']) ? $attr['class'] : '';
            } break;
            case 'value': {
                $this -> _typed[] = false;
            } break;
        }
    }

    function cdata($parser, $cdata) {
        $this -> _currentTagContents .= $cdata;
    }

    function tag_close($parser, $tag) {
        $typeFlag = true;
        $valueFlag = false;
        switch($tag) {
            case 'int':
            case 'i4':
                $value = (int)trim($this -> _currentTagContents);
                $valueFlag = true;
                break;
            case 'double':
                $value = (double)trim($this -> _currentTagContents);
                $valueFlag = true;
                break;
            case 'string':
                //$value = (string)html_entity_decode($this -> _currentTagContents);
                $value = $this -> _currentTagContents;
                $valueFlag = true;
                break;
            case 'dateTime.iso8601':
                $value = new AP5L_XmlRpc_Date(trim($this -> _currentTagContents));
                // $value = $iso -> getTimestamp();
                $valueFlag = true;
                break;
            case 'value':
                // If no type is indicated, the type is string.
                if (! array_pop($this -> _typed)) {
                    $value = $this -> _currentTagContents;
                    $valueFlag = true;
                }
                $typeFlag = false;
                break;
            case 'boolean':
                $value = (boolean)trim($this -> _currentTagContents);
                $valueFlag = true;
                break;
            case 'base64':
                $value = base64_decode($this -> _currentTagContents);
                $valueFlag = true;
                break;
            case 'nil':
                $value = null;
                $valueFlag = true;
                break;
            // Deal with stacks of arrays and structs
            case 'class':
                $this -> _structsClass[count($this -> _structsClass) - 1] =
                    trim($this -> _currentTagContents);
                $typeFlag = false;
                break;
            case 'data':
                $value = array_pop($this -> _arraystructs);
                array_pop($this -> _arraystructstypes);
                $valueFlag = true;
                $typeFlag = false;
                break;
            case 'struct':
                // If there is a class name, attempt to instantiate it
                $cindx = count($this -> _structsClass) - 1;
                $classID = $this -> _structsClass[$cindx];
                //
                // Parse out a language identifier. If there is none, assume PHP.
                //
                if (($posn = strpos($classID, ':')) !== false) {
                    if (substr($classID, 0, $posn) == 'php') {
                        $classID = substr($classID, $posn + 1);
                    } else {
                        // We don't know how to deal with a non-php class...
                        $classID = '';
                    }
                }
                if ($classID && class_exists($classID)) {
                   $members = array_pop($this -> _arraystructs);
                   $value = new $classID;
                   foreach ($members as $member => $mval) {
                      $value -> $member = $mval;
                   }
                } else {
                   $value = array_pop($this -> _arraystructs);
                }
                array_pop($this -> _arraystructstypes);
                array_pop($this -> _structsClass);
                $valueFlag = true;
                $typeFlag = true;
                break;
            case 'member':
                array_pop($this -> _currentStructName);
                $typeFlag = false;
                break;
            case 'name':
                $this -> _currentStructName[] = trim($this -> _currentTagContents);
                $typeFlag = false;
                break;
            case 'methodName':
                $this -> methodName = trim($this -> _currentTagContents);
                $typeFlag = false;
                break;
        }
        if ($typeFlag && ($ind = count($this -> _typed))) {
            $this -> _typed[$ind - 1] = true;
        }
        $this -> _currentTagContents = '';
        if ($valueFlag) {
            //if (!is_array($value) && !is_object($value)) {
            //    $value = trim($value);
            //}

            if (count($this -> _arraystructs) > 0) {
                // Add value to struct or array
                if ($this -> _arraystructstypes[count($this -> _arraystructstypes)-1] == 'struct') {
                    // Add to struct
                    $this -> _arraystructs[count($this -> _arraystructs)-1][$this -> _currentStructName[count($this -> _currentStructName)-1]] = $value;
                } else {
                    // Add to array
                    $this -> _arraystructs[count($this -> _arraystructs)-1][] = $value;
                }
            } else {
                // Just add as a paramater
                $this -> params[] = $value;
            }
        }
    }
}

?>