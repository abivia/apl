<?php
/**
 * Abivia PHP Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Debug.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

namespace Apl;
/**
 * Debug: a diagnostic output control class.
 *
 * This class attempts to solve the problem of selectively controlling what
 * diagnostic information is displayed at runtime. The class is a singleton that
 * maintains an array of debug settings. The format of a setting is
 * "type@namespace::class::method". Any value can be associated with a setting.
 * All parts of a setting are optional; an empty setting string refers to the
 * global default setting.
 *
 * The setting type is managed by the application. An example of using a type
 * might be to use "backtrace" to control the generation of call stacks, while
 * leaving default diagnostic output on. This would be achieved with:
 *
 * <code>
 * $dbg = Apl\Debug::getInstance();
 * $dbg -> setState('', true);
 * $dbg -> setState ('backtrace@', false);
 * </code>
 *
 * The remainder of the setting string is evaluated in a hierarchical manner
 * (namespaces are part of the syntax in anticipation of the namespace feature
 * in PHP 5.3).
 *
 * The basic diagnostic output routine, write() takes the output string as the
 * first parameter, and an optional setting (or setting handle) as the second
 * parameter. If the setting is not provided, or if only a setting type is
 * provided, the debug class will assume that the current namespace, class, and
 * method have been passed. For example in this code:
 *
 * <code>
 * class MyClass_Driver {
 *
 *     function foo() {
 *         Apl::getDebug() -> write('some message', 'details@');
 *     }
 * }
 * </code>
 *
 * The setting 'details@' will be interpreted as 'details@::MyClass_Driver::
 * foo'. The hierarchical evaluation will cause the manager to look for settings
 * in the following order: 'details@::MyClass_Driver::foo', 'details@::
 * MyClass_Driver', 'details@::MyClass_', 'details@::MyClass', 'details@', and
 * finally '' (the global default). If the first value found in this sequence
 * evaluates true, then output will be generated.
 *
 * This allows the application to exert fine control over the generation of
 * diagnostics at any level. If a explicit setting is not set, then the next
 * step in the hierarchy is evaluated until a value is found.
 *
 * Note that there is no requirement to use actual namespace, class, or method
 * names. An application can use any values in the settings, but when this is
 * done, the setting string needs to be passed in as a handle explicitly.
 */
abstract class Debug implements DebugProvider {
    /**
     * Mapping from setting key to handle.
     *
     * @var array.
     */
    protected $_handleMap = array();

    /**
     * Cached references to settings.
     *
     * @var array
     */
    protected $_handles = array();

    /**
     * Handle refresh required flag.
     *
     * @var boolean
     */
    protected $_handleRefresh = false;

    /**
     * Reference to a global instance of the default debugger.
     */
    static protected $_instance;

    /**
     * New line string. Variable so output can be routed to other streams.
     *
     * @var string
     */
    public $newLine = Apl::NL;

    /**
     * Output control settings. Array indexed by hierarchical key
     * (information_type@namespace::class::method) (class keys with a trailing
     * underscore refer to the hierarchy, those without refer to a specific
     * class). Empty string represents the default.
     *
     * @var string
     */
    protected $_settings = array('@::::' => false);

    /**
     * Verify that handles reference the correct setting.
     *
     * The handle refresh flag is set whenever
     */
    protected function _checkHandles() {
        if (! $this -> _handleRefresh) {
            return;
        }
        foreach ($this -> _handleMap as $key => $handle) {
            if (isset($this -> _settings[$key])) {
                $this -> _handles[$handle] = &$this -> _getComponent($key);
            } else {
                unset($this -> _handles[$handle]);
            }
        }
        $this -> _handleRefresh = false;
    }

    /**
     * Decompose a debug key into subcomponents.
     */
    static protected function _decompose($key) {
        $elements = array();
        /*
         * If there is a specified infotype, add "infotype@" to the
         * decomposition. Otherwise just add "@".
         */
        if (($posn = strpos($key, '@')) !== false) {
            $elements[] = substr($key, 0, $posn + 1);
            $key = substr($key, $posn + 1);
        } else {
            $elements[] = '@';
        }
        $terms = explode('::', $key);
        /*
         * Add package::class::method elements
         */
        $elements = array_merge($elements, self::_decomposePackage(array_shift($terms)));
        $elements[] = '::';
        if (count($terms)) {
            $elements = array_merge($elements, self::_decomposePackage(array_shift($terms)));
        }
        $elements[] = '::';
        if (count($terms)) {
            $elements[] = array_shift($terms);
        }
        return $elements;
    }

