<?php
/**
 * XmlRpc session test client. Generates calls to the test server and ensures
 * the results are as expected.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: XmlRpcSessionTestClient.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

//
// Set include paths
//
$oldPath = get_include_path();
echo 'old path is ' . $oldPath . '<br/>';
$newPath = preg_replace('!([/\\\\])lib([/\\\\])php([^5]|$)!', '$1lib$2php5$3', $oldPath);
echo 'new path is ' . $newPath . '<br/>';
set_include_path($newPath);

//require_once('xmlrpc/XmlRpc.php');

function __autoload($className) {
    $components = explode('_', $className);
    $base = array_shift($components);
    if ($base == 'AP5L' && count($components)) {
        $final = array_pop($components);
        $path = '';
        foreach ($components as $dir) {
            $path .= strtolower($dir) . '/';
        }
        $path .= $final . '.php';
        require_once($path); 
    }
}

function runTests() {
    $host = isset($_REQUEST['host']) ? $_REQUEST['host'] : 'localhost'; 
    
    $url = 'http://' . $host . '/lib/php5/xmlrpc/test/XmlRpcSessionTestServer.php';
    echo 'url=' . $url . '<br/>';
    $client = new AP5L_XmlRpc_Client($url);
    $client -> debug = 0;
    $hashPass = md5('pass');
    /*
     * Request a sesion start, passing an invalid ID. The server should respond
     * with a new session Id and nonce.
     */
    echo '<hr/><h2>Session Start (with bad session ID)</h2>';
    $result = $client -> call('session.start', 'abc/def', true);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo 'Session, nonce<pre>', print_r($result, true), '</pre>';
        $session = $result[0];
        $nonce = $result[1];
    }
    /*
     * Query to see if the session is authenticated. At this point this should
     * return false.
     */
    checkAuth($client, $session, false);
    
    /*
     * Request authentication, using a valid user/password and the nonce for
     * this session.
     */
    echo '<hr/><h2>Authenticate (valid)</h2>';
    $digest = md5($hashPass . ':' . $nonce);
    $result = $client -> call('session.auth', $session, 'user', $digest);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo ($result) ? 'Passed' : 'Failed<br/>';
        echo '<pre>', print_r($result, true), '</pre>';
    }
    
    /*
     * Query to see if the session is authenticated. At this point this should
     * return true.
     */
    checkAuth($client, $session, true);
    
    /*
     * Request authentication, using an invalid password but the correct nonce
     * for this session.
     */
    echo '<hr/><h2>Authenticate (invalid pass)</h2>';
    $digest = md5(md5('foo') . ':' . $nonce);
    $result = $client -> call('session.auth', $session, 'user', $digest);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo (! $result) ? 'Passed' : 'Failed<br/>';
        echo '<pre>', print_r($result, true), '</pre>';
    }
    
    /*
     * Query to see if the session is authenticated. At this point this should
     * return false.
     */
    checkAuth($client, $session, false);
    
    /*
     * Request authentication, using an invalid user but the correct password
     * and nonce for this session.
     */
    echo '<hr/><h2>Authenticate (invalid user)</h2>';
    $digest = md5($hashPass . ':' . $nonce);
    $result = $client -> call('session.auth', $session, 'foouser', $digest);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo (!$result) ? 'Passed' : 'Failed<br/>';
        echo '<pre>', print_r($result, true), '</pre>';
    }
    
    /*
     * Query to see if the session is authenticated. At this point this should
     * return false.
     */
    checkAuth($client, $session, false);

    /*
     * Request authentication, using valid user and password but an incorrect
     * nonce for this session.
     */
    echo '<hr/><h2>Authenticate (invalid nonce)</h2>';
    $digest = md5($hashPass . ':bad_nonce');
    $result = $client -> call('session.auth', $session, 'user', $digest);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo (! $result) ? 'Passed' : 'Failed<br/>';
        echo '<pre>', print_r($result, true), '</pre>';
    }
    
    /*
     * Query to see if the session is authenticated. At this point this should
     * return false.
     */
    checkAuth($client, $session, false);
    
    /*
     * Request authentication, again using a valid user/password and the nonce
     * for this session.
     */
    echo '<hr/><h2>Authenticate (valid)</h2>';
    $digest = md5($hashPass . ':' . $nonce);
    $result = $client -> call('session.auth', $session, 'user', $digest);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo ($result) ? 'Passed' : 'Failed<br/>';
        echo '<pre>', print_r($result, true), '</pre>';
    }
    
    /*
     * Query to see if the session is authenticated. At this point this should
     * return true.
     */
    checkAuth($client, $session, true);

    /*
     * Destroy the session.
     */
    echo '<hr/><h2>Session destroy</h2>';
    $digest = md5($hashPass . ':' . $nonce);
    $result = $client -> call('session.destroy', $session);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo ($result) ? 'Passed' : 'Failed<br/>';
        echo '<pre>', print_r($result, true), '</pre>';
    }
    
    /*
     * Query to see if the session is authenticated. At this point this should
     * return false.
     */
    checkAuth($client, $session, false);
}

function checkAuth($client, $session, $expect) {
    echo '<hr/><h3>Check auth state (' . ($expect ? 'true' : 'false') . ')</h3>';
    $result = $client -> call('session.isauth', $session);
    if ($client -> isError()) {
        echo $client -> getErrorCode() . ': ' . $client -> getErrorMessage() . '<br/>';
        return;
    } else {
        echo ($result == $expect) ? 'Passed' : 'Failed<br/>';
        echo '<pre>', print_r($result, true), '</pre>';
    }
    
}

runTests();

?>