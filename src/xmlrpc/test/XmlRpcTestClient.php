<?php
/**
 * XmlRpc test client.
 * 
 * Generates calls to the test server and ensures the results are as expected.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: XmlRpcTestClient.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */


require_once('xmlrpc/XmlRpc.php');


$client = new XmlRpcClient('http://localhost/lib/php5/xmlrpc/test/XmlRpcTestServer.php');
$client -> debug = 1;


echo '<hr/>';
$test = 'This is a simple string.';
$result = $client -> call('test', 'echo', $test);
if ($client -> isError()) {
    echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
} else {
    echo ($test == $result[1] ? 'Passed' : 'Failed: ' . htmlentities($test)) . '<br/>';
    print_r($result);
}

echo '<hr/>';
$test = 'This is a string with a less than < in it.';
$result = $client -> call('test', 'echo', $test);
if ($client -> isError()) {
    echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
} else {
    echo ($test == $result[1] ? 'Passed' : 'Failed: ' . htmlentities($test)) . '<br/>';
    print_r($result);
}

echo '<hr/>';
$test = 'This contains � (e acute), &eacute; an HTML only entity.';
$result = $client -> call('test', 'echo', $test);
if ($client -> isError()) {
    echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
} else {
    echo ($test == $result[1] ? 'Passed' : 'Failed: ' . htmlentities($test)) . '<br/>';
    print_r($result);
}

echo '<hr/>';
$test1 = 'String, complex array, string.';
$test2 = array('element zero', array('1.0', '1.1', '1.2'), '0.3');
$test3 = 'Trailing string.';
$expect = array('echo', $test1, $test2, $test3);
$result = $client -> call('test', 'echo', $test1, $test2, $test3);
if ($client -> isError()) {
    echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
} else {
    echo ($expect == $result ? 'Passed' : 'Failed:<br/>' 
        . htmlentities(print_r($expect, true))) . '<br/>';
    print_r($result);
}

echo '<hr/>';
$client -> eol = chr(10);
$test1 = 'With EOL: String, complex array, string.';
$test2 = array('element zero', array('1.0', '1.1', '1.2'), '0.3');
$test3 = 'Trailing string.';
$expect = array('echo', $test1, $test2, $test3);
$result = $client -> call('test', 'echo', $test1, $test2, $test3);
if ($client -> isError()) {
    echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
} else {
    echo ($expect == $result ? 'Passed' : 'Failed:<br/>'
        . htmlentities(print_r($expect, true))) . '<br/>';
    print_r($result);
}
$client -> eol = '';

echo '<hr/>';
$xml = '<info attr="1"><data>This is cdata with an &amp; in it</data></info>' . chr(10);
$result = $client -> call('test', 'xmlarg', $xml);
if ($client -> isError()) {
    echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
} else {
    $serverXml = file_get_contents('xmlarg.xml');
    echo ($xml == $serverXml ? 'Passed' : 'Failed') . '<br/>';
    echo 'Sent:<pre>' . htmlentities($xml) . '</pre>';
    echo 'Received:<pre>' . htmlentities($serverXml) . '</pre>';
}
        
echo '<hr/>';
$xml = '<info attr="1"><data>Cdata with � (e acute), &amp;eacute; in it</data></info>' . chr(10);
$result = $client -> call('test', 'xmlarg', $xml);
if ($client -> isError()) {
    echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
} else {
    $serverXml = file_get_contents('xmlarg.xml');
    echo ($xml == $serverXml ? 'Passed' : 'Failed') . '<br/>';
    echo 'Sent:<pre>' . htmlentities($xml) . '</pre>';
    echo 'Received:<pre>' . htmlentities($serverXml) . '</pre>';
}
        
?>