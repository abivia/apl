<?php
/**
 * filesync command line client
 *
 * Usage: php filesync.php [user[:password]@]serverurl localpath remotepath [sync|update]
 *
 * Contacts the named server, and ensures that the remote path has all the files
 * in the local path.
 *
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: filesync.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
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
        require_once($path);
    }
}

require_once('PEAR.php');

if ($argc < 4) {
    echo 'Usage: php filesync.php -t [user[:password]@]serverurl localpath remotepath [dry][sync|update]';
    exit(1);
}
$fs = new AP5L_XmlRpc_FileSyncClient();

$work = array_shift($argv);     // toss script name
$work = array_shift($argv);
if ($work[0] == '-') {
    /*
     * Options
     */
    for ($ind = 1; $ind < strlen($work); ++$ind) {
        switch ($work[$ind]) {
            case 't': {
                $fs -> showTiming = true;
            }
            break;

        }
    }
    $work = array_shift($argv);
}
$rpcServer = $work;
$localBase = array_shift($argv);
$remoteBase = array_shift($argv);
if (count($argv)) {
    $oprn = strtolower(array_shift($argv));
} else {
    $oprn = 'update';
}

if (substr($oprn, 0, 3) == 'dry') {
    $fs -> setDryRun(true);
    $oprn = substr($oprn, 3);
}
try {
    $retval = 0;
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
        // This should have been thrown as an exception...
        $result = $result -> toString();
    }
    echo $result . chr(10);
} catch (Exception $e) {
    // Just issue warning on file time stamp errors.
    if ($e -> getCode() == 5) {
        echo 'Failed to set time stamp.' . chr(10);
    } else {
        echo $e;
        $retval = 1;
    }
}
$msgs = $fs -> getMessages();
foreach ($msgs as $msg) {
    echo $msg . chr(10);
}
exit($retval);

?>