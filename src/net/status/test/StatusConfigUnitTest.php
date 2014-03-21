<?php
/**
 * Unit test for status configuration
 * 
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: StatusConfigUnitTest.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

function __autoload($className) {
    $components = explode('_', $className);
    $base = array_shift($components);
    if (count($components)) {
        $final = array_pop($components);
        switch ($base) {
            case 'AP5L': {
                $rootPath = '';
            }
            break;

            default: {
                // We don't know how to load this one.
                return;
            }
        }
        $path = $rootPath;
        foreach ($components as $dir) {
            $path .= strtolower($dir) . '/';
        }
        $path .= $final . '.php';
        require_once($path);
    }
}

function statusConfigTest($verbose = false) {
    $tests = 0;
    $fails = 0;
    //
    // Time tests
    //
    echo 'Testing StatusConfig::read<br/>';
    $sc = new AP5L_Net_Status_Config();
    if ($sc -> read('smconfig.xml') === false) {
        echo $sc -> errMsg;
        $fails++;
        return false;
    }
    $tests++;
    if ($verbose) {
        echo 'Read Configuration:<pre>';
        print_r($sc);
        echo '</pre>';
    }
    //
    // Summary
    //
    echo 'Summary: Ran ' . $tests . ' tests, ' . $fails . ' failures.<br/>';
}

statusConfigTest(true);
?>