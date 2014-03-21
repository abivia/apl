<?php
/**
 * Status event.
 *
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Event.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Status event
 */
class AP5L_Net_Status_Event {
    var $customXml;                     // Service specific information
    var $description;                   // Array [lang] User description
    var $errMsg;
    var $status;                        // Down or degraded
    var $sysInfo;                       // Descriptive text for systems folk
    var $windows;                       // Array [i] Time windows

    function genError($msg) {
        $this -> errMsg = 'AP5L_Net_Status_Event::' . $msg;
    }

    function nextEvent() {
        // Look at all defined windows, pick the earliest event
        return 0;
    }

    function readXmlTree($node) {
        $root = 'event';
        if ($node -> element != $root && $node -> element != 'default') {
            $this -> genError('readXmlTree: node must be a ' . $root . ' element (found ' . $node -> element . ')');
            return false;
        }
        if ($node -> element == $root) {
            if (($this -> status = $node -> getAttribute('status', false)) === false) {
                $this -> genError('readXmlTree: ' . $root . ' is missing required status attribute.');
                return false;
            }
            if ($this -> status != 'down' && $this -> status != 'degraded') {
                $this -> genError('readXmlTree: ' . $root . ' status ' . $this -> status
                    . ' not valid. Must be down/degraded.');
            }
        } else {
            $this -> status = 'up';
        }
        //
        // Process the children
        //
        foreach ($node -> children as $subNode) {
            if ($subNode instanceof XmlElement) {
                switch ($subNode -> element) {
                    case 'custominfo': {
                        $this -> customXml = AP5L_Net_Status_Monitor::subNodesToString($subNode);
                    } break;

                    case 'description': {
                        $lang = $subNode -> getAttribute('language', '*');
                        $this -> description[$lang] = AP5L_Net_Status_Monitor::subNodesToString($subNode);
                    } break;

                    case 'sysinfo': {
                        $this -> customXml = AP5L_Net_Status_Monitor::subNodesToString($subNode);
                    } break;

                    case 'window': {
                        $statWin = new AP5L_Net_Status_Window();
                        if ($statWin -> readXmlTree($subNode) === false) {
                            $this -> errMsg = $statWin -> errMsg;
                            return false;
                        }
                        if (! $this -> windows) {
                            $this -> windows = array();
                        }
                        $this -> windows[] = $statWin;
                    } break;

                    default: {
                        $this -> genError('readXmlTree: Unknown ' . $subNode -> element . ' element in ' . $root);
                        return false;
                    } break;
                }
            }
        }
        return true;
    }

    function toXML($indent = 2, $depth = 0) {
    }

}

?>
