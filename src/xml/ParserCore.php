<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: ParserCore.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('PEAR.php');

/**
 * Extensible XML parser with base functions to support parsing. Designed to be
 * useful both as a base and an adjunct class.
 *
 * Usage:
 * Set the handlers for element open and element close (and element
 * push/pop if required).
 * Define which elements are defined in the DTD with setElements. Provide
 * a class reference and factory method if the element requires the creation of
 * a data structure.
 *
 * Element attached classes should provide everything a AP5L_Xml_ParsedObject
 * provides, such as the xmlElementOpen and xmlElementClose methods.
 *
 * See the XmlParserCoreDemo.php file in the test subdirectory.
 *
 * @package AP5L
 * @subpackage Xml
 */
class AP5L_Xml_ParserCore {
    // Push/pop logic. Values at time of handler entry:
    // _element     _currElement    XML                 Notes
    // --------     ------------    ---                 -----
    //  e1push      e1push          <e1push>            Stack is empty
    //  nopush      e1push              <nopush>
    //  nopush      e1push              </nopush>
    //  e2push      e2push              <e2push>        e1push is on stack
    //  stuff       e2push                  <stuff>
    //  stuff       e2push                  </stuff>
    //  e2push      e1push              </e2push>       Pop ocurrs before close handler entered
    //  e1push                      </e1push>

    /**
     * List of all expected elements.
     *
     * @var array
     */
    protected $_allElements = array();

    /**
     * Accumulated CDATA.
     *
     * @var string
     */
    protected $_cData;
    /**
     * Array of factory methods, indexed by class. Each element is array(class,
     * factory_method)
     *
     * @var array
     */
    protected $_classFactory = array();

    /**
     * Array of class names, indexed by element.
     *
     * @var array
     */
    protected $_classMap = array();

    /**
     * List of elements causing a "context push".
     *
     * There is a note: "contextElements deprecated, use _classmap instead." Not
     * sure how true this is...
     *
     * @var array
     */
    protected $_contextElements = array();

    /**
     * Track internally allocated objects.
     *
     * @var array
     */
    protected $_contextStack = array();

    /**
     * The current element open attributes. Values indexed by attribute name.
     *
     * @var array
     */
    protected $_currAttr;

    /**
     * Name of the current object level XML element.
     *
     * @var string
     */
    protected $_currElement;

    /**
     * Set if curr node allocated via a registered element.
     *
     * @var boolean
     */
    protected $_currIsReg;
    /**
     * Current object-level node.
     *
     * @var object
     */
    protected $_currNode;
    /**
     * The element in an element open/close call.
     *
     * @var string
     */
    protected $_element;

    /**
     * Array to track element nesting.
     *
     * @var array
     */
    protected $_elementStack;

    /**
     * Name of the element close handler.
     *
     * @var string
     */
    protected $_handleClose;

    /**
     * Name of the element open handler.
     *
     * @var string
     */
    protected $_handleOpen;

    /**
     * Reference to context pop handler.
     *
     * @var string|array
     */
    protected $_handlePop;
    /**
     * Reference to context push handler.
     *
     * @var string|array
     */
    protected $_handlePush;

    /**
     * Flag, set when element creates an object.
     *
     * @var boolean
     */
    protected $_isObject;

    /**
     * Array of booleans, set if an object was allocated for element.
     *
     * @var array
     */
    protected $_isObjectStack;

    /**
     * Errors buffered during a load.
     *
     * @var array
     */
    protected $_loadErrors;

    /**
     * Set if new node allocated via registered element.
     *
     * @var boolean
     */
    protected $_newIsReg;

    /**
     * Node allocated by element open.
     *
     * @var object
     */
    protected $_newNode;

    /**
     * Stack of nodes created while parsing.
     *
     * @var array
     */
    protected $_nodeStack;

    /**
     * The root node.
     *
     * @var object
     */
    protected $_rootNode;

    /**
     * Parser used for decoding XML.
     *
     * @var object
     */
    protected $_xmlParser;

    /**
     * Flag to control generation of diagnostic output
     *
     * @var boolean
     */
    public $debug;

    function __construct() {
    }

    function __destruct() {
        //xml_parser_free($this -> _xmlParser);
    }

