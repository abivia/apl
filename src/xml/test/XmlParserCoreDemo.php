<?php
/**
 * Demonstration of XmlParserCore
 * 
 * @package AP5L
 * @subpackage Xml
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: XmlParserCoreDemo.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('xml/XmlParserCore.php');

/**
 * Sample code to demonstrate use of the XmlParserCore class.
 * 
 * Contains a class derived from the parser and some sample classes.
 * See the XML sample for a definition of what is parsed.
 */

class DocObject extends XmlParsedObject {
    var $_docID;
    var $_footers = array();
    var $_sections = array();
    
    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            case 'foot': {
                $this -> _footers[] = $parser -> getNewNode();
                echo 'Added Footer number ' . count($this -> _footers) . ')'
                    . ' to Document ' . $this -> _docID . '<br/>';
            } break;
                        
            case 'section': {
                $this -> _sections[] = $parser -> getNewNode();
                echo 'Added Section number ' . count($this -> _sections) . ')'
                    . ' to Document ' . $this -> _docID . '<br/>';
            } break;
                        
            default: {
                $result = false;
            } break;
            
        }
        return $result;
    }
    
    function xmlElementOpen($parser) {
        $this -> _docID = $parser -> attrValueRequired('docid');
    }
    
    function &xmlFactory() {
        return new DocObject();
    }
    
    function xmlRegister(&$parser) {
        $parser -> registerElement('doc', 'DocObject');
    }
    
}

class FooterObject extends XmlParsedObject {
    var $_text;
    
    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            case 'text': {
                $this -> _text = $parser -> getCData();
                echo 'Set text of a FooterObject to ' . $this -> _text . '<br/>';
            } break;
                        
            default: {
                $result = false;
            } break;
            
        }
        return $result;
    }
    
    function &xmlFactory() {
        return new FooterObject();
    }
    
    function xmlRegister(&$parser) {
        $parser -> registerElement('foot', 'FooterObject');
    }
    
}

class SectionObject extends XmlParsedObject {
    var $_name;
    var $_thingies = array();
    
    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            case 'name': {
                $this -> _name = $parser -> getCData();
                echo 'Set name of a SectionObject to ' . $this -> _name . '<br/>';
            } break;
                        
            case 'thingy': {
                $this -> _thingies[] = $parser -> getNewNode();
                echo 'Added Thingy number ' . count($this -> _thingies) . ')'
                    . ' to Section ' . $this -> _name . '<br/>';
            } break;
                        
            default: {
                $result = false;
            } break;
            
        }
        return $result;
    }
    
    function &xmlFactory() {
        return new SectionObject();
    }
    
    function xmlRegister(&$parser) {
        $parser -> registerElement('section', 'SectionObject');
    }
    
}

class ThingyObject extends XmlParsedObject {
    var $_name;
    
    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            case 'name': {
                $this -> _name = $parser -> getCData();
                echo 'Set name of a ThingyObject to ' . $this -> _name . '<br/>';
            } break;
            
            default: {
                $result = false;
            } break;
            
        }
        return $result;
    }
    
    function &xmlFactory() {
        return new ThingyObject();
    }
    
    function xmlRegister(&$parser) {
        $parser -> registerElement('thingy', 'ThingyObject');
    }
    
}

class XmlParserDemo extends XmlParserCore {
    
    function __construct() {
        parent::__construct();
        $this -> registerElement('name');
        $this -> registerElement('text');
        DocObject::xmlRegister($this);
        FooterObject::xmlRegister($this);
        SectionObject::xmlRegister($this);
        ThingyObject::xmlRegister($this);
    }
    
    function XmlParserDemo() {
        $this -> __construct();
    }
    
}

$xml = '
    <doc docid="foo">
        <section>
            <name>section one</name>
            <thingy>
                <name>thingy number one</name>
            </thingy>
            <thingy>
                <name>thingy number two</name>
            </thingy>
        </section>
        <section>
            <name>section two</name>
        </section>
        <foot>
            <text>One toe</text>
        </foot>
        <foot>
            <text>Five toes</text>
        </foot>
     </doc>';

$xpd = new XmlParserDemo();
$xpd -> debug = isset($_REQUEST['dbg']);
$result = $xpd -> _xmlMessage($xml);
if (PEAR::isError($result)) {
    echo $result -> toString();
    echo '<pre>' . print_r($xpd , true) . '<pre/>';
} else {
    echo 'parse is good<br/>';
    $root = $xpd -> getRootNode();
    echo 'Root<pre>' . print_r($root, true) . '<pre/>';
    if (! $root instanceof DocObject) {
        echo 'Parser:<pre>' . print_r($xpd, true) . '<pre/>';
    }
}

?>