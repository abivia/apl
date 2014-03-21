<?php
/**
 * Define active window.
 *
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Window.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * AP5L_Net_Status_Window -- Defines an event's active window
 */
class AP5L_Net_Status_Window {
    var $customXml;                     // Service specific information
    var $duration;                      // Time (secs) of downtime (not including warning time)
    var $errMsg;
    var $schedule;                      // The time specification object
    var $status;                        // Down or degraded
    var $warningTime = 0;               // Time (secs) to warn of impending downtime

    function genError($msg) {
        $this -> errMsg = 'AP5L_Net_Status_Event::' . $msg;
    }

    function readXmlTree($node) {
        $root = 'window';
        if ($node -> element != $root) {
            $this -> genError('readXmlTree: node must be a ' . $root . ' element (found ' . $node -> element . ')');
            return false;
        }
        //
        // Process the children
        //
        $hasDuration = false;
        $timeSpecCount = 0;
        foreach ($node -> children as $subNode) {
            if ($subNode instanceof XmlElement) {
                switch ($subNode -> element) {
                    case 'custominfo': {
                        $this -> customXml = AP5L_Net_Status_Monitor::subNodesToString($subNode);
                    } break;

                    case 'duration': {
                        $timeStr = $subNode -> getAttribute('time', false);
                        if (! $timeStr) {
                            $this -> genError('readXmlTree: duration must have time attribute.');
                            return false;
                        }
                        $this -> duration = AP5L_Net_Status_Monitor::parseTime($timeStr);
                        if (! $this -> duration) {
                            $this -> genError('readXmlTree: duration time attribute must be a valid non-zero time.');
                            return false;
                        }
                        $hasDuration = true;
                    } break;

                    case 'warning': {
                        $timeStr = $subNode -> getAttribute('time', false);
                        if (! $timeStr) {
                            $this -> genError('readXmlTree: warning must have time attribute.');
                            return false;
                        }
                        $this -> warningTime = AP5L_Net_Status_Monitor::parseTime($timeStr);
                        if ($this -> warningTime === false) {
                            $this -> genError('readXmlTree: warning time attribute must be a valid time.');
                            return false;
                        }
                    } break;

                    //
                    // Event time specifications
                    //
                    case 'crontime': {
                        $time = new AP5L_Net_Status_ScheduleCron();
                        if ($time -> readXmlTree($subNode) === false) {
                            $this -> errMsg = $time -> errMsg;
                            return false;
                        }
                        $this -> schedule = $time;
                        $timeSpecCount++;
                    } break;

                    default: {
                        $this -> genError('readXmlTree: Unknown ' . $subNode -> element . ' element in ' . $root);
                        return false;
                    } break;
                }
            }
        }
        if (! $hasDuration) {
            $this -> genError('readXmlTree: window needs a duration element.');
            return false;
        }
        if ($timeSpecCount != 1) {
            $this -> genError('readXmlTree: window needs exactly one event time specification. '
                . $timeSpecCount . ' found.');
            return false;
        }
        return true;
    }

    function toXML($indent = 2, $depth = 0) {
        return '';
    }

}

?>