    /**
     * Create a new parser and initialize related data structures.
     */
    protected function _createParser() {
        $this -> _xmlParser = xml_parser_create();
        //
        // Set options
        //
        xml_parser_set_option($this -> _xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this -> _xmlParser, XML_OPTION_SKIP_WHITE, false);
        //
        // Parser callback functions
        //
        xml_set_object($this -> _xmlParser, $this);
        xml_set_character_data_handler($this -> _xmlParser, '_xmlCdata');
        xml_set_element_handler($this -> _xmlParser, '_xmlElementOpen', '_xmlElementClose');
        //
        // Reset the stacks and error array
        //
        $this -> _elementStack = array();
        $this -> _isObjectStack = array();
        $this -> _nodeStack = array();
        $this -> _loadErrors = array();
        $this -> _currNode = null;
        $this -> _rootNode = null;
    }

    /**
     * Convert various representations of a flag into a standard format.
     *
     * @param string The flag value to be normalized
     * @param string The value to be used if val is unconvertable
     * @return string T, F, or defaultValue.
     */
    protected function _flagNormalize($val, $defaultValue = 'F') {
        if (is_bool($val)) {
            return $val ? 'T' : 'F';
        } else if (strlen($val)) {
            $val = strtoupper($val);
            switch ($val{0}) {
                case '0':
                case 'F':
                case 'N':
                    return 'F';

                case '1':
                case 'T':
                case 'Y':
                    return 'T';

                default:
                    return $defaultValue;
            }
        } else {
            return $defaultValue;
        }
    }

    /**
     * Default handler for CDATA. Simply accumulate it into a buffer.
     * Overload this function to do anything fancier.
     *
     * @param object parser XML Parser
     * @param string text The CDATA value
     */
    protected function _xmlCdata($parser, $text) {
        $this -> _cData .= $text;
    }

    /**
     * Element close handler. Adjusts context then calls the handler
     * provided by the derived class. Do not overload this function if you want
     * automatic context handling.
     *
     * @param object XML Parser.
     * @param string The element beinng closed.
     */
    protected function _xmlElementClose($parser, $element) {
        if ($this -> debug) {
            echo __CLASS__ . ':_xmlElementClose e=' . $element
                . ' cn=' . get_class($this -> _currNode)
                . ' isObj=' . $this -> _isObject . '<br/>';
        }
        $this -> _element = $element;
        if ($this -> _currNode && $this -> _isObject) {
            if ($this -> debug) {
                echo '&nbsp;&nbsp;&nbsp;' . __CLASS__ . ':_xmlElementClose call PreClose<br/>';
            }
            $this -> _currNode -> xmlElementPreClose($this);
        }
        if (in_array($element, $this -> _contextElements)) {
            $this -> _xmlElementPop();
        }
        if ($this -> debug) {
            echo '&nbsp;&nbsp;&nbsp;' . __CLASS__ . ':_xmlElementClose post-pop ce=' . $this -> _currElement
                . ' cn=' . get_class($this -> _currNode)
                . ' nn=' . get_class($this -> _newNode)
                . ' cir=' . $this -> _currIsReg
                . '<br/>';
        }
        if ($this -> _handleClose) {
            call_user_func($this -> _handleClose);
        }
        if ($this -> _currNode && $this -> _currIsReg) {
            if ($this -> debug) {
                echo '&nbsp;&nbsp;&nbsp;' . __CLASS__ . ':_xmlElementClose call Close e=' . $element
                    . ' cn=' . get_class($this -> _currNode) . '<br/>';
            }
            $this -> _currNode -> xmlElementClose($this);
        }
    }

