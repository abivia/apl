<?php
/**
 * Parse XML into tree-structured variable.
 * 
 * @package AP5L
 * @subpackage Xml
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: XmlTree.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Read/write XML to/from an object structure

$globalXmlNullRef = null;                 // Empty reference for tree nodes DO NOT DELETE!

class XmlFormatInfo {
    var $eatNextIndent = false;
    var $escape = false;
    var $indentSize = 2;
    var $namespacePrefixes = array();   // array[uri] = prefix
    var $newNsText = array();           // array[uri]=text. Pending namespace definitions.
    var $textWrap = 80;
    var $textWrapOver = 60;
    var $useNoEol = false;
    
    function clearNamespace($ns) {
        unset($this -> namespacePrefixes[$ns -> def]);
        unset($this -> newNsText[$ns -> def]);
    }
    
    function eol() {
        if ($this -> useNoEol) {
            return '';
        }
        if ($this -> escape) {
            $s = '<br/>';
        } else {
            $s = chr(10);
        }
        return $s;
    }
    
    function escape($text) {
        if ($this -> escape) {
            $text = htmlentities($text);
        }
        return $text;
    }
    
    function getIndent($depth) {
        if ($this -> useNoEol) {
            return '';
        }
        if ($this -> eatNextIndent) {
            $this -> eatNextIndent = false;
            return '';
        }
        $s = str_pad('', $depth * $this -> indentSize, ' ');
        if ($this -> escape) {
            $s = htmlentities($s);
        }
        return $s;
    }

    function getNamespacePrefix($uri) {
        if (isset($this -> namespacePrefixes[$uri])) {
            $prefix = $this -> namespacePrefixes[$uri] ? $this -> namespacePrefixes[$uri] . ':' : '';
        } else {
            $prefix = '';
        }
        return $prefix;
    }
    
    function getNamespaceXml() {
        $s = '';
        foreach ($this -> newNsText as $def => $text) {
            $s .= ' ' . $text;
            unset($this -> newNsText[$def]);
        }
        return $s;
    }
    
    function setNamespace($ns) {
        $this -> namespacePrefixes[$ns -> def] = $ns -> prefix;
        if ($ns -> prefix) {
            $this -> newNsText[$ns -> def] = 'xmlns:' . $ns -> prefix . '="' . $ns -> def . '"';
        } else {
            $this -> newNsText[$ns -> def] = 'xmlns="' . $ns -> def . '"';
        }
    }
    
    function wrap($text) {
        $s = '';
        $multiLine = false;
        while (strlen($text) > $this -> textWrap) {
            $tooLong = true;
            for ($ind = $this -> textWrap; $ind; $ind--) {
                if ($text{$ind} == ' ') {
                    $s .= $this -> escape(substr($text, 0, $ind)) . $this -> eol();
                    $text = substr($text, $ind + 1);
                    $tooLong = false;
                    $this -> eatNextIndent = false;
                    $multiLine = true;
                    break;
                }
            }
            if ($tooLong) {
                for ($ind = $this -> textWrap + 1; $ind < strlen($text); $ind++) {
                    if ($text{$ind} == ' ') {
                        $s .= $this -> escape(substr($text, 0, $ind)) . $this -> eol();
                        $text = substr($text, $ind + 1);
                        $this -> eatNextIndent = false;
                        $multiLine = true;
                        break;
                    }
                }
            }
        }
        return $s . $this -> escape($text) . ($multiLine ? $this -> eol() : '');
    }
}

$globalLastXmlNodeID = 0;

class XmlNode {
    var $_xmlNodeID;                    // Internal unique node identifier
    var $appData = null;                // Reference to application provided data
    //var $nextNode = null;               // The next node in sequence
    var $parent = null;                 // The parent node
    //var $prevNode = null;               // The previous node in sequence
    
    function __construct($appData = null) {
        global $globalLastXmlNodeID;
        
        $this -> _xmlNodeID = ++$globalLastXmlNodeID;
        $this -> appData = $appData;
    }
    
    function XmlNode($appData = null) {
        $this -> __construct($appData);
    }
    
    function childKill() {
        echo 'Error: call to childKill() not supported by ' . get_class($this);
    }
    
    /**
     * Removes this node from the node tree
     */
    function delink() {
        global $globalXmlNullRef;

        if ($this -> parent) {
            foreach ($this -> parent -> children as $key => $search) {
                if ($this -> equalNodeID($search)) {
                    array_splice($this -> parent -> children, $key, 1);
                    $this -> parent = &$globalXmlNullRef;
                    break;
                }
            }
        }
    }
    
    /**
     * See if this is the same node as another, based on node ID
     */
    function equalNodeID(&$node) {
        if (is_null($node)) return false;
        return $this -> _xmlNodeID == $node -> _xmlNodeID;
    }
    
    function &getNext() {
        global $globalXmlNullRef;

        if ($this -> parent) {
            foreach ($this -> parent -> children as $key => $search) {
                if ($this -> equalNodeID($search)) {
                    if ($key == count($this -> parent -> children) - 1) {
                        $result = &$globalXmlNullRef;
                    } else {
                        $result = &$this -> parent -> children[$key + 1];
                    }
                    break;
                }
            }
        } else {
            $result = &$globalXmlNullRef; 
        }
        return $result;
    }

    function &getPrev() {
        global $globalXmlNullRef;

        if ($this -> parent) {
            foreach ($this -> parent -> children as $key => $search) {
                if ($this -> equalNodeID($search)) {
                    if ($key == 0) {
                        $result = &$globalXmlNullRef;
                    } else {
                        $result = &$this -> parent -> children[$key - 1];
                    }
                    break;
                }
            }
        } else {
            $result = &$globalXmlNullRef; 
        }
        return $result;
    }

    /**
     * Inserts the passed node after this node in the tree
     */
    function linkAfter(&$newNode) {
        if ($this -> parent) {
            foreach ($this -> parent -> children as $key => $search) {
                if ($this -> equalNodeID($search)) {
                    $newNode -> parent = &$this -> parent;
                    array_splice($this -> parent -> children, $key + 1, 0, array($newNode));
                    break;
                }
            }
        }
    }
    
    /**
     * Inserts the passed node before this node in the tree
     */
    function linkBefore(&$newNode) {
        if ($this -> parent) {
            foreach ($this -> parent -> children as $key => $search) {
                if ($this -> equalNodeID($search)) {
                    $newNode -> parent = &$this -> parent;
                    array_splice($this -> parent -> children, $key, 0, array($newNode));
                    break;
                }
            }
        }
    }
    
    function toDump() {
        if ($this -> parent) {
            foreach ($this -> parent -> children as $key => $search) {
                if ($this -> equalNodeID($search)) {
                    if ($key == 0) {
                        $prevText = 'null';
                    } else {
                        $prevText = $this -> parent -> children[$key - 1] -> _xmlNodeID;
                    }
                    if ($key == count($this -> parent -> children) - 1) {
                        $nextText = 'null';
                    } else {
                        $nextText = $this -> parent -> children[$key + 1] -> _xmlNodeID;
                    }
                    break;
                }
            }
        } else {
            $nextText = 'null';
            $prevText = 'null';
        }
        $dump = 'this=' . $this -> _xmlNodeID . ' next=' . $nextText . ' prev=' . $prevText;
        return $dump;
    }
    
    function toString($tag = true) {
        return '';
    }

    function toXml(&$format, $depth) {
        echo 'toXml not implemented for ' . get_class($this) . '. Sorry.';
        exit;
    }
    
}

