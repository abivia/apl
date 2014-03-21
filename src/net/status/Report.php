<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Report.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Constants
 */
define('SM_STATUS_BAD', -1);
define('SM_STATUS_DOWN', 0);
define('SM_STATUS_UP', 1);

define('SM_SUBSTATUS_NORMAL', 0);
define('SM_SUBSTATUS_DOWN_UNPLANNED', 1);
define('SM_SUBSTATUS_UP_WARNING', 2);
define('SM_SUBSTATUS_UP_DEGRADED', 3);

/**
 * Status report
 *
 * @package AP5L
 * @subpackage Net.Status
 */
class AP5L_Net_Status_Report {
    var $_description = array();        // Array[lang] = Description of the service
    var $_message;                      // Array[lang] current status
    var $_plannedEvent;                 // Time of next planned down/degraded event
    var $_plannedRestore;               // Time service expected to resume (if any, 0 if not)
    var $_serviceFactor;                // Service factor 0..1
    var $_serviceName;                  // Name of the service
    var $_status;                       // Up (1), down(0)
    var $_subStatus;                    // Normal, going down, up but degraded
    var $_warningTime;                  // Time to start warning on planned shutdown
    var $customInfo;                    // Service specific data

    function __construct() {
        $this -> _message = array('*' => '');
        $this -> _status = SM_STATUS_UP;
        $this -> _subStatus = SM_SUBSTATUS_NORMAL;
        $this -> _serviceFactor = 1.0;
    }

    function getDescription($lang = '*') {
        if (isset($this -> _description[$lang])) {
            return $this -> _description[$lang];
        }
        return $this -> _description['*'];
    }

    function getMessage($lang = '*') {
        if (isset($this -> _message[$lang])) {
            return $this -> _message[$lang];
        }
        return $this -> _message['*'];
    }

    function getName() {
        return $this -> _serviceName;
    }

    function getStatus() {
        return $this -> _status;
    }

    function getSubStatus() {
        return $this -> _subStatus;
    }

    function setMessage($text, $lang = '*') {
        $this -> _message[$lang] = $text;
    }

    function setServiceFactor($factor) {
        if (! is_numeric($factor) || $factor < 0 || $factor > 1) {
            return false;
        }
        $this -> _serviceFactor = $factor;
        return true;
    }

    function setServiceName($name) {
        $this -> _serviceName = $name;
        return true;
    }

    function setStatus($status, $subStatus = false) {
        switch ($status) {
            case SM_STATUS_DOWN: {
                $this -> _status = SM_STATUS_DOWN;
                if ($subStatus == SM_SUBSTATUS_DOWN_UNPLANNED) {
                    $this -> _subStatus = SM_SUBSTATUS_DOWN_UNPLANNED;
                } else {
                    $this -> _subStatus = SM_SUBSTATUS_NORMAL;
                    return false;
                }
            } break;

            case SM_STATUS_UP: {
                $this -> _status = SM_STATUS_UP;
                if ($subStatus == SM_SUBSTATUS_UP_WARNING) {
                    $this -> _subStatus = SM_SUBSTATUS_UP_WARNING;
                } else if ($subStatus == SM_SUBSTATUS_UP_DEGRADED) {
                    $this -> _subStatus = SM_SUBSTATUS_UP_DEGRADED;
                } else {
                    $this -> _subStatus = SM_SUBSTATUS_NORMAL;
                    return false;
                }
            } break;

            default: {
                $this -> _status = SM_STATUS_BAD;
                $this -> _subStatus = SM_SUBSTATUS_NORMAL;
                return false;
            } break;

        }
        return true;
    }

    function setSubStatus($subStatus) {
        return $this -> setStatus($this -> _status, $subStatus);
    }
}
