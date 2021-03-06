<?php
/**
 * XML-RPC Date
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Date.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Representation of a date object
 */
class AP5L_XmlRpc_Date {
    var $year;
    var $month;
    var $day;
    var $hour;
    var $minute;
    var $second;
    
    function __construct($time) {
        // $time can be a PHP timestamp or an ISO one
        if (is_numeric($time)) {
            $this -> parseTimestamp($time);
        } else {
            $this -> parseIso($time);
        }
    }
    
    function parseTimestamp($timestamp) {
        $this -> year = date('Y', $timestamp);
        $this -> month = date('Y', $timestamp);
        $this -> day = date('Y', $timestamp);
        $this -> hour = date('H', $timestamp);
        $this -> minute = date('i', $timestamp);
        $this -> second = date('s', $timestamp);
    }
    
    function parseIso($iso) {
        $this -> year = substr($iso, 0, 4);
        $this -> month = substr($iso, 4, 2);
        $this -> day = substr($iso, 6, 2);
        $this -> hour = substr($iso, 9, 2);
        $this -> minute = substr($iso, 12, 2);
        $this -> second = substr($iso, 15, 2);
    }
    
    function getIso() {
        return $this -> year . $this -> month . $this -> day . 'T' . $this -> hour . ':' . $this -> minute . ':' . $this -> second;
    }
    
    function getXml() {
        return '<dateTime.iso8601>' . $this -> getIso() . '</dateTime.iso8601>';
    }
    
    function getTimestamp() {
        return mktime($this -> hour, $this -> minute, $this -> second, $this -> month, $this -> day, $this -> year);
    }
    
}

?>