class XmlRef extends XmlNode {
    var $base;
    var $publicID;
    var $systemID;
    
    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Ref: ';
        }
        $s .= $this -> base;
        if ($this -> publicID) {
            $s .= ' pid=' . $this -> publicID;
        }
        if ($this -> systemID) {
            $s .= ' sid=' . $this -> systemID;
        }
        return $s;
    }

}

class XmlCdata extends XmlNode {
    var $text;
    
    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Cdata: ';
        }
        $s .= 'text=' . htmlspecialchars($this -> text);
        return $s;
    }

    function toXml(&$format, $depth) {
        if (strlen($this -> text) <= $format -> textWrapOver) {
            $format -> eatNextIndent = true;
            return $this -> text;
        }
        return $format -> eol() . $format -> wrap($this -> text);
    }
    
}

class XmlRoot extends XmlNode {
     var $children;                      // array[n] of xmlElement or cdata
    
    function __construct($appData = null) {
        parent::__construct($appData);
        $this -> children = array();
    }
    
    function XmlRoot($appData = null) {
        $this -> __construct($appData);
    }

    function childAppend(&$node) {
            //$this -> currElement -> children[] = &$child;
            //$child -> parent = &$this -> currElement;
        $kids = count($this -> children);
        $this -> children[] = &$node;
        $node -> parent = &$this;
    }
    
