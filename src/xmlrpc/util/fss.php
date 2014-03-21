<?php
/**
 * A file sync server.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: fss.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

@include_once($_SERVER['DOCUMENT_ROOT'] . '/config/ini.php');

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

require_once('xmlrpc/FileSyncServer.php');
?>