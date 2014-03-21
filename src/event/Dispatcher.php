<?php
/**
 * AP5L The Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Dispatcher.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Event dispatcher.
 */
class AP5L_Event_Dispatcher {
    /**
     * Static reference array of named instances.
     *
     * @var array
     */
    protected static $_instances = array();

    protected $_notificationClass;

    /**
     * Matrix of registered observers.
     *
     * The observer matrix is indexed by event name and class. The global event is
     * an empty string; listeners to an empty event receive all events. An empty
     * class is a wildcard, listeners with no class filter receive all events.
     * Each element in the matrix is a list of callbacks.
     *
     * @var array
     */
    protected $_observers = array();

    protected $_parents = array();

    protected $_options = array(
        'notifyParents' => true,
        'queue' => true,
    );

    /**
     * Pending notifications.
     *
     * @var array
     */
    protected $_pending = array();

    /**
     * Class constructor, should only be called by {@see getInstance()}.
     *
     * @param string Name of the dispatcher.
     * @param string Name of class to use for notifications.
     */
    protected function __construct($name, $className) {
        $this -> _name = $name;
        $this -> _notificationClass = $className;
        $this -> _observers[''] = array();
    }

    function __toString() {
        return __CLASS__ . '()'; // FIXME add name, notification class name
    }

    /**
     * Check a callback for validity.
     *
     * @param mixed Either a valid PHP callback or an object. If an object
     * is provided, it must have an update() method.
     * @return mixed The "callable name" for the callback.
     * @throws AP5L_Event_Exception If the callback is not valid.
     */
    protected function _checkCallback(&$callback) {
       if (!is_string($callback) || !is_callable($callback, false, $reg)) {
            if (is_object($callback) && !is_callable(array($callback, 'update'), false, $reg)
            ) {
                throw new AP5L_Event_Exception(
                    'Callback object must have an update() method.'
                );
            }
            $callback = array($callback, 'update');
        } else {
            throw new AP5L_Event_Exception('Invalid callback.');
        }
        return $callback;
    }

    protected function _notifyParents($notification, $eventName, $options) {
        foreach ($this -> _parents as $parent) {
            $parent -> notify($notification, $eventName, $options);
        }
        return $this;
    }

    protected function _postPending($callback, $eventName = '', $class = null) {
        if (isset($this -> _pending[$eventName])) {
            foreach ($this -> _pending[$eventName] as $notification) {
                if (!$notification -> isCanceled()) {
                    $objClass = get_class($notification -> getObject());
                    if (empty($class) || $objClass instanceof $class) {
                        call_user_func_array($callback, array($notification));
                        $notification -> posted();
                    }
                }
            }
        }
    }

    /**
     * Attach an observer.
     *
     * @param object The observer object to attach.
     */
    public function attach($observer) {
        if (! isset($this -> _observers[''][''])) {
            $this -> _observers[''][''] = array();
        }
        $callback = array($observer, 'update');
        foreach ($this -> _observers[''][''] as $old) {
            if ($old === $callback) {
                // Observer is a duplicate.
                return false;
            }
        }
        $this -> _observers[''][''][] = $callback;
        $this -> _postPending($callback);
        return true;
    }

    /**
     * Detach an observer.
     *
     * @param object The observer object to detach.
     */
    public function detach($observer) {
        $callback = array($observer, 'update');
        foreach ($this -> _observers[''][''] as $key => $old) {
            if ($old === $callback) {
                return $this -> _observers[''][''][$key];
                return true;
            }
        }
        return false;
    }

    protected function _listen($callback, $eventName, $className) {
        if (! isset($this -> _observers[$eventName][$className])) {
            $this -> _observers[$eventName][$className] = array();
        }
        foreach ($this -> _observers[$eventName][$className] as $old) {
            if ($old === $callback) {
                // Observer is a duplicate.
                return false;
            }
        }
        $this -> _observers[$eventName][$className][] = $callback;
        $this -> _postPending($callback, $eventName, $className);
        return true;
    }