    function childKill($node) {
        if (count($this -> children)) {
            foreach ($this -> children as $key => $subNode) {
                if ($node -> equalNodeID($subNode)) {
                    $this -> children[$key] -> delink();
                    //array_splice($this -> children, $key, 1);
                    return true;
                }
            }
        }
        return false;
    }
    
    function &getFirstChild() {
        if (count($this -> children) == 0) {
            return null;
        }
        return $this -> children[0];
    }
    
    function &getFirstChildByClass($className) {
        if (count($this -> children) == 0) {
            return null;
        }
        for ($ind = 0; $ind < count($this -> children); $ind++) {
            if ($this -> children[$ind] instanceof $className) {
                return $this -> children[$ind];
            }
        }
        return null;
    }
    
    function &getNextChildByClass($className, &$start) {
        if (count($this -> children) == 0) {
            return null;
        }
        $walk = 0;
        if (! is_null($start)) {
            foreach ($this -> children as $ind => $kid) {
                if ($this -> children[$ind] === $start) {
                    $walk = $ind;
                    break;
                }
            }
        }
        for ($ind = $walk; $ind < count($this -> children); $ind++) {
            if ($this -> children[$ind] instanceof $className) {
                return $this -> children[$ind];
            }
        }
        return null;
    }
    
    function subNodesToString() {
        //
        // Descriptive text nodes may contain XHTML, so we convert all
        // child nodes back into XML/XHTML.
        //
        $fmt = new XmlFormatInfo();
        $xmlString = '';
        foreach ($this -> children as $subNode) {
            $xmlString .= $subNode -> toXml($fmt, 0);
        }
        return $xmlString;
    }
}

class XmlElementAttribute {
    var $name;
    var $namespace;
    var $value;
        
    function toString() {
        return '[' . $this -> name . ($this -> namespace ? '(ns=' . $this -> namespace . ')' : '')
            . ']=' . $this -> value;
    }
    
    function toXml(&$format) {
        return $format -> getNamespacePrefix($this -> namespace)
            . $this -> name . '="' . htmlentities($this -> value) . '"';
    }
}

class XmlElement extends XmlRoot {
    var $_attrs = array();              // array[i] of XmlElementAttribute
    var $attributes;                    // array[attr_name]=(array[ns]=value)
    var $element;
    var $namespace;
    
    function __construct($appData = null) {
        parent::__construct($appData);
    }
    
    function XmlElement($appData = null) {
        $this -> __construct($appData);
    }

