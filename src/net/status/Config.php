<?php
/**
 * Status configuration.
 * 
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Config.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/*
 * This is broken
 */
require_once('xml/XmlTree.php');

/**
 * Memory version of a configuration file
 *
 * @package AP5L
 * @subpackage Net.Status
 */
class AP5L_Net_Status_Config {
    var $errMsg;                        // Message in the event of an error
    var $serverClass;                   // Class of server when in an array
    var $serverID;                      // This server's unique identifier
    var $services = array();            // Array of service definitions

    function cloneOf() {
        // clone this configuration
    }

    function genError($msg) {
        $this -> errMsg = 'AP5L_Net_Status_Config::' . $msg;
    }

    function read($file) {
        $this -> errMsg = '';
        $xTree = new XmlTree();
        if (! $xTree -> parseFile($file)) {
            $this -> errMsg = $xTree -> errMsg;
            return false;
        }
        //
        // Convert the tree into our data structures
        //
        $root = 'statusmonitor';
        $node = $xTree -> root;
        if ($node -> element != $root) {
            $this -> genError('read: Config file must have ' . $root . ' element as root.');
            return false;
        }
        //
        // Pick up attributes
        //
        if (($this -> serverID = $node -> getAttribute('serverid', false)) === false) {
            $this -> genError('read: ' . $root . ' element missing required serverid attribute');
            return false;
        }
        $this -> serverClass = $node -> getAttribute('serverclass', '');
        //
        // Process the children
        //
        foreach ($node -> children as $subNode) {
            if ($subNode instanceof XmlElement) {
                switch ($subNode -> element) {
                    case 'service': {
                        $service = new AP5L_Net_Status_Service;
                        if ($service -> readXmlTree($subNode)) {
                            $this -> services[] = $service;
                        } else {
                            $this -> errMsg = $service -> errMsg;
                            return false;
                        }
                    } break;

                    default: {
                        $this -> genError('read: Unknown ' . $subNode -> element . ' element in ' . $root);
                        return false;
                    } break;
                }
            }
        }
    }

    function toXML($indent = 2, $depth = 0) {
    }

}

?>