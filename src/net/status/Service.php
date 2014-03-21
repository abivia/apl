<?php
/**
 * Service definition
 *
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Service.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Service definition
 */
class AP5L_Net_Status_Service {
    var $checkProcess = array();        // Array [proglang] = method / call / process
    var $description = array();         // Array[lang] = Description of the service
    var $editTime;                      // timestamp of last edit
    var $errMsg;
    var $mirrors = array();             // array [address] = sync interval
    var $schedules = array();           // array [i] of service window information
    var $servers = array();             // array [i] of servers
    var $serviceDefault;                // Default status for the service
    var $serviceName;

    function genError($msg) {
        $this -> errMsg = 'AP5L_Net_Status_Service::' . $msg;
    }

    function readXmlTree($node) {
        $root = 'service';
        if ($node -> element != $root) {
            $this -> genError('readXmlTree: node must be a ' . $root . ' element (found ' . $node -> element . ')');
            return false;
        }
        if (($this -> serviceName = $node -> getAttribute('name', false)) === false) {
            $this -> genError('readXmlTree: ' . $root . ' is missing required name attribute.');
            return false;
        }
        $this -> editTime = $node -> getAttribute('edittime', 0);
        if ($this -> editTime) {
            $this -> editTime = AP5L_Net_Status_Monitor::parseTime($this -> editTime);
            if (! $this -> editTime) {
                $this -> genError('readXmlTree: ' . $root . ': edittime must be a valid time.');
                return false;
            }
        }
        //
        // Process the children
        //
        foreach ($node -> children as $subNode) {
            if ($subNode instanceof XmlElement) {
                switch ($subNode -> element) {
                    case 'checkprocess': {
                        //
                        // Check process requires language and method attributes
                        //
                        $lang = $subNode -> getAttribute('language', false);
                        $method = $subNode -> getAttribute('method', false);
                        if (! $lang || ! $method) {
                            $this -> genError('readXmlTree: checkprocess must have language and method attributes.');
                            return false;
                        }
                        $this -> checkProcess[$lang] = $method;
                    } break;

                    case 'default': {
                        // default is an event with no windows
                        $event = new AP5L_Net_Status_Event;
                        if ($event -> readXmlTree($subNode)) {
                            $event -> windows = null;
                            $this -> serviceDefault = $event;
                        } else {
                            $this -> errMsg = $event -> errMsg;
                            return false;
                        }
                    } break;

                    case 'description': {
                        $lang = $subNode -> getAttribute('language', '*');
                        $this -> description[$lang] = AP5L_Net_Status_Monitor::subNodesToString($subNode);
                    } break;

                    case 'mirror': {
                        //
                        // Mirror requires address and sync attributes
                        //
                        $addr = $subNode -> getAttribute('address', false);
                        $sync = $subNode -> getAttribute('sync', false);
                        if (! $addr || ! $sync) {
                            $this -> genError('readXmlTree: mirror must have address and sync attributes.');
                            return false;
                        }
                        $sync = AP5L_Net_Status_Monitor::parseTime($sync);
                        if ($sync === false) {
                            $this -> genError('readXmlTree: mirror sync attribute must be a valid time.');
                            return false;
                        }
                        $this -> mirrors[$addr] = $sync;
                    } break;

                    case 'schedule': {
                        $this -> schedules = array();
                        //
                        // Process the children
                        //
                        foreach ($subNode -> children as $sub2Node) {
                            if ($subNode instanceof XmlElement) {
                                switch ($sub2Node -> element) {
                                    case 'event': {
                                        $event = new AP5L_Net_Status_Event;
                                        if ($event -> readXmlTree($sub2Node)) {
                                            $this -> schedules[] = $event;
                                        } else {
                                            $this -> errMsg = $event -> errMsg;
                                            return false;
                                        }
                                    } break;

                                    default: {
                                        $this -> genError('readXmlTree: Unknown ' . $sub2Node -> element . ' element in event.');
                                        return false;
                                    } break;
                                }
                            }
                        }
                    } break;

                    case 'server': {
                        $server = new AP5L_Net_Status_Server();
                        if (! $server -> readXmlTree($subNode)) {
                            $this -> errMsg = $server -> errMsg;
                            return false;
                        }
                        $this -> servers[] = $server;
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