    function getAttribute($attrName, $default = null, $namespace = null) {
        if (isset($this -> attributes[$attrName])) {
            if (is_null($namespace)) {
                // Return the first element
                reset($this -> attributes[$attrName]);
                $attr = current($this -> attributes[$attrName]);
                return $attr -> value;
            } else if (isset($this -> attributes[$attrName][$namespace])) {
                return $this -> attributes[$attrName][$namespace] -> value;
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }
    
    function &getFirstChildElementByName($name, $namespace = null) {
        if (count($this -> children) == 0) {
            return null;
        }
        for ($ind = 0; $ind < count($this -> children); $ind++) {
            if ($this -> children[$ind] instanceof XmlElement) {
                if ($this -> children[$ind] -> element == $name
                    && (is_null($namespace) || $this -> children[$ind] -> namespace == $namespace)) {
                    return $this -> children[$ind];
                }
            }
        }
        return null;
    }
    
    function &getNextChildElementByName($name, &$start, $namespace = null) {
        if (count($this -> children) == 0) {
            return null;
        }
        $walk = 0;
        if (! is_null($start)) {
            foreach ($this -> children as $ind => $kid) {
                if ($this -> children[$ind] === $start) {
                    $walk = $ind;
                    break;
                }
            }
        }
        for ($ind = $walk; $ind < count($this -> children); $ind++) {
            if ($this -> children[$ind] instanceof XmlElement) {
                if ($this -> children[$ind] -> element == $name
                    && (is_null($namespace) || $this -> children[$ind] -> namespace == $namespace)) {
                    return $this -> children[$ind];
                }
            }
        }
        return null;
    }
    
    function parserAttributes($attrList) {
        $this -> _attrs = array();
        $this -> attributes = array();
        foreach ($attrList as $name => $value) {
            $newAttr = new XmlElementAttribute();
            $newAttr -> value = $value;
            if (($posn = strrpos($name, ':')) === false) {
                $newAttr -> namespace = '';
                $newAttr -> name = $name;
            } else {
                $newAttr -> namespace = substr($name, 0, $posn);
                $name = substr($name, $posn + 1);
                $newAttr -> name = $name;
            }
            $this -> _attrs[] = &$newAttr;
            if (! isset($this -> attributes[$name])) {
                $this -> attributes[$name] = array();
            }
            $this -> attributes[$name][$newAttr -> namespace] = &$newAttr;
            unset($newAttr);
        }
    }
    
    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Element: ';
        }
        $s .= $this -> element . ' ' . count($this -> children) . ' children';
        foreach ($this -> _attrs as $attr) {
            $s .= '  ' . $attr -> toString();
        }
        return $s;
    }

    function toXml(&$format, $depth) {
        $s = $format -> getIndent($depth);
        $s .= '<' . $format -> getNamespacePrefix($this -> namespace) . $this -> element;
        $s .= $format -> getNamespaceXml();
        if ($this -> _attrs) {
            foreach ($this -> _attrs as $attr) {
                $s .= ' ' . $attr -> toXml($format);
            }
        }
        ++$depth;
        if (count($this -> children)) {
            $s .= '>';
            $firstNode = true;
            foreach ($this -> children as $subNode) {
                if ($firstNode) {
                    $firstNode = false;
                    if (! $subNode instanceof XmlCdata) {
                        $s .= $format -> eol();
                    }
                }
                $s .= $subNode -> toXml($format, $depth);
            }
            $s .= $format -> getIndent(--$depth) . '</' . $this -> element . '>' . $format -> eol();
        } else {
            $s .= '/>';
            $format -> eatNextIndent = true;
        }
        return $s;
    }
    
}

class XmlExternalRef extends XmlRef {
    var $openEntityNames;

    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Ext Ref:&nbsp;';
        }
        $s .= 'Entity names=' . $this -> openEntityNames;
        return $s;
    }

}

class XmlNamespace extends XmlRoot {
    var $prefix;
    var $def;

    function __construct($appData = null) {
        parent::__construct($appData);
    }
    
    function XmlNamespace($appData = null) {
        $this -> __construct($appData = null);
    }

    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Namespace:&nbsp;';
        }
        $s .= 'prefix=' . $this -> prefix . ' def=' . $this -> def . ' ' . count($this -> children) . ' children';
        return $s;
    }

    function toXml(&$format, $depth) {
        $format -> setNamespace($this);
        $s = '';
        if (count($this -> children)) {
            $needRoot = true;
            foreach ($this -> children as $subNode) {
                if ($needRoot && ($subNode instanceof XmlRoot)) {
                    $needRoot = false;
                    $s .= $subNode -> toXml($format, $depth);
                } else {
                    $s .= $subNode -> toXml($format, $depth);
                }
            }
//        } else {
//            $format -> eatNextIndent = true;
        }
        $format -> clearNamespace($this);
        return $s;
    }
    
}

