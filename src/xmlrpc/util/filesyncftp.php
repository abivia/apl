<?php
/**
 * filesync command line client (ftp transport)
 *
 * Usage:
 * php filesync.php xmlserverurl localpath ftpserver remotepath [sync|update]
 *
 * Both servers have the form: proto://[user[:password]@]basepath
 *
 * Contacts the named server, and ensures that the remote path has all the files
 * in the local path.
 *
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: filesyncftp.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
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
        require_once $path;
    }
}

require_once('PEAR.php');

if ($argc < 5) {
    echo 'Usage: php filesyncftp.php http[s]://[user[:password]@]serverurl localpath ftp[s]://[user[:password]@]ftpserver remotepath [sync|update]';
    exit(1);
}
if ($argc > 5) {
    $oprn = strtolower($argv[5]);
} else {
    $oprn = 'update';
}
$rpcServer = $argv[1];
$localBase = $argv[2];
$ftpServer = $argv[3];
$remoteBase = $argv[4];
//echo ' Operation: ' . $oprn . chr(10);
//echo 'RPC Server: ' . $rpcServer . chr(10);
//echo 'Local Base: ' . $localBase . chr(10);
//echo 'FTP Server: ' . $ftpServer . chr(10);
//echo 'RemoteBase: ' . $remoteBase . chr(10);

$fs = new AP5L_XmlRpc_FileSyncClient();
$fs -> setTransport($ftpServer);
switch ($oprn) {
    case 'sync': {
        $result = 'Sorry, sync isn\'t implemented yet.';
    } break;

    case 'update': {
        $result = $fs -> updateBatch($localBase, $remoteBase, $rpcServer);
    } break;

    case 'update1x1': {
        $result = $fs -> update($localBase, $remoteBase, $rpcServer);
    } break;

    default: {
        $result = 'Unknown operation ' . $oprn;
    } break;
}
if (PEAR::isError($result)) {
    echo $result -> toString();
    exit(1);
}
echo $result . chr(10);
$msgs = $fs -> getMessages();
foreach ($msgs as $msg) {
    echo $msg . chr(10);
}
exit(0);

?>