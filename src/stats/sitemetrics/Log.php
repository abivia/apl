<?php
/**
 * A site metrics event organized for storing/retrieving in a database
 *
 * @package AP5L
 * @subpackage Stats.SiteMetrics
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Log.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A site metrics event organized for storing/retrieving in a database
 */
class AP5L_Stats_SiteMetrics_Log {
    var $_eventTimeUtc;                 // When the event was recorded. String
    var $_eventXml;                     // XML data related to the event
    var $_logSeq;                       // Local sequence number, changes when uploaded
    var $_logID;                        // Immutable unique log record identifier
    var $_sessionID;                    // Client session ID

    static function &eventFactory($event) {
        $log = new AP5L_Stats_SiteMetrics_Log();
        $log -> _eventTimeUtc = gmdate('Y-m-d H:i:s', $event -> getStart());
        $log -> _sessionID = $event -> getSessionID();
        $log -> _eventXml = $event -> toXml();
        return $log;
    }

    function getDate() {
        return $this -> _eventTimeUtc;
    }

    function getLogID() {
        return $this -> _logID;
    }

    function getSequence() {
        return $this -> _logSeq;
    }

    function getSessionID() {
        return $this -> _sessionID;
    }

    function getXml() {
        return $this -> _eventXml;
    }

    /**
     * Write the event to a database.
     */
    function write() {
    }

}

?>