class XmlNotation extends XmlRef {
    var $name;

    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Notation:&nbsp;';
        }
        $s .= 'name=' . $this -> name;
        return $s;
    }

}

class XmlOther extends XmlNode {
    var $data;

    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Other:&nbsp;';
        }
        $s .= 'data=' . htmlspecialchars($this -> data);
        return $s;
    }

    function toXml(&$format, $depth) {
        $s = $format -> getIndent($depth);
        $s .= $format -> escape($this -> data) . $format -> eol();
        return $s;
    }

}

class XmlPinst extends XmlNode {
    var $data;
    var $target;

    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Pinst:&nbsp;';
        }
        $s .= 'data=' . $this -> data . '&nbsp;target=' . $this -> target;
        return $s;
    }
    
    function toXml(&$format, $depth) {
        return $format -> getIndent($depth) . '<?' . $this -> target. ' ' . $this -> data . '?>'
             . $format -> eol();
    }
        

}

class XmlUnparsedEntity extends XmlNotation {
    var $notationName;

    function toString($tag = true) {
        $s = parent::toString(false);
        if ($tag) {
            $s .= 'Unparsed:&nbsp;';
        }
        $s .= 'name=' . $this -> name;
        return $s;
    }

}

class XmlTree {
    var $_parser;
    var $currElement;                   // Current element
    var $currNode;                      // Current node set when entering an external handler
    var $epilog;                        // Anything following the root
    var $errMsg;                        // Error text
    var $handlers = array();            // Array of secondary event handlers
    var $lastNode;                      // Last parsed node, null on element, ns, etc close
    var $optionSkipWhite = true;        // Skip whitespace when set
    var $prolog;                        // declarations preceeding the root
    var $root;                          // Root of the parsed document
    var $showParse;                     // Output parse events
    
    function cdata($parser, $text) {
        if ($this -> showParse) {
            echo 'cdata (' . strlen($text) . ') "' . htmlspecialchars($text) . '" ';
        }
        if ($this -> lastNode instanceof XmlCdata) {
            $node = &$this -> lastNode;
            $saveIt = false;
        } else {
            $node = new XmlCdata();
            $node -> text = '';
            $saveIt = true;
        }
        if ($this -> showParse) {
            echo ($saveIt ? 'create' : 'append') . ' node=' . $node -> _xmlNodeID;
        }
        if ($this -> optionSkipWhite) {
            $text = $this -> stripWhite($text);
        }
        if ($text != '') {
            //
            // Eliminate two whitespace chatacters when combining cdata nodes
            //
            if ($this -> optionSkipWhite && $text{0} == ' ' && strlen($node -> text)
                && $node -> text{strlen($node -> text) - 1} == ' ') {
                $node -> text .= substr($text, 1);
            } else {
                $node -> text .= $text;
            }
            //
            // Eliminate stand-alone whitespace
            //
            if ($saveIt && $this -> optionSkipWhite && $text == ' ') {
                if ($this -> currElement) {
                    $slot = count($this -> currElement -> children) - 1;
                    if ($slot < 0) {
                        $saveIt = false;
                    } else {
                        if (get_class($this -> currElement -> children[$slot]) != 'xmlcdata') {
                            $saveIt = false;
                        }
                    }
                } else if ($this -> root) {
                    // checking the epilog
                    $slot = count($this -> epilog) - 1;
                    if ($slot < 0) {
                        $saveIt = false;
                    } else {
                        if (get_class($this -> epilog) != 'xmlcdata') {
                            $saveIt = false;
                        }
                    }
                } else {
                    // Checking the prolog
                    $slot = count($this -> prolog) - 1;
                    if ($slot < 0) {
                        $saveIt = false;
                    } else {
                        if (get_class($this -> prolog[$slot]) != 'xmlcdata') {
                            $saveIt = false;
                        }
                    }
                }
            }
            if ($saveIt) {
                $this -> nodeSave($node);
            }
        }
        if ($this -> showParse) {
            echo ' u=' . ($node -> parent ? $node -> parent -> _xmlNodeID : 'null') . '<br/>';
        }
    }
    