    /**
     * Element open handler. Verifies element, adjusts context, calls the
     * handler provided by the derived class. Do not overload this function
     * unless you provide similar functionality.
     *
     * @param object XML Parser
     * @param string The element being opened
     * @param array Array [attr] = value; element attributes.
     */
    protected function _xmlElementOpen($parser, $element, $attr) {
        if ($this -> debug) {
            echo __CLASS__ . ':_xmlElementOpen e=' . $element
                . ' cn=' . get_class($this -> _currNode) . '<br/>';
        }
        if (! in_array($element, $this -> _allElements)) {
            $this -> _loadErrors[] = new PEAR_Error('unexpected element: ' . $element);
            return;
        }
        $this -> _element = $element;
        //
        // If this element has an associated class, set the _newIsReg flag
        // then call the factory to create a class instance
        //
        $this -> _isObject = $this -> _newIsReg = isset($this -> _classMap[$element]);
        if ($this -> _isObject) {

            if ($this -> debug) {
                echo '&nbsp;&nbsp;&nbsp;' . __CLASS__ . ':_xmlElementOpen new object, context='
                    . $this -> _classMap[$element] . '<br/>';
            }
            $nn = call_user_func($this -> _classFactory[$this -> _classMap[$element]]);
            $this -> _newNode = &$nn;
            if (is_null($this -> _rootNode)) {
                if ($this -> debug) {
                    echo '&nbsp;&nbsp;&nbsp;' . __CLASS__ . ':_xmlElementOpen assigning ROOT<br/>';
                }
                $this -> _rootNode = $this -> _newNode;
            }
        }
        $this -> _currAttr = &$attr;
        if ($this -> _handleOpen) {
            call_user_func($this -> _handleOpen);
        }
        if ($this -> _isObject) {
            $nn -> xmlElementOpen($this);
        } elseif ($this -> _currNode) {
            $this -> _currNode -> xmlElementOpen($this);
        }
        if (in_array($element, $this -> _contextElements)) {
            $this -> _xmlElementPush();
        }
        $this -> _cData = '';
    }

    /**
     * Remove an element from the context stack.
     */
    protected function _xmlElementPop() {
        if ($this -> debug) {
            echo __CLASS__ . ':_xmlElementPop cn=' . get_class($this -> _currNode)
                . ' stack=' . get_class($this -> _elementStack[count($this -> _elementStack) - 1]) . '<br/>';
        }
        $this -> _currElement = array_pop($this -> _elementStack);
        $this -> _currIsReg = array_pop($this -> _contextStack);
        $this -> _isObject = array_pop($this -> _isObjectStack);
        if ($this -> _currIsReg) {
            $this -> _newNode = $this -> _currNode;
            $this -> _currNode = array_pop($this -> _nodeStack);
        }
        if ($this -> _handlePop) {
            call_user_func($this -> _handlePop);
        }
        if ($this -> debug) {
            echo '&nbsp;&nbsp;&nbsp;' . __CLASS__ . ':_xmlElementPop exit'
                . ' cn=' . get_class($this -> _currNode)
                . ' nn=' . get_class($this -> _newNode)
                . ' cir=' . $this -> _currIsReg
                . '<br/>';
        }
    }

    /**
     * Push an element onto the context stack.
     */
    protected function _xmlElementPush() {
        if ($this -> debug) {
            echo __CLASS__ . ':_xmlElementPush cn=' . get_class($this -> _currNode)
                . ' nn=' . get_class($this -> _newNode)
                . ' nir=' . $this -> _newIsReg
                . '<br/>';
        }
        if ($this -> _handlePush) {
            call_user_func($this -> _handlePush);
        }
        if ($this -> _newIsReg) {
            $this -> _nodeStack[] = $this -> _currNode;
            $this -> _currNode = $this -> _newNode;
            $this -> _currIsReg = $this -> _newIsReg;
            unset($this -> _newNode);
        }
        $this -> _contextStack[] = $this -> _currIsReg;
        $this -> _isObjectStack[] = $this -> _isObject;
        $this -> _elementStack[] = $this -> _currElement;
        $this -> _currElement = $this -> _element;
    }

