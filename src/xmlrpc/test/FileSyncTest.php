<?php
/**
 * Test file sync
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: FileSyncTest.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('xmlrpc/FileSyncClient.php');

session_start();

echo '<?xml version="1.0" encoding="utf-8" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title>FileSyncClient Test Script</title>
  <style type="text/css">
* {
    font-family: arial,verdana,helvetica,sans-serif;
}
td,th {
    border-thickness:1; 
    border-style:solid;
    padding: 0px;
}
  </style>
 </head>
 <body>
<?php
if (! isset($_SESSION['fsc'])) {
    $_SESSION['fsc'] = new FileSyncClient();
}
$fsc = &$_SESSION['fsc'];
//
// Write status as a header
//
echo $fsc -> _debugInfo();
//
// Dump a test menu
//
echo '<table width="100%">';

echo '<tr><td>Set server connection string</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="setServer"/>';
echo '<input type="text" name="scc" size="60"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Create Session</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="sessionCreate"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Destroy Session</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="sessionDestroy"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Set Base Path</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="setBasePath"/>';
echo '<input type="text" name="path" size="60"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Set Server Path</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="setRemotePath"/>';
echo '<input type="text" name="path" size="60"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Get Directory List</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="getDirList"/>';
echo '<input type="text" name="dir" size="60"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Get File Date</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="getFileDate"/>';
echo '<input type="text" name="fid" size="60"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Get Host Time</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="getServerTime"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '<tr><td>Transfer File</td><td><form method="post" action="FileSyncTest.php">';
echo '<input type="hidden" name="cmd" value="putFile"/>';
echo '<input type="text" name="fid" size="60"/>';
echo '<input type="submit" value="Go"/></form></td></tr>';

echo '</table>';
//
// Process the command
//
if (isset($_REQUEST['cmd'])) {
    $cmd = $_REQUEST['cmd'];
    echo 'Command: ' . $cmd . '<br/>';
} else {
    $cmd = '';
}
$fsc -> clearMessages();
switch ($cmd) {
    case '': {
        $result = true;
    } break;
    
    case 'getDirList': {
        $result = $fsc -> getDirList($_REQUEST['dir']);
        if (! PEAR::isError($result)) {
            foreach ($result as $info) {
                echo $info[0] . ' type ' . $info[1] . ' modified ' . date('Y m d H:i:s', $info[2]) 
                    . ' size ' . $info[3] . '<br/>';
            }
        }
    } break;
    
    case 'getFileDate': {
        $result = $fsc -> getFileDate($_REQUEST['fid']);
        if (! PEAR::isError($result)) {
            if ($result == -1) {
                echo $_REQUEST['fid'] . ' not found<br/>';
            } else {
                echo $_REQUEST['fid'] . ' has time ' . date('Y m d H:i:s', $result) . ' on server<br/>';
            }
        }
    } break;
    
    case 'getServerTime': {
        $result = $fsc -> getServerTime();
    } break;
    
    case 'putFile': {
        $result = $fsc -> putFile($_POST['fid']);
    } break;
    
    case 'sessionCreate': {
        $result = $fsc -> sessionCreate();
    } break;
    
    case 'sessionDestroy': {
        $result = $fsc -> sessionDestroy();
    } break;
    
    case 'setBasePath': {
        $result = $fsc -> setBasePath($_POST['path']);
    } break;
    
    case 'setRemotePath': {
        $result = $fsc -> setRemotePath($_POST['path']);
    } break;
    
    case 'setServer': {
        $result = $fsc -> setServer($_POST['scc']);
    } break;
    
    default: {
        $result = 'Unknown command: ' . $cmd;
    } break;
}
if (PEAR::isError($result)) {
    echo $result -> toString();
} else {
    echo 'Result is ' . $result . '<br/>';
    $msgs = $fsc -> getMessages();
    foreach ($msgs as $msg) {
        echo $msg . '<br/>';
    }
}
echo '<br/>';
//
// Write status as a footer
//
echo $fsc -> _debugInfo();

?>
 </body>
</html>