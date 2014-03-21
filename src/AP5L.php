<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: AP5L.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/*
 * Workaround for not being able to evaluate constant expressions when defining
 * a class constant.
 */
if (PHP_SAPI == 'cli') {
    /**
     * Workaround class to define elements of AP5L
     *
     * @package AP5L
     */
    class AP5Lcore {
        /**
         * Shorthand for "new line".
         */
        const NL = PHP_EOL;
    }
} else {
    /**
     * @ignore
     */
    class AP5Lcore {
        const NL = '<br />';
    }
}

/**
 * Library-wide definitions and setup. Includes library constants and shortcuts,
 * autoloader, debugging support.
 *
 * @package AP5L
 */
class AP5L extends AP5Lcore {
    /**
     * Shorthand for carrige return.
     */
    const CR = "\r";

    /**
     * Shorthand for directory separator.
     */
    const DS = DIRECTORY_SEPARATOR;

    /**
     * Shorthand for line feed.
     */
    const LF = "\n";

    /**
     * Shorthand for path separator.
     */
    const PS = PATH_SEPARATOR;

    /**
     * Debug dump object
     */
    static protected $_debug;

    /**
     * Runtime install flag, set when the installer has been run.
     */
    static private $_isInstalled = false;

    /**
     * Add the AP5L base path into an array of paths.
     *
     * If the path is already in the current list of paths, no action is taken.
     * If there is a current directory in the list, the base path is inserted
     * immediately after it. If there is no current directory, the path is added
     * to the beginning.
     *
     * @param array List of the current path directories.
     * @param string The AP5L base path.
     * @param string Current directory. Optional, defaults to ".".
     * @return boolean true if the base path was added, false if the base path
     * is already in the directory list.
     */
    static function addPath(&$currPath, $here, $current = '.') {
        if (in_array($here, $currPath, true)) {
            return false;
        }
        if (($posn = array_search($current, $currPath)) !== false) {
            $currPath = array_splice($currPath, $posn, 0, $here);
        } else {
            array_unshift($currPath, $here);
        }
        return true;
    }

    /**
     * Autoloader for AP5L classes.
     *
     * @param string Class name to load.
     * @return boolean True if the class was loaded, false if the class name was
     * not in AP5L scope.
     */
    static function autoload($className) {
        if (substr($className, 0, 5) != 'AP5L_') {
            if (self::$_debug) {
                self::$_debug -> writeln('Passed autoload request ' . $className);
            }
            return false;
        }
        $components = explode('_', $className);
        array_shift($components);
        if (count($components)) {
            $final = array_pop($components);
            $rootPath = dirname(__FILE__) . AP5L::DS;
            $path = $rootPath;
            foreach ($components as $dir) {
                $path .= strtolower($dir) . AP5L::DS;
            }
            $path .= $final . '.php';
        } else {
            return false;
        }
        if (self::$_debug) {
            self::$_debug -> writeln('Autoload ' . $className . ' from ' . $path);
        }
        /*
         * Despite the performance hit, we check for the file because @include
         * can supress a bunch of useful information on a fatal error.
         */
        if ($exists = file_exists($path)) {
            include $path;
        } else {
            $good = false;
        }
        $good = class_exists($className, false);
        if (! $good && self::$_debug) {
            self::$_debug -> writeln(
                'Failed autoload ' . $className . ' from ' . $path
                . ($exists ? '' : ' (file does not exist)')
            );
        }
        return $good;
    }

    /**
     * Get an instance of the main diagnostic output handler, or a void handler
     * if none set.
     *
     * @param boolean Optional, default true. Allow conditional output. If
     * false, output is supressed by reurning an AP5L_Debug_Void object.
     * @return AP5L_Debug The current debug output handler, if any.
     */
    static function &getDebug($enabled = true) {
        if (! $enabled) {
            $nothing = new AP5L_Debug_Void();
            return $nothing;
        }
        if (! self::$_debug instanceof AP5L_DebugProvider) {
            self::$_debug = new AP5L_Debug_Void();
        }
        return self::$_debug;
    }

    /**
     * "Runtime Install" the AP5L Library.
     *
     * This method sets up the library for use. It defines runtime dependent
     * variables, can define an autoloader, add the library path to the include
     * path, etc.
     *
     * @param array Associative array of options. Options are [autoload]
     * (boolean) default true; [include_path] (boolean) default false.
     */
    static function install($options = array()) {
        if (self::$_isInstalled) {
            return false;
        }
        $optDefs = array(
            'autoload' => true,
            'include_path' => false,
        );
        foreach ($optDefs as $key => &$opt) {
            if (isset($options[$key])) {
                $opt = $options[$key];
            }
        }
        if ($optDefs['autoload']) {
            /*
             * If an autoloder is already defined and there is no autoload
             * stack, add the user's function to the stack first.
             */
            if (function_exists('__autoload') && spl_autoload_functions() === false) {
                spl_autoload_register('__autoload');
            }
            spl_autoload_register(array('AP5L', 'autoload'));
        }
        if ($optDefs['include_path']) {
            $here = dirname(__FILE__);
            $currPath = explode(AP5L::PS, get_include_path());
            if (self::addPath($currPath, $here)) {
                set_include_path(implode(AP5L::PS, $currPath));
            }

        }
        self::$_isInstalled = true;
        return true;
    }

    static function setDebug(&$debug) {
        if (! $debug instanceof AP5L_DebugProvider) {
            return false;
        }
        self::$_debug = $debug;
        return true;
    }

}