    function defaultData($parser, $data) {
        if ($this -> showParse) {
            echo 'default (' . strlen($data) . ') "' . htmlspecialchars($data) . '" ';
        }
        if (false && ($this -> lastNode instanceof XmlOther)) {
            $node = &$this -> lastNode;
            $saveIt = false;
        } else {
            $node = new XmlOther();
            $node -> data = '';
            $saveIt = true;
        }
        if ($this -> showParse) {
            echo ($saveIt ? 'create' : 'append') . ' node=' . $node -> _xmlNodeID;
        }
        if ($this -> optionSkipWhite) {
            $data = $this -> stripWhite($data);
        }
        if ($data != '') {
            if ($this -> optionSkipWhite && $data{0} == ' ' && strlen($node -> data)
                && $node -> data{strlen($node -> data) - 1} == ' ') {
                $node -> data .= substr($data, 1);
            } else {
                $node -> data .= $data;
            }
            if ($saveIt) {
                $this -> nodeSave($node);
            }
        }
        if ($this -> showParse) {
            echo ' u=' . ($node -> parent ? $node -> parent -> _xmlNodeID : 'null') . '<br/>';
        }
    }
    
    function elementClose($parser, $element) {
        global $globalXmlNullRef;
        
        // probably need some validation here
        if ($this -> showParse) {
            echo 'element close "' . $element . '" ce(in)=' 
                . ($this -> currElement ? $this -> currElement -> _xmlNodeID : 'null') . ' ';
        }
        $ignoreNode = false;
        if (isset($this -> handlers['elementclose'])) {
            if (call_user_func($this -> handlers['elementclose']) === false) {
                // The handler doesn't want this element in the tree...
                $ignoreNode = true;
            }
        }
        if ($ignoreNode) {
            if ($this -> currElement -> parent) {
                $parent = &$this -> currElement -> parent;
                $parent -> childKill($this -> currElement);
            } else {
                $parent = &$globalXmlNullRef;
            }
            $this -> currElement -> delink();
            $this -> currElement = &$parent;
        } else if (! is_null($this -> currElement)) {
            $this -> currElement = &$this -> currElement -> parent;
        }
        $this -> lastNode = &$globalXmlNullRef;
        if ($this -> showParse) {
            echo 'ce(out)=' . ($this -> currElement ? $this -> currElement -> _xmlNodeID : 'null') . ' ';
            echo '<br/>';
        }
    }
    
    function elementOpen($parser, $element, $attr) {
        global $globalXmlNullRef;

        $child = new XmlElement();
        if ($this -> showParse) {
            echo 'element open "' . $element . '" (' . count($attr) . ' attr) ';
            echo 'nodeID=' . $child -> _xmlNodeID . ' ce=';
            echo is_null($this -> currElement) ? 'null' : $this -> currElement -> _xmlNodeID . ' ';
        }
        if (($posn = strrpos($element, ':')) === false) {
            $child -> namespace = '';
            $child -> element = $element;
        } else {
            $child -> namespace = substr($element, 0, $posn);
            $child -> element = substr($element, $posn + 1);
        }
        $child -> parserAttributes($attr);
        if (! is_null($this -> currElement)) {
            $this -> currElement -> childAppend($child);
        } else {
            $child -> parent = &$globalXmlNullRef;
            $this -> root = &$child;
        }
        $this -> lastNode = &$child;
        $this -> currNode = &$child;
        if (isset($this -> handlers['elementopen'])) {
            call_user_func($this -> handlers['elementopen']);  
        }
        $this -> currElement = &$child;
        if ($this -> showParse) {
            echo ' On exit: '. $child -> toDump();
            echo ' u=' . ($child -> parent ? $child -> parent -> _xmlNodeID : 'null');
            echo ' ce='  . ($this -> currElement ? $this -> currElement -> _xmlNodeID : 'null');
            echo '<br/>';
        }
    }
    