    /**
     * Parse the contents of a file.
     *
     * @param string Path to the file to be parsed.
     * @param int File buffer size.
     * @return boolean True if the parse was successful, PEAR Error object on
     * failure.
     */
    protected function _xmlFile($path, $blockSize = 8192) {
        if (! $this -> _xmlParser) {
            $this -> _createParser();
        }
        $result = true;
        $fh = @fopen($path, 'r');
        if (! $fh) {
            $result = new PEAR_Error('Unable to open ' . $path);
        } else {
            while (! feof($fh)) {
                $xml = fread($fh, $blockSize);
                if (! xml_parse($this -> _xmlParser, $xml, feof($fh))) {
                    return new PEAR_Error('XML error: ' . xml_error_string(xml_get_error_code($this -> _xmlParser))
                        . ' on line ' . xml_get_current_line_number($this -> _xmlParser) . ' col '
                        . xml_get_current_column_number($this -> _xmlParser));
                }
            }
            if (count($this -> _loadErrors)) {
                $result = $this -> _loadErrors[0];
                $result -> addUserInfo('Total errors: ' . count($this -> _loadErrors));
            }
        }
        return $result;
    }

    /**
     * Parse the contents of a string.
     *
     * @param string The XML to be parsed.
     * @return boolean True if the parse was successful, PEAR Error object on
     * failure.
     */
    protected function _xmlMessage(&$xml) {
        $this -> _createParser();
        if (xml_parse($this -> _xmlParser, $xml)) {
            if (count($this -> _loadErrors)) {
                $result = $this -> _loadErrors[0];
                $result -> addUserInfo('Total errors: ' . count($this -> _loadErrors));
            } else {
                $result = true;
            }
        } else {
            $result = new PEAR_Error('XML error: ' . xml_error_string(xml_get_error_code($this -> _xmlParser))
                . ' on line ' . xml_get_current_line_number($this -> _xmlParser) . ' col '
                . xml_get_current_column_number($this -> _xmlParser));
        }
        xml_parser_free($this -> _xmlParser);
        $this -> _xmlParser = 0;
        return $result;
    }

    /**
     * Parse a string representation of a flag into a boolean.
     *
     * @param string attrName The attribute to be parsed
     * @param boolean defaultValue The value to be returned in the flag is not
     * parsable as a boolean.
     * @return boolean Boolean value of the parsed flag.
     */
    function attrFlag($attrName, $defaultValue = false) {
        return (isset($this -> _currAttr[$attrName]) && strlen($this -> _currAttr[$attrName]))
            ? (strpos('1TY', strtoupper($this -> _currAttr[$attrName]{0})) !== false)
            : $defaultValue;
    }

    /**
     * Parse a string representation of a flag into a boolean
     */
    function attrFlagRequired($attrName, $defaultValue = false) {
        if (!isset($this -> _currAttr[$attrName])) {
            $this -> _loadErrors[] = new PEAR_Error('"' . $this -> _currElement . '" element requires "'
                . $attrName . '" attribute at line '
                . xml_get_current_line_number($this -> _xmlParser));
            return $defaultValue;
        }
        return (strlen($this -> _currAttr[$attrName]))
            ? (strpos('1TY', strtoupper($this -> _currAttr[$attrName]{0})) !== false)
            : $defaultValue;
    }

    /**
     * Get value of an attribute.
     *
     * @param string The attribut to retrieve.
     * @param string The value to return if the attribut is not present.
     * @return string The attribute value or the default value
     */
    function attrValue($attrName, $defaultValue = '') {
        if (!isset($this -> _currAttr[$attrName])) {
            return $defaultValue;
        }
        return $this -> _currAttr[$attrName];
    }

    /**
     * Get a required attribute.
     *
     * @param string The attribute to retrieve.
     * @param string The value to return if the attribute is not present.
     * @return string The attribute value or the default value. If the attribute
     * is not present, an error is added to _loadErrors.
     */
    function attrValueRequired($attrName, $defaultValue = '') {
        if (!isset($this -> _currAttr[$attrName])) {
            $this -> _loadErrors[] = new PEAR_Error('"' . $this -> _currElement . '" element requires "'
                . $attrName . '" attribute at line '
                . xml_get_current_line_number($this -> _xmlParser));
            return $defaultValue;
        }
        return $this -> _currAttr[$attrName];
    }

    /**
     * Remove all element definitions.
     */
    function clearElements() {
        $this -> _allElements = array();
        $this -> _contextElements = array();
    }

    /**
     * Return the current CDATA buffer, optionally decoding HTML entities.
     *
     * @param boolean If set, then HTML entities are converted to their
     * corresponding characters.
     * @return string The contents of the current CDATA buffer.
     */
    function getCData($decode = false) {
        if ($decode) {
            return html_entity_decode($this -> _cData);
        }
        return $this -> _cData;
    }

