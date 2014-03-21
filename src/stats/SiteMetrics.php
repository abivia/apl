<?php
/**
 * Site metrics.
 * 
 * @package AP5L
 * @subpackage Stats.SiteMetrics
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: SiteMetrics.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Site metrics.
 */
class AP5L_Stats_SiteMetrics {
    var $_aborts = array();             // array[i] of abnormally ended events
    var $_events;                       // array[i] of application events
    var $_listeners = array();          // array[fn] => array[callback]
    var $_page;                         // Event for the page

    /**
     * Create an event object. We use a factory to allow derived classes to
     * override this function and return an extended event.
     */
    function _eventFactory($type, $eventID = '', $eventSubID = '') {
        return new AP5L_Stats_SiteMetrics_Event($type, $eventID, $eventSubID);
    }

    /**
     * Add a custom listener for the named event or page function.
     */
    function addListener($fnName, $handler) {
        if (! isset($this -> _listeners[$fnName])) {
            $this -> _listeners[$fnName] = array();
        }
        $this -> _listeners[$fnName][] = $handler;
    }

    /**
     * Look for outstanding events at the beginning of a parse and flag them as
     * abnormally terminated.
     */
    function checkpoint() {
        if (count($this -> _events)) {
            $keys = array_keys($this -> _events);
            foreach ($keys as $eHandle) {
                if ($this -> _events[$eHandle] -> isOpen()) {
                    $this -> _events[$eHandle] -> addAttribute('ABEND', '1');
                    $this -> _aborts[] = $this -> _events[$eHandle];
                    unset($this -> _events[$eHandle]);
                }
            }
        }
        if ($this -> _page -> isOpen()) {
            $this -> _page -> addAttribute('ABEND', '1');
            $this -> _aborts[] = $this -> _page;
            $this -> _page = $this -> _eventFactory('page');
        }
    }

    /**
     * Close all open events at end of parse.
     */
    function close() {
        if (count($this -> _events)) {
            $keys = array_keys($this -> _events);
            foreach ($keys as $eHandle) {
                $this -> eventEnd($eHandle);
            }
        }
        $this -> pageEnd();
    }

    /**
     * Set an attribute on the specified event.
     */
    function eventAttribute($eHandle, $attr, $value) {
        if (! isset($this -> _events[$eHandle])) return false;
        $this -> _events[$eHandle] -> addAttribute($attr, $value);
        if (isset($this -> _listeners['eventAttribute'])) {
            foreach ($this -> _listeners['eventAttribute'] as $handler) {
                call_user_func($handler, $this -> _events[$eHandle]);
            }
        }
        return true;
    }

    /**
     * Close the specified event and write it.
     */
    function eventEnd($eHandle) {
        if (! isset($this -> _events[$eHandle])) return false;
        if (isset($this -> _listeners['eventEnd'])) {
            foreach ($this -> _listeners['eventEnd'] as $handler) {
                call_user_func($handler, $this -> _events[$eHandle]);
            }
        }
        if ($this -> _events[$eHandle] -> close()) {
            $this -> write($this -> _events[$eHandle]);
        }
        unset($this -> _events[$eHandle]);
    }

    /**
     * Set the specified event's ID (and optionally sub-ID)
     */
    function eventID($eHandle, $eventID, $eventSubID = '') {
        if (isset($this -> _events[$eHandle])) {
            $this -> _events[$eHandle] -> setID($eventID, $eventSubID);
            if (isset($this -> _listeners['eventID'])) {
                foreach ($this -> _listeners['eventID'] as $handler) {
                    call_user_func($handler, $this -> _events[$eHandle]);
                }
            }
        }
    }

    /**
     * Create an event and start it; return an event handle.
     */
    function eventStart($eventID, $eventSubID = '') {
        $eHandle = count($this -> _events);
        $this -> _events[$eHandle] = $this -> _eventFactory('event', $eventID, $eventSubID);
        if (isset($this -> _listeners['eventStart'])) {
            foreach ($this -> _listeners['eventStart'] as $handler) {
                call_user_func($handler, $this -> _events[$eHandle]);
            }
        }
        return $eHandle;
    }

    function eventSubID($eHandle, $eventSubID = '') {
        if (! isset($this -> _events[$eHandle])) return;
        $this -> _events[$eHandle] -> setSubID($eventSubID);
        if (isset($this -> _listeners['eventSubID'])) {
            foreach ($this -> _listeners['eventSubID'] as $handler) {
                call_user_func($handler, $this -> _events[$eHandle]);
            }
        }
    }

    function pageAttribute($attr, $value) {
        if (! $this -> _page) return false;
        $this -> _page -> addAttribute($attr, $value);
        if (isset($this -> _listeners['pageAttribute'])) {
            foreach ($this -> _listeners['pageAttribute'] as $handler) {
                call_user_func($handler, $this -> _page);
            }
        }
        return true;
    }

    function pageAttributes($pairs) {
        if (! $this -> _page) return false;
        foreach ($pairs as $attr => $value) {
            $this -> pageAttribute($attr, $value);
        }
        return true;
    }

    function pageEnd() {
        if (! $this -> _page) return false;
        if (isset($this -> _listeners['pageEnd'])) {
            foreach ($this -> _listeners['pageEnd'] as $handler) {
                call_user_func($handler, $this -> _page);
            }
        }
        if ($this -> _page -> close()) {
            $this -> write($this -> _page);
        }
    }

    function pageID($pageID, $pageSubID = '') {
        if (! $this -> _page) return false;
        $this -> _page -> setID($pageID, $pageSubID);
        if (isset($this -> _listeners['pageID'])) {
            foreach ($this -> _listeners['pageID'] as $handler) {
                call_user_func($handler, $this -> _page);
            }
        }
    }

    function pageStart($pageID = '', $pageSubID = '') {
        $this -> _page = $this -> _eventFactory('page', $pageID, $pageSubID);
        if (isset($this -> _listeners['pageStart'])) {
            foreach ($this -> _listeners['pageStart'] as $handler) {
                call_user_func($handler, $this -> _page);
            }
        }
    }

    function pageStartTime($mt) {
        if (! $this -> _page) return false;
        $this -> _page -> setStart($mt);
        if (isset($this -> _listeners['pageStartTime'])) {
            foreach ($this -> _listeners['pageStartTime'] as $handler) {
                call_user_func($handler, $this -> _page);
            }
        }
    }

    function pageSubID($pageSubID = '') {
        if ($this -> _page) return false;
        $this -> _page -> setSubID($pageSubID);
        if (isset($this -> _listeners['pageSubID'])) {
            foreach ($this -> _listeners['pageSubID'] as $handler) {
                call_user_func($handler, $this -> _page);
            }
        }
    }

    /**
     * Function to be called when all support for metrics in place; writes
     * aborted events.
     */
    function start() {
        foreach ($this -> _aborts as $abevent) {
            if ($abevent -> close()) {
                $this -> write($abevent);
            }
        }
        $this -> _aborts = array();
    }

    /**
     * The write method is to be overridden by the implementing class
     */
    function write($event) {
    }
}

?>