    /**
     * Break a class name down into subcomponents.
     *
     * Given a full class name like My_Class_Name, this function will return an
     * array of ('My_', 'Class_', 'Name').
     *
     * @param string A class name, possibly with components delimited by
     * underscores.
     * @return array An array of each component, preserving underscores.
     */
    static protected function _decomposePackage($name) {
        $elements = explode('_', $name);
        $last = array_pop($elements);
        $result = array();
        foreach ($elements as $element) {
            $result[] = $element . '_';
        }
        if ($last != '') {
            $result[] = $last;
        }
        return $result;
    }

    /**
     * Get the settings value for a setting.
     *
     * Walks up the hierarchy of settings until a match is found.
     *
     * @param string A scope identifier of the form [type@][namespace]::class[::
     * method]. Examples ::MyClass, ::MyClass::method, network@::MyClass,
     * dumpconfig@space::Package_.
     * @return mixed The matching settings value for the key.
     */
    protected function &_getComponent($key) {
        $elements = self::_decompose($key);
        $suffix = '';
        while (count($elements)) {
            if (end($elements) == '') {
                // skip the element
            } elseif (end($elements) == '::') {
                $suffix = '::' . $suffix;
            } else {
                $mkey = implode('', $elements) . $suffix;
                if (isset($this -> _settings[$mkey])) {
                    return $this -> _settings[$mkey];
                }
            }
            array_pop($elements);
        }
        return $this -> _settings['@::::'];
    }

    /**
     * Get information about where an application invoked the debug output
     * class.
     *
     * @return array A debug_backtrace() array entry detailing where Debug
     * was called.
     */
    protected function _getCaller() {
        $trace = debug_backtrace();
        for ($stack = 0; $stack < count($trace); ++$stack) {
            if ($trace[$stack]['class'] != __CLASS__) {
                /*
                 * Return the line that called us.
                 */
                $caller = $trace[$stack];
                $caller['line'] = $stack
                    ? $trace[$stack - 1]['line'] : '';
                return $caller;
            }
        }
    }

    /**
     * Get handle of caller.
     *
     * @return string A string of the form class::method, where the class and
     * the method are those of the module that invoked the Debug class.
     */
    protected function _getDefaultHandle() {
        $caller = $this -> _getCaller();
        return '::' . $caller['class']. '::' . $caller['function'];
    }

    /**
     * Normalize a key by parsing it into elements and reassembling it.
     *
     * This function ensures that all elements are represented.
     *
     * @param string A scope identifier of the form [type@][namespace]::class[::
     * method]. Examples ::MyClass, ::MyClass::method, network@::MyClass,
     * dumpconfig@space::Package_.
     * @return string A normalized key.
     */
    function _keyNormalize($key) {
        $elements = self::_decompose($key);
        return implode('', $elements);
    }

    abstract function _write($data);

    function backtrace($handle = null, $options = array()) {
        /*
            $trace = debug_backtrace();
            foreach ($trace as &$call) {
                if (isset($call['args'])) {
                    foreach  ($call['args'] as &$arg) {
                        if (is_object($arg)) {
                            $arg = get_class($arg) . ' Object';
                        } elseif (is_array($arg)) {
                            $arg = 'Array[' . count($arg) . ']';
                        }
                    }
                }
            }
            print_r($trace[0]);
        */
    }

    function flush() {
        $this -> _write($this -> _buffer);
        $this -> _buffer = '';
    }

    /**
     * Free a handle.
     *
     * Freeing the handle saves an unused handle from being re-evaluated each
     * time the settings change.
     *
     * @param int The handle to free.
     */
    function freeHandle($handle) {
        $map = array_search($this -> handleMap, $handle);
        if ($map !== false) {
            unset($this -> handleMap[$map]);
        }
        unset($this -> handles[$handle]);
    }

    /**
     * Get or assign a handle.
     *
     * @param string A scope identifier of the form [type@][namespace]::class[::
     * method]. Examples ::MyClass, ::MyClass::method, network@::MyClass,
     * dumpconfig@space::Package_.
     * @return int A handle identifier corresponding to the key.
     */
    function getHandle($key = null) {
        if ($key === null) {
            $key = $this -> _getDefaultHandle();
        }
        if (isset($this -> handleMap[$key])) {
            return $this -> handleMap[$key];
        }
        $handle = count($this -> _handles);
        $this -> handleMap[$key] = $handle;
        $this -> _handles[] = &$this -> _getComponent($key);
        return $handle;
    }

    /**
     * Get the class singleton.
     *
     * @return Apl\Debug
     */
    static function getInstance() {
        return self::$_instance;
    }

