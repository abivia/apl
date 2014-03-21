<?php echo '<?xml version="1.0" encoding="utf-8" ?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title>XmlTree Unit Test Script</title>
  <style type="text/css">
  * { font-family: Arial, Verdana, Helvetica, sans-serif }
  </style>
 </head>
 <body>
<?php

require_once('../XmlTree.php');

class FilterTree extends XmlTree {
    var $ignoreTags;
    
    function elementFilter() {
        return ! in_array($this -> currElement -> element, $this -> ignoreTags);
    }
    
    function init() {
        $this -> ignoreTags = array('title', 'selfclose');
        $this -> setHandler('elementclose', array(&$this, 'elementFilter'));
    }
}

/*
 * A simple tracking structure to verify a parsed tree against expectations.
 */
class ExpectNode {
    var $children = array();
    var $className;
    var $elementName;
    
    function __construct($cname, $ename = null) {
        $this -> className = $cname;
        $this -> elementName = $ename;
    }
    
    function ExpectNode($cname, $ename = null) {
        $this -> __construct($cname, $ename);
    }
    
    function &addChild($cname, $ename = null) {
        $kid = new ExpectNode($cname, $ename);
        $this -> children[] = &$kid;
        return $kid;
    }
    
    function match($node) {
        $nc = get_class($node);
        if ($nc != strtolower($this -> className)) {
            return 'Expected ' . $this -> className 
                . ($this -> className == 'XmlElement' ? ' / ' . $this -> elementName : '')
                . ' got ' . $nc . ($nc == 'xmlelement' ? ' / ' . $node -> element : '');
        }
        if ($this -> className == 'xmlelement' && $this -> elementName != $node -> element) {
            return 'Expected element ' . $this -> elementName . ' got ' . $node -> element;
        }
        if ($node instanceof XmlRoot) {
            if (count($this -> children) != count($node -> children)) {
                return 'Element ' . $this -> elementName . ' expected ' . count($this -> children)
                    . ' children, got ' . count($node -> children);
            }
            foreach($this -> children as $key => $greatExpectation) {
                if (($msg = $greatExpectation -> match($node -> children[$key])) != '') {
                    return $msg;
                }
            }
        }
        return '';
    }
    
}

$testXml = '<?xml version="1.0" ?>' . chr(10);
$testXml .= '<?xml-transform type="text/xsl" href="cdcatalog.xsl"?>' . chr(10);
$testXml .= '<!-- example transform ref: <?xml-transform type="text/xsl" href="cdcatalog.xsl"?> -->' . chr(10);
$testXml .= '<pagedefs xmlns:nst="http://foo.org/foospec">' . chr(10);
$testXml .= '    Char data seems to be a problem again.' . chr(10);
$testXml .= '    <pagedef name="mainsite" nst:theme="corp">' . chr(10);
$testXml .= '        <!-- Menus -->' . chr(10);
$testXml .= '        <buildmenu name="topnav" />' . chr(10);
$testXml .= '        <buildmenu name="leftnav" />' . chr(10);
$testXml .= '        <!-- Page content, starting with callouts -->' . chr(10);
$testXml .= '        <menu name="topnav">' . chr(10);
$testXml .= '            <title>Consulting Stuff</title>' . chr(10);
$testXml .= '        </menu>' . chr(10);
$testXml .= '        <selfclose test="prune"/>' . chr(10);
$testXml .= '        <stuff>Now here is a bunch' . chr(10);
$testXml .= '        of text on multiple lines for testing' . chr(10);
$testXml .= '        whitespace     elimination' . chr(10);
$testXml .= '        </stuff>' . chr(10);
$testXml .= '        <stuff>looking for embedded cdata: <test1/>&amp;nbsp;<test2/> within the test markers.</stuff>';
$testXml .= '        more cdata after stuff' . chr(10);
$testXml .= '        <?foo multiple lines' . chr(10);
$testXml .= '        of foo' . chr(10);
$testXml .= '        for you!?>' . chr(10);
$testXml .= '    </pagedef>' . chr(10);
$testXml .= '</pagedefs>' . chr(10);

