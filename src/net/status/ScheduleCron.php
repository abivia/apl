<?php
/**
 * Chronological schedule
 * 
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ScheduleCron.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Chronological shcedule
 */
class AP5L_Net_Status_ScheduleCron extends AP5L_Net_Status_Schedule {
    //
    // Handle a status schedule with a cron-style specification of the
    //  event window.
    //
    // IMPLEMENTATION RESTRICTION: don't select weekDay.
    // TODO: implement weekDay!
    //
    var $day;
    var $hour;
    var $minute;
    var $month;
    var $weekDay;
    var $year;

    function __construct() {
        parent::__construct();
        $this -> day = '*';
        $this -> hour = '*';
        $this -> minute = '*';
        $this -> month = '*';
        $this -> weekDay = '*';
        $this -> year = '*';
    }

    function CronStatusEvent() {
        $this -> __construct();
    }

    function &cloneOf() {
        $cloned = new AP5L_Net_Status_ScheduleCron();
        $cloned -> day = $this -> day;
        $cloned -> hour = $this -> hour;
        $cloned -> minute = $this -> minute;
        $cloned -> month = $this -> month;
        $cloned -> weekDay = $this -> weekDay;
        $cloned -> year = $this -> year;
        return $cloned;
    }

    function nextEvent($after = 0) {
        if ($after == 0) {
            $after = time();
        }
        $locked = $this -> cloneOf();
        $dateInfo = getdate($after);
        if ($locked -> day == '*') {
            $locked -> day = $dateInfo['mday'];
        }
        if ($locked -> hour == '*') {
            $locked -> hour = $dateInfo['hours'];
        }
        if ($locked -> minute == '*') {
            $locked -> minute = $dateInfo['minutes'];
        }
        if ($locked -> month == '*') {
            $locked -> month = $dateInfo['mon'];
        }
        if ($locked -> weekDay == '*') {
            $locked -> weekDay = $dateInfo['wday'];
        }
        if ($locked -> year == '*') {
            $locked -> year = $dateInfo['year'];
        }
        $next = mktime($locked -> hour, $locked -> minute, $locked -> second,
            $locked -> month, $locked -> day, $locked -> year);
        return $next;
    }

    function readXmlTree($node) {
        $root = 'crontime';
        if ($node -> element != $root) {
            $this -> genError('readXmlTree: node must be a ' . $root . ' element (found ' . $node -> element . ')');
            return false;
        }
        $this -> day = $node -> getAttribute('day', '*');
        $this -> hour = $node -> getAttribute('hour', '*');
        $this -> minute = $node -> getAttribute('minute', '*');
        $this -> month = $node -> getAttribute('month', '*');
        $this -> weekDay = $node -> getAttribute('weekDay', '*');
        $this -> year = $node -> getAttribute('year', '*');
        return true;
    }

    function toXML($indent = 2, $depth = 0) {
        return '<!-- write me! -->';
    }

}

?>