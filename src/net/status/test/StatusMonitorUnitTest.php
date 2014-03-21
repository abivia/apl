<?php
/**
 * Unit test for status monitoring
 * 
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: StatusMonitorUnitTest.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
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

function statusMonitorTest() {
    $tests = 0;
    $fails = 0;
    //
    // Time tests
    //
    echo 'Testing AP5L_Net_Status_Monitor::parseTime<br/>';
    $timeTests = array(
        array('0', 0, '1970 01 01 00:00:00', 'Zero seconds'),
        array('10', 10, '1970 01 01 00:00:10', 'Ten seconds'),
        array('600', 600, '1970 01 01 00:10:00', '600s -> 10m'),
        array('10:20', 620, '1970 01 01 00:10:20', 'Ten min, 20 sec'),
        array('1:00:00', 3600, '1970 01 01 01:00:00', 'One hour'),
        array('3600', 3600, '1970 01 01 01:00:00', '3600s -> One hour'),
        array('1 0:0:0', 86400, '1970 01 02 00:00:00', 'One day'),
        array('2000 10 8', 970963200, '2000 10 08 00:00:00', 'October 8, 2000'),
        array('2000 10 8 12:15', 971007300, '2000 10 08 12:15:00', 'October 8, 2000 at 12:15'),
        array('2000 10 8 12:15:7', 971007307, '2000 10 08 12:15:07', 'October 8, 2000 at 12:15:07')
        );
    foreach ($timeTests as $test) {
        $time = AP5L_Net_Status_Monitor::parseTime($test[0]);
        if ($time != $test[1]) {
            echo 'Failed ' . $test[3] . ' test (int). Expected ' . $test[1] . ' got ' . $time . '<br/>';
            $fails++;
        }
        $tests++;
        $timeStr = gmdate('Y m d H:i:s', $time);
        if ($timeStr != $test[2]) {
            echo 'Failed ' . $test[3] . ' test (string). Expected ' . $test[2] . ' got ' . $timeStr . '<br/>';
            $fails++;
        }
        $tests++;
    }
    //
    // Summary
    //
    echo 'Summary: Ran ' . $tests . ' tests, ' . $fails . ' failures.<br/>';
}

statusMonitorTest();
?>