    function externalRef($parser, $openNames, $base, $sid, $pid) {
        if ($this -> showParse) {
            echo 'extref ' . $base . ' ' . $openNames . ' ' . $sid . ' ' . $pid . '<br/>';
        }
        $node = new XmlExternalRef();
        $node -> openEntityNames = $openNames;
        $node -> base = $base;
        $node -> publicID = $pid;
        $node -> systemID = $sid;
        $this -> nodeSave($node);
    }

    function initParser() {
        global $globalXmlNullRef;
        
        $this -> _parser = xml_parser_create_ns();
        //
        // Set options
        //
        xml_parser_set_option($this -> _parser, XML_OPTION_CASE_FOLDING, false);
        // NB: we do our own whitespace elimination
        xml_parser_set_option($this -> _parser, XML_OPTION_SKIP_WHITE, false); //$this -> optionSkipWhite);
        //
        // Parser callback functions
        //
        xml_set_object($this -> _parser, $this);
        xml_set_character_data_handler($this -> _parser, 'cdata');
        xml_set_default_handler($this -> _parser, 'defaultData');
        xml_set_element_handler($this -> _parser, 'elementOpen', 'elementClose');
        xml_set_end_namespace_decl_handler($this -> _parser, 'namespaceEnd');
        xml_set_external_entity_ref_handler($this -> _parser, 'externalRef');
        xml_set_notation_decl_handler($this -> _parser, 'notation');
        xml_set_start_namespace_decl_handler($this -> _parser, 'namespaceStart');
        xml_set_processing_instruction_handler ($this -> _parser, 'processInstruction');
        xml_set_unparsed_entity_decl_handler($this -> _parser, 'unparsedEntity');
        $this -> currElement = &$globalXmlNullRef;
    }
    
    function namespaceEnd($parser, $prefix) {
        global $globalXmlNullRef;
        
        if ($this -> showParse) {
            echo 'namespace end ' . htmlspecialchars($prefix) . '<br/>';
        }
        if (! is_null($this -> currElement)) {
            if ($this -> currElement -> parent) {
                $this -> currElement = &$this -> currElement -> parent;
            }
        }
        $this -> lastNode = &$globalXmlNullRef;
    }
    
    function namespaceStart($parser, $prefix, $def) {
        if ($this -> showParse) {
            echo 'namespace start ' . htmlspecialchars($prefix) . ' = ' . htmlspecialchars($def) . '<br/>';
        }
        $child = new XmlNamespace();
        $child -> prefix = $prefix;
        $child -> def = $def;
        if (! is_null($this -> currElement)) {
            $this -> currElement -> children[] = &$child;
            $child -> parent = &$this -> currElement;
        } else {
            $child -> parent = null;
            $this -> root = &$child;
        }
        $this -> currElement = &$child;
    }
    
    function nodeSave(&$node) {
        if (! is_null($this -> currElement)) {
            $this -> currElement -> childAppend($node);
        } else if (! is_null($this -> root)) {
            $this -> epilog[] = &$node;
        } else {
            $this -> prolog[] = &$node;
        }
        $this -> lastNode = &$node;
    }
    
    function notation($parser, $name, $base, $sid, $pid) {
        if ($this -> showParse) {
            echo 'notation ' . $base . ' ' . $name . ' ' . $sid . ' ' . $pid . '<br/>';
        }
        $node = new XmlNotation();
        $node -> base = $base;
        $node -> name = $name;
        $node -> notationName = $name;
        $node -> publicID = $pid;
        $node -> systemID = $sid;
        $this -> nodeSave($node);
    }
    
    function parseBuffer($buffer) {
        global $globalXmlNullRef;
        
        $this -> initParser();
        if (! xml_parse($this -> _parser, $buffer)) {
            $this -> parserError();
            return false;
        }
        xml_parser_free($this -> _parser);
        $this -> currElement = &$globalXmlNullRef;
        return true;
    }
    
