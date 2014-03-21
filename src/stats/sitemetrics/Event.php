<?php
/**
 * Site metrics event.
 *
 * @package AP5L
 * @subpackage Stats.SiteMetrics
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Event.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Site metrics event
 */
class AP5L_Stats_SiteMetrics_Event {
    var $_attributes = array();
    var $_eventDuration;
    var $_eventID;
    var $_eventStart;
    var $_eventStartUsec;
    var $_eventSubID;
    var $_eventType;                    // Likely 'page' or 'event'
    var $_open;                         // Flag set when a (timed) event is open
    var $_sessionID;

    function __construct($type, $eventID, $eventSubID = '') {
        $this -> _sessionID = session_id();
        $this -> _eventType = $type;
        $this -> _eventID = $eventID;
        $this -> _eventSubID = $eventSubID;
        $this -> _open = true;
        //
        // Do the timing last to reduce overhead
        //
        $mt = microtime();
        $this -> setStart($mt);
    }

    /**
     * Returns a floating point number between 0 and 10,000 that represents
     * the current time at the highest possible system precision.
     */
    static function _clock($mt = '') {
        if (! $mt) {
            $mt = microtime();
        }
        $fmt = substr($mt, strlen($mt) - 4) . substr($mt, 1, strpos($mt, ' ') - 1);
        return (float) $fmt;
    }

    /**
     * Calculate the difference between two _clock() values.
     */
    static function _delta($since) {
        $delta = round(self::_clock() - $since, 6);
        if ($delta < 0) $delta += 10000;
        return $delta;
    }

    function addAttribute($attr, $value) {
        if (! isset($this -> _attributes[$attr])) {
            $this -> _attributes[$attr] = array();
        }
        $this -> _attributes[$attr][] = $value;
    }

    function close($withDuration = true) {
        if (! $this -> _open) return false;
        if ($withDuration) {
            $this -> _eventDuration = $this -> _delta($this -> _eventStartUsec);
        } else {
            $this -> _eventDuration = null;
        }
        $this -> _open = false;
        return true;
    }

    function getDuration() {
        if (! $this -> _open) {
            return $this -> _delta($this -> _eventStartUsec);
        }
        return $this -> _eventDuration;
    }

    function getSessionID() {
        return $this -> _sessionID;
    }

    function getStart() {
        return $this -> _eventStart;
    }

    function isOpen() {
        return $this -> _open;
    }

    function setID($eventID, $eventSubID = '') {
        $this -> _eventID = $eventID;
        $this -> _eventSubID = $eventSubID;
    }

    function setStart($mt) {
        $this -> _eventStart = (int) substr($mt, strpos($mt, ' ') + 1);
        $this -> _eventStartUsec = $this -> _clock($mt);
    }

    function setSubID($eventSubID) {
        $this -> _eventSubID = $eventSubID;
    }

    function toXml() {
        $xml = '<event type="' . $this -> _eventType . '">' . chr(10)
            . '<timestamp>' . gmdate('Y-m-d H:i:s', $this -> _eventStart) . '</timestamp>' . chr(10);
        if (! is_null($this -> _eventDuration)) {
            $xml .= '<duration>' . $this -> _eventDuration . '</duration>' . chr(10);
        }
        $xml .= '<eventid>' . AP5L_Xml_Lib::toXmlString($this -> _eventID) . '</eventid>' . chr(10);
        if ($this -> _eventSubID) {
            $xml .= '<eventsubid>' . AP5L_Xml_Lib::toXmlString($this -> _eventSubID) . '</eventsubid>';
        }
        foreach ($this -> _attributes as $attr => $attrVals) {
            $xml .= '<attribute>' . chr(10)
                . '<name>' . AP5L_Xml_Lib::toXmlString($attr) . '</name>' . chr(10);
            foreach ($attrVals as $val) {
                $xml .= '<value>' . AP5L_Xml_Lib::toXmlString($val) . '</value>' . chr(10);
            }
            $xml .= '</attribute>' . chr(10);
        }
        $xml .= '</event>' . chr(10);
        return $xml;
    }

}

?>
