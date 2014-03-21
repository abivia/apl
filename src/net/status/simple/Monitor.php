<?php
/**
 * This is the main class for the simplified (dumb) status monitor.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Monitor.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */


/**
 * Simple status monitor.
 * 
 * Methods:      status(service)              Read status info, check status or
 * ping if required, return
 * 
 * @package AP5L
 * @subpackage Net.Status
 */
class AP5L_Net_Status_Simple_Monitor {

    function status($serviceName, $basePath, $forTime = 0) {
        if ($forTime == 0) {
            $now = time();
        } else {
            $now = $forTime;
        }
        if (file_exists($basePath . $serviceName . '.dat')) {
            $fh = @fopen($basePath . $serviceName . '.dat', 'r');
        } else {
            $fh = 0;
        }
        $sr = new AP5L_Net_Status_Report();
        $sr -> serviceName = $serviceName;
        if ($fh) {
            $command = '';
            $desc = '';
            $warn = '';
            while (! feof($fh)) {
                $buf = fgets($fh);
                $buf = substr($buf, 0, strlen($buf) - 1);
                if (substr(trim($buf), 0, 3) == '---') {
                    $buf = trim($buf);
                    $command = strtolower(substr($buf, 3, strlen($buf) - 3));
                } else {
                    switch ($command) {
                        case 'event': {
                            //
                            // we expect YYYYMMDDHHMMSS dur [warn]
                            //
                            $sr -> plannedEvent = mktime(substr($buf, 8, 2),
                                substr($buf, 10, 2), substr($buf, 12, 2),
                                substr($buf, 4, 2), substr($buf, 6, 2),
                                substr($buf, 0, 4));
                            $buf = trim(substr($buf, 14));
                            $posn = strpos($buf, ' ');
                            if ($posn === false) {
                                $dur = intval($buf);
                                $buf = '';
                            } else {
                                $dur = intval(substr($buf, 0, $posn));
                                $buf = trim(substr($buf, $posn));
                            }
                            $sr -> plannedRestore = $sr -> plannedEvent + $dur;
                            if ($buf != '') {
                                $tWarn = intval($buf);
                            } else {
                                $tWarn = 0;
                            }
                            $sr -> warningTime = $sr -> plannedEvent - $tWarn;
                        } break;

                        case 'desc': {
                            $desc .= $buf . ' ';
                        } break;

                        case 'warn': {
                            $warn .= $buf . ' ';
                        } break;
                    }
                }
            }
            fclose($fh);
            //
            // Now that the data is read, figure out our status
            //
            if ($sr -> plannedEvent < $now) {
                $sr -> status = SM_STATUS_DOWN;
                $sr -> message = array('*' => $desc);
                $sr -> serviceFactor = 0;
            } else if ($sr -> warningTime < $now) {
                $sr -> status = SM_STATUS_UP;
                $sr -> subStatus = SM_SUBSTATUS_WARNING;
                $sr -> message = array('*' => $warn);
            }
        }
        return $sr;
    }
}