$fullRoot = new ExpectNode('XmlNamespace');
$page = &$fullRoot -> addChild('XmlElement', 'pagedefs');
$page -> addChild('XmlCdata');
$def = &$page -> addChild('XmlElement', 'pagedef');
$def -> addChild('XmlOther');
$def -> addChild('XmlElement', 'buildmenu');
$def -> addChild('XmlElement', 'buildmenu');
$def -> addChild('XmlOther');
$menu = &$def -> addChild('XmlElement', 'menu');
$leaf = &$menu -> addChild('XmlElement', 'title');
$leaf -> addChild('XmlCdata');
unset($leaf);
unset($menu);
$def -> addChild('XmlElement', 'selfclose');
$leaf = &$def -> addChild('XmlElement', 'stuff');
$leaf -> addChild('XmlCdata');
unset($leaf);
$leaf = &$def -> addChild('XmlElement', 'stuff');
$leaf -> addChild('XmlCdata');
$leaf -> addChild('XmlElement', 'test1');
$leaf -> addChild('XmlCdata');
$leaf -> addChild('XmlElement', 'test2');
$leaf -> addChild('XmlCdata');
unset($leaf);
$def -> addChild('XmlCdata');
$def -> addChild('XmlPinst');
unset($def);
unset($page);
$fullRoot -> addChild('XmlOther');

$filterRoot = new ExpectNode('XmlNamespace');
$page = &$filterRoot -> addChild('XmlElement', 'pagedefs');
$page -> addChild('XmlCdata');
$def = &$page -> addChild('XmlElement', 'pagedef');
$def -> addChild('XmlOther');
$def -> addChild('XmlElement', 'buildmenu');
$def -> addChild('XmlElement', 'buildmenu');
$def -> addChild('XmlOther');
$menu = &$def -> addChild('XmlElement', 'menu');
unset($menu);
$leaf = &$def -> addChild('XmlElement', 'stuff');
$leaf -> addChild('XmlCdata');
unset($leaf);
//$def -> addChild('XmlCdata');
$leaf = &$def -> addChild('XmlElement', 'stuff');
$leaf -> addChild('XmlCdata');
$leaf -> addChild('XmlElement', 'test1');
$leaf -> addChild('XmlCdata');
$leaf -> addChild('XmlElement', 'test2');
$leaf -> addChild('XmlCdata');
unset($leaf);
$def -> addChild('XmlCdata');
$def -> addChild('XmlPinst');
unset($def);
unset($page);
$filterRoot -> addChild('XmlOther');

$tests = 0;
$fails = 0;

function test($name, $pass, $failMsg = '') {
    global $fails;
    global $tests;

    echo '<span style="color:#0000cc">' . $name . ':</span> ';
    if ($pass) {
        echo ' <span style="color:#00cc00">passed</span>';
    } else {
        echo ' <span style="color:#ff3333">FAILED</span> '. $failMsg;
        $fails++;
    }
    $tests++;
    echo '<br/>';
    return $pass;
}

