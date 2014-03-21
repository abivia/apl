<?php
/**
 * XmlRpc test server.
 * 
 * Recieves a bunch of calls from the test client and responds accordingly.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: XmlRpcTestServer.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('xmlrpc/XmlRpc.php');

function processTest($args) {
    // testname, data, data, data...
    $testName = $args[0];
    switch ($testName) {
        case 'echo': {
            return $args;
        } break;
        case 'xmlarg': {
            $fh = fopen('xmlarg.xml', 'w');
            if (! $fh) {
                return 'Failed to open output file.';
            }
            fwrite($fh, $args[1]);
            fclose($fh);
        } break;
    }
    return 0;
}


$callBacks = array();
$callBacks['test'] = 'processTest';

$server = new XmlRpcServer($callBacks, false, false);
$server -> convertSingleArgument = false;
$server -> serve();

?>