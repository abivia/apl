<?php
/**
 * This is the main class for the status monitor.
 *
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Monitor.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */


/**
 * Status monitor class.
 */
/*
 * Methods:
 *      initiate
 *              read local configuration file, sync with mirrors if required
 *      monitor
 *              read local status, execute check, return status
 *      ping([service])
 *              Read status info, query relevant servers,
 *              update status
 *              return status
 *      saveConfig
 *              Write a local copy of configuration and inform mirrors
 *      status(service)
 *              Read status info, check status or ping if required, return
 */
class AP5L_Net_Status_Monitor {
    var $configPath;                    // Path to configuration information
    var $statusPath;                    // Path to persistent status

    function initialize($force = false) {
        // Read the config file if required
    }

    /**
     * Parse an ISO-like string into a date/time value expressed in UNIX ticks
     */
    function parseTime($timeStr) {
        //
        //         0   1   2   3   4   5        step
        //      yyyy  mm[ dd[ hh[:mm[:ss]]]]]   Mode 1 -- absolute time, yyyy>=1970
        // [[[[[yyyy ]mm ]dd ]hh:]mm:]ss        Mode 2 -- relative time
        //      ssss                            Mode 2 -- special case
        //
        $data = array(1970, 1, 1, 0, 0, 0);
        $mode = 0;
        $mode2Data = array();
        $size = 0;
        $step = 0;
        $accum = 0;
        for ($ind = 0; $ind <= strlen($timeStr) && $step < 6; $ind++) {
            if ($ind == strlen($timeStr)) {
                $ch = '';
            } else {
                $ch = strtolower($timeStr{$ind});
            }
            if (is_numeric($ch)) {
                $accum = $accum * 10 + $ch;
                $size++;
            } else if ($ch >= 'a' && $ch  <= 'z') {
                return false;
            } else {
                if ($mode == 0) {
                    $mode = ($accum >= 1970 && $ch != '') ? 1 : 2;
                }
                if ($size) {
                    if ($mode == 1) {
                        $data[$step++] = $accum;
                    } else {
                        $mode2Data[$step++] = $accum;
                    }
                    $accum = 0;
                    $size = 0;
                }
            }
        }
        if ($mode == 2) {
            //
            // In mode 2 we copy the data in shifted. If a day or month was
            // supplied, we also increment the first value to compensate for
            // dates having origin 1, so that '1 20:34:45' becomes '2 20:34:45'
            // and '2 3 10:00:15' becomes '3 3 10:00:15', although the month
            // case has questionable logic since a month is not a standard size.
            //
            if (count($mode2Data) > 3) {
                $mode2Data[0]++;
            }
            $step = 6 - count($mode2Data);
            for ($ind = 0; $ind < count($mode2Data); $ind++) {
                $data[$ind + $step] = $mode2Data[$ind];
            }
        }
        $time = gmmktime($data[3], $data[4], $data[5], $data[1], $data[2], $data[0]);
        return $time;
    }

    function status($service) {
    }

    function subNodesToString($node) {
        //
        // Descriptive text nodes may contain XHTML, so we convert all
        // child nodes back into XML/XHTML.
        //
        $fmt = new XmlFormatInfo();
        $xmlString = '';
        foreach ($node -> children as $subNode) {
            $xmlString .= $subNode -> toXml($fmt, 0);
        }
        return $xmlString;
    }
}
?>