    /**
     * Returns a notification dispatcher instance.
     *
     * @param string Name of the notification dispatcher.
     * @param string Class name for notification objects, must implement
     * update().
     * @return object AP5L_Event_Dispatcher
     */
    public static final function getInstance(
        $name = '', $notificationClass = 'AP5L_Event_Notification'
    ) {
        if (
            !isset(self::$_instances[$name])
            || !isset(self::$_instances[$name][$notificationClass])
        ) {
            self::$_instances[$name][$notificationClass] =
                new AP5L_Event_Dispatcher($name, $notificationClass);
        }
        return self::$_instances[$name][$notificationClass];
    }

    /**
     * Get the name of the notification class used by this dispatcher.
     *
     * @return string Notification class name.
     */
    public function getNotificationClassName()     {
        return $this -> notificationClass;
    }

    public function getPending() {
        return $this -> pending;
    }

    public function listen($callback, $eventName = '', $className = '') {
        $this -> _checkCallback($callback);
        if (is_array($eventName)) {
            foreach ($eventName as $ename) {
                if (is_array($className)) {
                    foreach ($className as $cname) {
                        $this -> _listen($callback, $ename, $cname);
                    }
                } else {
                    $this -> _listen($callback, $ename, $className);
                }
            }
        } else {
            if (is_array($className)) {
                foreach ($className as $cname) {
                    $this -> _listen($callback, $eventName, $cname);
                }
            } else {
                $this -> _listen($callback, $eventName, $className);
            }
        }
    }

    /**
     * Posts a notification object.
     *
     * @param AP5L_Event_Notification The Notification object
     * @param string Name of object class passed in notification object
     * @param string The name of the notification event.
     * @param array Options. Possible options include:
     * <ul><li>"notifyParents" Set id the event should be passed to parent
     * dispatchers, defaults to true.
     * </li><li>"queue" Set if the notification should be queued for
     * future listeners, defaults to true.
     * </li></ul>
     * @return object The notification object.
     */
    public function notify(
        AP5L_Event_Notification $notification,
        $eventName = null,
        $options = array()
    ) {
        if ($notification -> isCanceled()) {
            return $notification;
        }
        $myOptions = array_merge($this -> _options, $options);
        $className = get_class($notification -> getObject());
        $eventName = ($eventName === null) ? $notification -> getName() : $eventName;

        if ($myOptions['queue']) {
            $this -> _pending[$eventName][] = $notification;
        }

        // Notify observers of this specific event and class
        if (isset($this -> _observers[$eventName][$className])) {
            foreach ($this -> _observers[$eventName][$className] as $observer) {
                call_user_func_array($observer, array(&$notification));
                $notification -> posted();
            }
        }

        // Notify observers of this specific event and any class
        if (isset($this -> _observers[$eventName][''])) {
            foreach ($this -> _observers[$eventName][''] as $observer) {
                call_user_func_array($observer, array(&$notification));
                $notification -> posted();
            }
        }

        // Notify observers of any event and this specific class
        if (isset($this -> _observers[''][$className])) {
            foreach ($this -> _observers[''][$className] as $observer) {
                call_user_func_array($observer, array(&$notification));
                $notification -> posted();
            }
        }

        // Notify global observers
        if (isset($this -> _observers[''][''])) {
            foreach ($this -> _observers[''][''] as $observer) {
                call_user_func_array($observer, array(&$notification));
                $notification -> posted();
            }
        }

        // Notify parent dispatchers
        if ($myOptions['notifyParents']) {
            $this -> _notifyParents($notification, $eventName, $options);
        }
        return $notification;
    }

    public function post($object, $eventName, $info = array(), $options = array()) {
        $notification = new $this -> _notificationClass($object, $eventName, $info);
        return $this -> notify($notification, $eventName, $options);
    }

    public function setOption($optionName, $value) {
        if (! isset($this -> _options[$optionName])) {
            throw new AP5L_Event_Exception('Unknown option name "' . $optionName . '"');
        }
        $this -> _options[$optionName] = $value;
        // If we're not queuing, clear pending events.
        if ($optionName == 'queue' && !$value) {
            $this -> _pending = array();
        }
    }

    public function unlisten($callback, $eventName = '', $className = '') {
        $reg = $this -> _checkCallback($callback);
        foreach ($this -> _observers[$eventName][$className] as $key => $old) {
            if ($old === $reg) {
                unset($this -> _observers[$eventName][$className][$key]);
                return true;
            }
        }
        return false;
    }

}