    function parseFile($file) {
        global $globalXmlNullRef;
        
        $this -> initParser();
        if (!($fp = @fopen($file, "r"))) {
            $this -> errMsg = 'Error opening ' . $file;
            return false;
        }
        while ($data = @fread($fp, 4096)) {
            if (! xml_parse($this -> _parser, $data, feof($fp))) {
                $this -> parserError();
                return false;
            }
        }
        xml_parser_free($this -> _parser);
        $this -> currElement = &$globalXmlNullRef;
        return true;
    }
    
    function parserError() {
        $this -> errMsg = 'XML error: ' . xml_error_string(xml_get_error_code($this -> _parser))
            . ' on line ' . xml_get_current_line_number($this -> _parser) . ' col '
            . xml_get_current_column_number($this -> _parser);
    }

    function processInstruction($parser, $target, $data) {
        if ($this -> showParse) {
            echo 'procinst ' . $target . ' (' . count($data) . ') ' . htmlspecialchars($data) . '<br/>';
        }
        $node = new XmlPinst();
        $node -> target = $target;
        $node -> data = $data;
        $this -> nodeSave($node);
    }
    
    function setHandler($name, $handler) {
        $name = strtolower($name);
        if ($handler == '') {
            unset($this -> handlers[$name]);
        } else {
            $this -> handlers[$name] = $handler;
            $this -> handlers[$name][0] = &$handler[0];
        }
    }
    
    function stripWhite($text) {
        $text = str_replace(array("\t", "\n", "\r"), ' ', $text);
        //$leading = ($text{0} == ' ') ? ' ' : '';
        //$text = trim($text);
        do {
            $oldLen = strlen($text);
            $text = str_replace('  ', ' ', $text);
        } while (strlen($text) != $oldLen);
        return $text;
    }

    function toString($indentSize = 3) {
        $depth = 0;
        $indent = '';
        $s = '';
        if (count($this -> prolog)) {
            $s .= 'Preamble<br/>';
            foreach ($this -> prolog as $node) {
                $s .= $this -> toStringNode($node, $depth, $indentSize);
            }
        }
        $s .= 'Root node<br/>';
        if (! is_null($this -> root)) {
            $s .= $this -> toStringNode($this -> root, $depth, $indentSize);
        }
        //
        if (count($this -> epilog)) {
            $s .= 'Postamble<br/>';
            foreach ($this -> epilog as $node) {
                $s .= $this -> toStringNode($node, $depth, $indentSize);
            }
        }
        return $s;
    }
    
    function toStringNode($node, $depth, $indentSize) {
        $s = str_pad('', $depth * $indentSize, ' ');
        $s = str_replace(' ', '&nbsp;', $s);
        $s .= $node -> toString() . '<br/>';
        if ($node instanceof XmlRoot) {
            ++$depth;
            if (count($node -> children)) {
                foreach ($node -> children as $subNode) {
                    $s .= $this -> toStringNode($subNode, $depth, $indentSize);
                }
            }
        }
        return $s;
    }
    
    function toXml(&$format) {
        $depth = 0;
        $s = '';
        if (count($this -> prolog)) {
            foreach ($this -> prolog as $node) {
                $s .= $node -> toXml($format, $depth);
            }
        }
        if (! is_null($this -> root)) {
            $s .= $this -> root -> toXml($format, $depth);
        }
        //
        if (count($this -> epilog)) {
            foreach ($this -> epilog as $node) {
                $s .= $node -> toXml($format, $depth);
            }
        }
        return $s;
    }
    
    function unparsedEntity($parser, $name, $base, $sid, $pid, $notation) {
        if ($this -> showParse) {
            echo 'unparsed ' . $base . ' ' . $name . ' ' . $sid . ' ' . $pid . ' ' . $notation . '<br/>';
        }
        $node = new XmlUnparsedEntity();
        $node -> base = $base;
        $node -> name = $name;
        $node -> notationName = $notation;
        $node -> publicID = $pid;
        $node -> systemID = $sid;
        $this -> nodeSave($node);
    }
    
}

?>