    /**
     * Get the current state setting for a key.
     *
     * @param string The key. Optional. If not provided, the caller's handle is
     * used.
     * @return mixed The current setting for the key.
     */
    function getState($key = null) {
        if (is_null($key)) {
            $key = $this -> _getDefaultHandle();
        }
        $state = $this -> _getComponent($key);
        return $state;
    }

    /**
     * See if debug output is enabled.
     *
     * This method is useful when there's significant computation required to
     * create a diagnostic string, by allowing a branch around it if it's not
     * required.
     *
     * @param int|string An optional debugger handle or scope identifier. See
     * {@see write()}.
     */
    function isEnabled($handle = null) {
        if (is_null($handle)) {
            $handle = $this -> _getDefaultHandle();
        }
        if (is_int($handle)) {
            $this -> _checkHandles();
            $enable = isset($this -> _handles[$handle])
                ? $this -> _handles[$handle] : false;
        } else {
            /*
             * If only a type is passed, add the package/class/method
             */
            if (substr($handle, -1) == '@') {
                $handle .= $this -> _getDefaultHandle();
            }
            $enable = $this -> _getComponent($handle);
        }
        return $enable;
    }

    /**
     * Get the class singleton.
     */
    static function setInstance(&$instance) {
        self::$_instance = $instance;
    }

    /**
     * Configure the debugger.
     *
     * @param string A scope identifier of the form [type@][namespace]::class[::
     * method]. Examples ::MyClass, ::MyClass::method, network@::MyClass,
     * dumpconfig@space::Package_.
     * @param mixed The corresponding value. Typically boolean, but it can be
     * anything.
     * @return The current object.
     */
    function &setState($key, $value) {
        $key = $this -> _keyNormalize($key);
        if (! isset($this -> _settings[$key])) {
            /*
             * This is a new setting. New settings may change the setting
             * hierarchy and cause a key to map to a new component, so we need
             * to do a handle refresh. The refresh will trigger the next time a
             * handle is used, which saves us from evaluating all handles every
             * time a new setting is made.
             */
            $this -> _handleRefresh = true;
        }
        $this -> _settings[$key] = $value;
        return $this;
    }

    /**
     * Clear a setting.
     *
     * @param string A scope identifier of the form [type@][namespace]::class[::
     * method]. Examples ::MyClass, ::MyClass::method, network@::MyClass,
     * dumpconfig@space::Package_.
     */
    function unsetState($key) {
        $key = $this -> _keyNormalize($key);
        if ($key == '@::::') {
            // Can't clear the default, set it false instead
            $this -> _settings[$key] = false;
            $this -> _handleRefresh = true;
        } elseif (isset($this -> _settings[$key])) {
            unset($this -> _settings[$key]);
            $this -> _handleRefresh = true;
        }
    }

    /**
     * Write diagnostic information.
     *
     * @param string The information to be written
     * @param int|string An optional debugger handle or scope identifier of the
     * form [type@] [namespace]:: class [:: method]. Examples ::MyClass, ::
     * MyClass:: method, network@:: MyClass, dumpconfig@space::Package_. If
     * missing, the caller's class and method are used.
     * @param array Options.
     */
    function write($data, $handle = null, $options = array()) {
        if (is_null($handle)) {
            $handle = $this -> _getDefaultHandle();
        }
        /*
         * Trap some cases where an echo x, y, z... has been poorly converted to
         * a function call.
         */
        if (
            func_num_args() > 3
            || (func_num_args() >= 3 && ! is_array($options))
        ) {
            $caller = $this -> _getCaller();
            $this -> _write(
                'Warning: Debug parameter error in '
                . $caller['class']. $caller['type'] . $caller['function']
                . ' at line ' . $caller['line'] . PHP_EOL
            );
        }
        if (is_int($handle)) {
            $this -> _checkHandles();
            $enable = isset($this -> _handles[$handle])
                ? $this -> _handles[$handle] : false;
        } else {
            /*
             * If only a type is passed, add the package/class/method
             */
            if (substr($handle, -1) == '@') {
                $handle .= $this -> _getDefaultHandle();
            }
            $enable = $this -> _getComponent($handle);
        }
        if ($enable) {
            $this -> _write($data);
        }
    }

    /**
     * Write diagnostic information, followed by a newline.
     *
     * @param string The information to be written
     * @param int|string An optional debugger handle or scope identifier of the
     * form [type@] [namespace]:: class [:: method]. Examples ::MyClass, ::
     * MyClass:: method, network@:: MyClass, dumpconfig@space::Package_. If
     * missing, the caller's class and method are used.
     * @param array Options.
     */
    function writeln($data, $handle = null, $options = array()) {
        $this -> write($data . $this -> newLine, $handle, $options);
    }

}