function selfTest($file = '') {
    global $fails;
    global $filterRoot;
    global $fullRoot;
    global $testXml;
    global $tests;
    
    echo '<h1>XmlTree Unit Test Suite</h1>';
    //
    // Formatting tests
    //
    echo '<h2>XmlFormatInfo tests:</h2>';    
    $format = new XmlFormatInfo();
    // GetIndent
    $work = $format -> getIndent(2);
    test('getIndent', ($work == '    '), 'strlen=' . strlen($work) . 'text="' . $work . '"');
    
    // Text wrap
    $preWrap = 'a23456789 b23456789 c23456789 d23456789 e23456789 f23456789 g23456789 h234567890123456789';
    $preWrap .= ' a234567890b234567890c234567890d234567890e234567890f234567890g234567890123456789 foo';
    $postWrap = 'a23456789 b23456789 c23456789 d23456789 e23456789 f23456789 g23456789<br/>h234567890123456789';
    $postWrap .= '<br/>a234567890b234567890c234567890d234567890e234567890f234567890g234567890123456789<br/>foo<br/>';
    $format -> escape = true;
    $fmt = $format -> wrap($preWrap);
    $format -> escape = false;
    test('Text wrap', $fmt == $postWrap, '<pre>' . $postWrap . '</pre>Got:<pre>' . $fmt . '</pre>');
    //
    // Node tests
    //
    $testRoot = new XmlRoot();
    echo '<h2>XmlNode (and derived classes) tests:</h2>';
    $head = new XmlNode(0);
    $testRoot -> childAppend($head);
    echo 'head has appdata=' . $head -> appData . ' ' . $head -> toDump() . '<br/>';

    $tn = new XmlNode(300);
    echo 'new node has appdata=' . $tn -> appData . ' ' . $tn -> toDump() . '<br/>';
    $head -> linkAfter($tn);
    echo 'head post-link:' . $head -> toDump() . '<br/>';
    echo 'new node post-link:' . $tn -> toDump() . '<br/>';
    $ref = $head -> getNext();
    test('linkAfter/getNext', $ref -> appData == 300,
        'node is wrong, appdata=' . $ref -> appData);
    $ref = $tn -> getPrev();
    test('linkAfter/getPrev', $ref -> appData == 0,
        'node is wrong, appdata=' . $ref -> appData);
    unset($tn);

    $tn = new XmlNode(200);
    echo 'new node has appdata=' . $tn -> appData . ' nodeid=' . $tn -> _xmlNodeID . '<br/>';
    $head -> linkAfter($tn);
    $ref = $head -> getNext();
    test('linkAfter/getNext(2)', $ref -> appData == 200,
        'Head Next node is wrong, nodeid=' . $ref -> _xmlNodeID
        . ' appdata(200)=' . $ref -> appData);
    $ref = $ref -> getNext();
    test('linkAfter/Next links', $ref -> appData == 300,
        'Head Next**2 node is wrong, nodeid=' . $ref -> _xmlNodeID
        . ' appdata(300)=' . $ref -> appData);
    unset($tn);
    
    $tn = new XmlNode(100);
    echo 'new node has appdata=' . $tn -> appData . ' nodeid=' . $tn -> _xmlNodeID . '<br/>';
    $head -> linkAfter($tn);
    $ref = $head -> getNext();
    test('getNext', $ref -> appData == 100, 'Next node is wrong, appdata=' . $ref -> appData);

    echo '<h3>Forward links</h3>';
    $walk = $head;
    for ($expect = 0; $expect < 400; $expect+=100) {
        if (is_null($walk)) {
            test('Forward walk', false, 'walk is null when expecting ' . $expect);
        } else {
            test('Forward walk', $walk -> appData == $expect, 'expected ' . $expect . ', got ' . $walk -> appData);
            $tail = $walk;
            $walk = $walk -> getNext();
        }
    }
    test('Forward walk', is_null($walk), 'walk is not null.');
    echo '<h3>Backward links</h3>';
    $walk = $tail;
    for ($expect = 300; $expect >= 0; $expect-=100) {
        if (is_null($walk)) {
            test('Backward walk', false, 'walk is null when expecting ' . $expect);
        } else {
            test('Backward walk', $walk -> appData == $expect, 'expected ' . $expect . ', got ' . $walk -> appData);
            $walk = $walk -> getPrev();
        }
    }
    test('Backward walk', is_null($walk), 'walk is not null.');
    
    $ref = $head -> getNext();
    $ref = $ref -> getNext();
    $ref -> delink();
    echo '<h3>Delink forward links</h3>';
    $walk = $head;
    for ($expect = 0; $expect < 400; $expect+=100) {
        if (is_null($walk)) {
            test('Forward delink', false, 'walk is null when expecting ' . $expect);
        } else if ($expect == 200) {
            // delinked node
        } else {
            test('Forward delink', $walk -> appData == $expect, 'expected ' . $expect . ', got ' . $walk -> appData);
            $tail = $walk;
            $walk = $walk -> getNext();
        }
    }
    test('Forward delink', is_null($walk), 'walk is not null.');
    echo '<h3>Delink backward links</h3>';
    $walk = $tail;
    for ($expect = 300; $expect >= 0; $expect-=100) {
        if (is_null($walk)) {
            test('Backward delink', false, 'walk is null when expecting ' . $expect);
        } else if ($expect == 200) {
            // delinked node
        } else {
            test('Backward delink', $walk -> appData == $expect, 'expected ' . $expect . ', got ' . $walk -> appData);
            $walk = $walk -> getPrev();
        }
    }
    test('Backward delink', is_null($walk), 'walk is not null.');
    unset($head);

    echo '<h3>Child Append</h3>';
    $root = new XmlRoot();
    
    $tn = new XmlNode(100);
    $root -> childAppend($tn);
    unset($tn);
    
    $midn = new XmlNode(200);
    $root -> childAppend($midn);
    
    $tn = new XmlNode(300);
    $root -> childAppend($tn);
    unset($tn);
    
    $tn = new XmlNode(400);
    $root -> childAppend($tn);
    unset($tn);
    
    test('Child count', count($root -> children) == 4, ' count=' . count($root -> children));
    foreach ($root -> children as $key => $child) {
        test('Parent', $child -> parent -> _xmlNodeID == $root -> _xmlNodeID,
            'Child ' . $key . ' has wrong parent.');
        if ($key == 0) {
            test('Child prev', is_null($root -> children[0] -> getPrev()), 'child[0] -> prev != null');
        } else {
            $ref = $child -> getPrev();
            test('Child prev', $root -> children[$key - 1] -> _xmlNodeID == $ref -> _xmlNodeID,
                'child[$key] -> prev != child[$key - 1]');
        }
        if ($key < count($root -> children) - 1) {
            $ref = $child -> getNext();
            test('Child next', $root -> children[$key + 1] -> _xmlNodeID == $ref -> _xmlNodeID,
                'child[$key] -> next != child[$key + 1]');
        } else {
            test('Child next', is_null($root -> children[$key] -> getNext()),
                'child[' . $key . '] -> next != null');
        }
    }
    
    echo '<h3>Child Kill</h3>';
    echo 'Start with ' . count($root -> children) . ' child nodes<br/>';
    $root -> childKill($midn);
    test('Child kill', count($root -> children) == 3, ' Wrong child count: ' . count($root -> children));
    foreach ($root -> children as $key => $child) {
        test('Child kill prev', $child -> parent -> _xmlNodeID == $root -> _xmlNodeID, 'Child ' . $key . ' has wrong parent.');
        if ($key == 0) {
            test('Child kill prev', is_null($root -> children[0] -> getPrev()), 'child[0] -> prev != null');
        } else {
            $ref = $child -> getPrev();
            test('Child kill prev', $root -> children[$key - 1] -> _xmlNodeID == $ref -> _xmlNodeID,
                'child[' . $key . '] -> prev != child[' . $key - 1 . ']');
        }
        if ($key < count($root -> children) - 1) {
            $ref = $child -> getNext();
            test('Child kill next', $root -> children[$key + 1] -> _xmlNodeID == $ref -> _xmlNodeID,
                'child[' . $key . '] -> next != child[' . $key + 1 . ']');
        } else {
            test('Child kill next', is_null($root -> children[$key] -> getNext()), 'child[' . $key . '] -> next != null');
        }
    }
    
    //---------------------------------------------------------------------------------
    //
    // XmlTree tests
    //
    //---------------------------------------------------------------------------------
    echo '<h2>XmlTree tests:</h2>';
    $p = new XmlTree();
    $p -> showParse = false;
    $p -> optionSkipWhite = true;
    test('Parse buffer', $p -> parseBuffer($testXml), $p -> errMsg);
    //
    // Compare the structure of the root
    //
    $msg = $fullRoot -> match($p -> root);
    test('Parsed structure', $msg == '', 'Parsed structure: ' . $msg);
    //
    // Since we know the contents of the buffer, perform additional checks
    //
    $xns = &$p -> root;
    test('Namespace node', ! is_null($xns), 'Namespace not found.');
    if ($xns) {
        $pagedefs = $xns -> getFirstChild();
        test('getFirstChild', ! is_null($pagedefs), 'getFirstChild returned null.');
        if (test('getFirstChild', get_class($pagedefs) == 'xmlelement', 'getFirstChild is a ' . get_class($pagedefs) . ' expected XmlElement')) {
            $page = $pagedefs -> getFirstChildElementByName('pagedef');
            test('getFirstChildElementByName', $page -> element == 'pagedef', 'expected pagedefs, found ' . get_class($page) . ' ' . $page -> element);
        }
    } else {
        $page = null;
    }

    if (! is_null($page)) {
        echo '<h3>Node link tests</h3>';
        $kid2 = &$page -> children[2];
        $kid3 = &$page -> children[3];
        echo 'Node IDs: page=' . $page -> _xmlNodeID;
        echo ' kid2=' . $kid2 -> _xmlNodeID . ' kid3=' . $kid3 -> _xmlNodeID . '<br/>';
        test('Node link, parent (1)', ! is_null($kid3 -> parent), 'kid3 has null parent');

        if (! is_null($kid3 -> parent)) {
            test('Node link, parent (2)', $kid3 -> parent -> equalNodeID($page), 'kid3 has parent ' . $kid3 -> parent -> _xmlNodeID . ' but page has ' . $page -> _xmlNodeID);
        }

        test('Node link, getNext() (1)', ! is_null($kid2 -> getNext()), 'kid2 next is null');

        $ref = $kid2 -> getNext();
        test('Node link, getNext() (2)', $kid3 -> equalNodeID($ref),
            'kid3 has ID ' . $kid3 -> _xmlNodeID . ' but kid2 next is ' . $ref -> _xmlNodeID);

        test('Node link, getPrev() (1)', ! is_null($kid3 -> getPrev()), 'kid3 prev is null');

        $ref = $kid3 -> getPrev();
        test('Node link, getPrev() (2)', $kid2 -> equalNodeID($kid3 -> getPrev()),
            'kid2 has ID ' . $kid2 -> _xmlNodeID . ' but kid3 prev is ' . $ref -> _xmlNodeID);

        echo '<h3>Node attribute tests</h3>';
        test('Attribute "name"', $page -> getAttribute('name') == 'mainsite',
            'Expected name=mainsite, got ' . $page -> getAttribute('name'));
        test('Attribute "theme"', $page -> getAttribute('theme') == 'corp',
            'Expected theme=corp, got ' . $page -> getAttribute('name'));
        test('Attribute "theme" with namespace',
            $page -> getAttribute('theme', null, 'http://foo.org/foospec') == 'corp',
            'Expected nst:theme=corp, got ' 
            . $page -> getAttribute('name', null, 'http://foo.org/foospec'));
    }
    //
    // XmlTree -- FitlerTree tests
    //
    echo '<h3>FilterTree tests:</h3>';
    $pf = new FilterTree();
    $pf -> init();
    $pf -> showParse = false;
    $pf -> optionSkipWhite = true;
    test('Filtered Parse buffer', $pf -> parseBuffer($testXml), 'parse fail: ' . $pf -> errMsg);
    //
    // Compare the structure of the root
    //
    $msg = $filterRoot -> match($pf -> root);
    if (! test('Parsed structure', $msg == '', $msg)) {
        echo $pf -> toString();
    }
    //
    // XmlTree -- Parse file tests
    //
    if ($file) {
        test('Parse file ' . $file, $p -> parseFile($file), $p -> errMsg);
        echo '<br/>';
    }
    echo '<h2>Dump tests</h2><h3>String dump</h3>';
    echo $p -> toString();
    echo '<h3>XML dump:</h3><pre>';
    $newXml = $p -> toXml($format);
    echo htmlentities($newXml);
    echo '</pre>';
}

if (isset($_GET['file'])) {
    $file = $_GET['file'];
} else {
    $file = '';
}
selfTest($file);
echo '<h2>Summary:</h2>' . $tests . ' tests, ' . $fails . ' failures.<br/>';

?>
 </body>
</html>