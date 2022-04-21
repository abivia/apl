<?php
/**
 * Apl The Abivia PHP Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Notification.php 93 2009-08-21 03:05:34Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl\Event;

/**
 * Event notification
 */
class Notification {
    /**
     * Initial notification state.
     */
    const STATE_NORMAL = 0;

    /**
     * Notification is canceled.
     */
    const STATE_CANCELED = 1;

    /**
     * Number of observers that received this notification.
     *
     * @var integer
     */
    protected $_count = 0;

    /**
     * Application data related to the event.
     *
     * @var mixed
     */
    protected $_info = null;

    /**
     * Notification name.
     *
     * @var string
     */
    protected $_name;

    /**
     * Event issuer (or other related object).
     *
     * @var object
     */
    protected $_object;

    /**
     * Notification state, one of the STATE_* constants. This is an integer to
     * allow extended classes to add other states.
     *
     * @var integer
     */
    protected $_state = self::STATE_NORMAL;

    /**
     * Constructor
     *
     * @param object The object of interest for the notification, usually is
     * the posting object.
     * @param string Notification name.
     * @param mixed Arbitrary application information.
     *
     * @return void
     */
    public function __construct($object, $name, $info = null) {
        $this -> _object = $object;
        $this -> _name = $name;
        $this -> _info = $info;
    }

    /**
     * Convert object to string.
     *
     * @return string
     */
    public function __toString() {
        return $this -> _name;
    }

    /**
     * Cancel the notification.
     */
    public function cancel() {
        $this -> _state = self::STATE_CANCELED;
    }

    /**
     * Returns aditional information related to the event.
     *
     * @return mixed Application data
     */
    public function getInfo() {
        return $this -> _info;
    }

    /**
     * Get the notification name
     *
     * @return string Notification name
     */
    function getName()
    {
        return $this -> _name;
    }

    /**
     * Returns the contained object
     *
     * @return object Contained object
     */
    public function getObject()
    {
        return $this -> _object;
    }

    /**
     * Get the number of posted notifications.
     *
     * @return int
     */
    public function getPostCount() {
        return $this -> _count;
    }

    /**
     * Determine if the notification is canceled.
     *
     * @return   boolean
     */
    public function isCanceled() {
        return $this -> _state == self::STATE_CANCELED;
    }

    /**
     * Increase the post count
     *
     * @return void
     */
    public function posted() {
        ++$this -> _count;
    }

}
