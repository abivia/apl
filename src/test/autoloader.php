<?php
/**
 * Autoloader
 *
 * This is the default autoloader, used by unit tests.
 *
 * @package AP5L
 * @subpackage UnitTest
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: autoloader.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

function __autoload($className) {
    $components = explode('_', $className);
    $base = array_shift($components);
    if (count($components)) {
        $final = array_pop($components);
        switch ($base) {
            case 'AP5L': {
                $rootPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
            }
            break;

            default: {
                // We don't know how to load this one.
                return;
            }
        }
        $path = $rootPath;
        foreach ($components as $dir) {
            $path .= strtolower($dir) . DIRECTORY_SEPARATOR;
        }
        $path .= $final . '.php';
    } elseif ($base == 'AP5L') {
        $path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $base . '.php';
    } else {
        $path = $base . '.php';
    }
    //echo 'Autoload ' . $className . ' from ' . $path . chr(10);
    //error_log('Autoload ' . $className . ' from ' . $path);
    include $path;
}

?>
