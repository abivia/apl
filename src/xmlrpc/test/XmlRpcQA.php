<?php
/**
 * XmlRpc QA tests. Validate what we can without a client/server.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: XmlRpcQA.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('lang/lib.php');
require_once('xmlrpc/XmlRpc.php');

$headers = array();
$tests = 0;
$fails = 0;

function emitHeaders() {
    global $headers;

    for ($indx = 1; isset($headers[$indx]); ++$indx) {
        if ($headers[$indx] != '') {
            echo '<h' . $indx . '>' . $headers[$indx] . '</h' . $indx . '>' . chr(10);
            $headers[$indx] = '';
        }
    }
}

function head($level, $text) {
    global $errsOnly;
    global $headers;

    if ($errsOnly) {
        $headers[$level] = $text;
        for ($indx = $level; $indx > 0; --$indx) {
            if (! isset($headers[$indx])) {
                $headers[$indx] = '';
            }
        }
        $indx = $level + 1;
        while (isset($headers[$indx])) {
            $headers[$indx++] = '';
        }
    } else {
        echo '<h' . $level . '>' . $text . '</h' . $level . '>' . chr(10);
    }
}

function test($name, $pass, $failMsg = '') {
    global $errsOnly;
    global $fails;
    global $tests;

    $tests++;
    if ($errsOnly) {
        if($pass) return $pass;
        emitHeaders();
    }
    
    echo '<span style="color:#0000cc">' . $name . ':</span> ';
    if ($pass) {
        echo ' <span style="color:#00cc00">passed</span>';
    } else {
        echo ' <span style="color:#ff3333">FAILED</span> '. $failMsg;
        $fails++;
    }
    echo '<br/>';
    return $pass;
}

class X1 {
    var $m1 = '';
    var $m2 = true;
    var $m3 = 3;
    var $m4 = 5.66;
    var $m4 = array(1, 2, 3);
}

class X2 {
    var $m1 = 'instance of X2';
    var $m2 = array();
}
    

function qa_message() {
    
    head(2, 'XmlRpcMessage');
    head(3, 'Complex Structure');
    $x2 = new X2();
    $x2 -> m2[] = new X1;
    $x2 -> m2[] = new X1;
    $request = new XmlRpcRequest('fred', array($x2));
    $actual = $request -> getXml();
    $expected = '<?xml version="1.0"?>
<methodCall><methodName>fred</methodName><params><param><value><struct class="php:x2"><member><name>m1</name><value><string>instance of X2</string></value></member><member><name>m2</name><value><array><data><value><struct class="php:x1"><member><name>m1</name><value><string></string></value></member><member><name>m2</name><value><boolean>1</boolean></value></member><member><name>m3</name><value><int>3</int></value></member><member><name>m4</name><value><array><data><value><int>1</int></value><value><int>2</int></value><value><int>3</int></value></data></array></value></member></struct></value><value><struct class="php:x1"><member><name>m1</name><value><string></string></value></member><member><name>m2</name><value><boolean>1</boolean></value></member><member><name>m3</name><value><int>3</int></value></member><member><name>m4</name><value><array><data><value><int>1</int></value><value><int>2</int></value><value><int>3</int></value></data></array></value></member></struct></value></data></array></value></member></struct></value></param></params></methodCall>';
    $expected = str_replace(chr(13), '', $expected);
    test('Message (No EOL)', $actual == $expected,
        'expected (' . strlen($expected) . '):<pre>' . htmlentities($expected) . '</pre><br/><br/>'
        . 'Got (' . strlen($actual) . '):<pre>' . htmlentities($actual) . '</pre><br/>');
    $msg = new XmlRpcMessage($actual);
    $msg -> parse();
    $fail = objectCompare($x2, $msg -> params, true);
    test('Object (No EOL)', ! $fail, $fail);
    if ($fail) {
        echo '<pre>' . print_r($msg, true) . '</pre>';
    }

}

function qa_value() {
    
    head(2, 'XmlRpcValue');
    head(3, 'Complex Structure');
    $x2 = new X2();
    $x2 -> m2[] = new X1;
    $x2 -> m2[] = new X1;
    $xv2 = new XmlRpcValue($x2);
    $actual = $xv2 -> getXml();
    $expected = '<struct class="php:x2"><member><name>m1</name><value><string>instance of X2</string></value></member><member><name>m2</name><value><array><data><value><struct class="php:x1"><member><name>m1</name><value><string></string></value></member><member><name>m2</name><value><boolean>1</boolean></value></member><member><name>m3</name><value><int>3</int></value></member><member><name>m4</name><value><array><data><value><int>1</int></value><value><int>2</int></value><value><int>3</int></value></data></array></value></member></struct></value><value><struct class="php:x1"><member><name>m1</name><value><string></string></value></member><member><name>m2</name><value><boolean>1</boolean></value></member><member><name>m3</name><value><int>3</int></value></member><member><name>m4</name><value><array><data><value><int>1</int></value><value><int>2</int></value><value><int>3</int></value></data></array></value></member></struct></value></data></array></value></member></struct>';
    test('No EOL', $actual == $expected, 'expected:<pre>' . htmlentities($expected)
        . '</pre><br/><br/>Got:<pre>' . htmlentities($actual) . '</pre><br/>');

    $actual = $xv2 -> getXml(chr(10));
    $expected = '<struct class="php:x2">
<member>
<name>m1</name>
<value>
<string>instance of X2</string>
</value>
</member>
<member>
<name>m2</name>
<value>
<array><data>
<value><struct class="php:x1">
<member>
<name>m1</name>
<value>
<string></string>
</value>
</member>
<member>
<name>m2</name>
<value>
<boolean>1</boolean>
</value>
</member>
<member>
<name>m3</name>
<value>
<int>3</int>
</value>
</member>
<member>
<name>m4</name>
<value>
<array><data>
<value><int>1</int>
</value>
<value><int>2</int>
</value>
<value><int>3</int>
</value>
</data></array>
</value>
</member>
</struct>
</value>
<value><struct class="php:x1">
<member>
<name>m1</name>
<value>
<string></string>
</value>
</member>
<member>
<name>m2</name>
<value>
<boolean>1</boolean>
</value>
</member>
<member>
<name>m3</name>
<value>
<int>3</int>
</value>
</member>
<member>
<name>m4</name>
<value>
<array><data>
<value><int>1</int>
</value>
<value><int>2</int>
</value>
<value><int>3</int>
</value>
</data></array>
</value>
</member>
</struct>
</value>
</data></array>
</value>
</member>
</struct>
';
    $expected = str_replace(chr(13), '', $expected);
    test('EOL', $actual == $expected,
        'expected (' . strlen($expected) . '):<pre>' . htmlentities($expected) . '</pre><br/><br/>'
        . 'Got (' . strlen($actual) . '):<pre>' . htmlentities($actual) . '</pre><br/>');
}


?>
<head>
  <title>XmlRpc QA Test Script</title>
</head>
<body>
<?php

$errsOnly = isset($_REQUEST['eo']);
head(1, 'XmlRpc QA Tests');
if (! $errsOnly) {
    echo ('add "eo" to URL for errors only report');
}
//
// QA Test routines
//
error_reporting(E_ALL);
qa_value();
qa_message();
//
// Summary
//
if ($errsOnly) {
    echo $fails . '<br/>' . chr(10);
}
echo '<h1>Results</h1>';
echo 'Errors only mode: ' . ($errsOnly ? 'on' : 'off') . '<br/>';
echo $tests . ' tests, ' . $fails . ' failures.<br/>';

?>