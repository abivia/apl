<?php
/**
 * XmlRpcSession test server. Test bench for session authentication.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: XmlRpcSessionTestServer.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

//
// Set include paths
//
$oldPath = get_include_path();
$newPath = preg_replace('!([/\\\\])lib([/\\\\])php([^5]|$)!', '$1lib$2php5$3', $oldPath);

set_include_path($newPath);

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
        include_once $path; 
    }
}

//require_once('xmlrpc/XmlRpc.php');
//require_once('xmlrpc/XmlRpcSessionService.php');

class TestXmlRpcSessionService extends AP5L_XmlRpc_SessionService {
    
    /**
     * Authentication returns true if user='user' and digest matches a password
     * of 'pass'.
     * @param array Session status settings.
     * @param string User identifier.
     * @param string Hash of user password and nonce.
     * @return boolean True if authentication is successful.
     */
    function authenticate(&$status, $user, $digest) {
        $valid = md5(md5('pass') . ':' . $status['nonce']);
        //return $user . ' ' . $digest . ' ' . $valid;
        return $user == 'user' && $digest == $valid;
    }
    
}

$callBacks = array();
//$callBacks['session'] = 'AP5L_XmlRpc_SessionService::dispatch';

$server = new TestXmlRpcSessionService($callBacks, false, false);
$server -> convertSingleArgument = false;
$server -> serve();

?>