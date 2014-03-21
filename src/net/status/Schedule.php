<?php
/**
 * Status schedule.
 *
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Schedule.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */
/**
 * Status schedule
 */
class AP5L_Net_Status_Schedule {

    function nextEvent() {
        return 0;
    }

    function toXML($indent = 2, $depth = 0) {
        return '<!-- AP5L_Net_Status_Schedule::toXML must be overidden -->';
    }

}

?>