    /**
     * Get the current element.
     *
     * @return string String representation of the current XML element.
     */
    function getElement() {
        return $this -> _element;
    }

    /**
     * Get the full set of parse errors.
     *
     * @return array Array of PEAR_Error objects
     */
    function getErrors() {
        return $this -> _loadErrors;
    }

    /**
     * Get the last object allocated by an element open.
     *
     * @return object A copy of the most recently allocated object structure.
     */
    function getNewNode() {
        return $this -> _newNode;
    }

    /**
     * Get the root node for a parsed structure.
     *
     * @return object A copy of the parsed root node.
     */
    function getRootNode() {
        if (count($this -> _nodeStack)) {
            return $this -> _nodeStack[0];
        } else {
            return $this -> _newNode;
        }
        //return $this -> _rootNode;
    }

    /**
     * Register classes to the parser.
     * @param string|array Class name or array of class names to register. Each
     * class must support the functions in XmlParsedObject.
     */
    function registerClasses($classes) {
        if (! is_array($classes)) {
            $classes = array($classes);
        }
        foreach ($classes as $className) {
            call_user_func_array(array($className, 'xmlRegister'), array(&$this));
        }
    }

    /**
     * Add an element to the list of acceptable elements.
     *
     * If the class name and factory are specified, register this as a context
     * element. The factoryMethod is assumed to be a statically callable method
     * of the named class.
     *
     * @param string The element to be added
     * @param string Name of a class to be instantiated when this element is
     * encountered.
     * @param string Name of a method to be called to create a class instance.
     */
    function registerElement($element, $className = '', $factoryMethod = '') {
        if ($className) {
            if (! $factoryMethod) {
                $factoryMethod = 'xmlFactory';
            }
            $factory = array($className, $factoryMethod);
            if (! is_callable($factory)) {
                return false;
            }
        }
        $this -> _allElements[$element] = $element;
        if ($className) {
            $this -> _classMap[$element] = $className;
            $this -> _classFactory[$className] = $factory;
            $this -> _contextElements[$element] = $element;
        }
        return true;
    }

    /**
     * Define an external handler for element close.
     *
     * @param mixed Either the name of a function or an array of (object,
     * method) to be called as part of an element close event.
     */
    function setCloseHandler($method = '') {
        if (! is_array($method)) {
            $method = array(&$this, $method);
        }
        $this -> _handleClose = $method;
    }

    /**
     * Define a set of recognized and context-setting elements.
     *
     * @param stringArray all List of all XML recognized elements.
     * @param stringArray context List of all elements that get pushed onto the
     * context stack.
     */
    function setElements($all, $context = null) {
        if (is_array($all)) {
            foreach ($all as $element) {
                $this -> _allElements[$element] = $element;
            }
        }
        if (is_array($context)) {
            foreach ($context as $element) {
                $this -> _contextElements[$element] = $element;
            }
        }
    }

    /**
     * Define an external handler for element open.
     *
     * @param mixed method Either the name of a function or an array of (object,
     * method) to be called as part of an element open event.
     */
    function setOpenHandler($method = '') {
        if (! is_array($method)) {
            $method = array(&$this, $method);
        }
        $this -> _handleOpen = $method;
    }

    /**
     * Define an external handler for element pop.
     *
     * @param mixed method Either the name of a function or an array of (object,
     * method) to be called as part of an element pop event.
     */
    function setPopHandler($method = '') {
        if (! is_array($method)) {
            $method = array(&$this, $method);
        }
        $this -> _handlePop = $method;
    }

    /**
     * Define an external handler for element push.
     *
     * @param mixed method Either the name of a function or an array of (object,
     * method) to be called as part of an element push event.
     */
    function setPushHandler($method = '') {
        if (! is_array($method)) {
            $method = array(&$this, $method);
        }
        $this -> _handlePush = $method;
    }

    function xmlFile($path, $blockSize = 8192) {
        return $this -> _xmlFile($path, $blockSize);
    }

    function xmlMessage($xml) {
        return $this -> _xmlMessage($xml);
    }

}
