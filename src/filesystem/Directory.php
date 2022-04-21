<?php
/**
 * Abivia PHP Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl\Filesystem;

/**
 * Helper functions for file paths.
 */
class Directory extends \Apl\Php\InflexibleObject {
    /**
     * Destination directory in file copy operations.
     *
     * @var string
     */
    protected $_dest;

    /**
     * The current operation that update() is receiving events for.
     * @var string
     */
    protected $_oprn;

    /**
     * Test splice point for opendir.
     * @param string A file path
     * @return handle|boolean Directory handle or false on error.
     */
    static protected function _opendir($path) {
        return @opendir($path);
    }

    /**
     * Test splice point for readdir.
     * @param handle An open directory handle
     * @return string|boolean Next directory entry or false at end of directory.
     */
    static protected function _readdir($dh) {
        return readdir($dh);
    }

    /**
     * Clean up a directory by converting backslashes to slashes, removing
     * double-slashes, and ensuring a trailing slash is present or not present
     * (as required).
     *
     * @param string The directory to be cleaned.
     * @return string The clean directory.
     */
    static function clean($dir, $trailing = true) {
        $dir = str_replace('\\', '/', $dir);
        $dir = preg_replace('|/+|', '/', $dir);
        if (strlen($dir)) {
            $hasOne = $dir[strlen($dir) - 1] == '/';
            if ($trailing && !$hasOne) {
                $dir .= '/';
            } elseif(!$trailing && $hasOne) {
                $dir = substr($dir, 0, -1);
            }
        }
        return $dir;
    }

    /**
     * Recursively create a directory.
     *
     * @param string The directory to be added.
     * @param int Directory permissions.
     */
    static function create($dir, $mode = 0777) {
        if (\Apl::DS == '\\') {
            $mode = null;
        }
        $dir = self::clean($dir);
        $parts = explode('/', $dir);
        $path = '';
        while (!empty($parts)) {
            $sub = array_shift($parts);
            if ($sub == '') {
                continue;
            }
            $path .= $sub . '/';
            if (!is_dir($path)) {
                mkdir($path, $mode);
            }
        }
    }

    /**
     * Recursively copy a directory.
     *
     * @param string The source directory.
     * @param string The target directory.
     * @throws Exception on error.
     */
    static function copy($src, $dest) {
        $src = self::clean($src);
        self::create($dest);
        $listener = new Directory();
        $listener -> setOperation('updateCopy');
        $listener -> setDestination($dest);
        // Get a private event dispatcher
        $disp = \Apl\Event\Dispatcher::getInstance(__CLASS__);
        $disp -> setOption('queue', false);
        $disp -> listen($listener);
        // Create a directory listing, set the dispatcher
        $listing = new Listing();
        $listing -> setDispatcher($disp);
        try {
            // Get the listing
            $listing -> execute($src);
        } catch (\Exception $err) {
            // Stop listening
            $disp -> unlisten($listener);
            throw $err;
        }
        // Stop listening
        $disp -> unlisten($listener);
    }

    /**
     * Recursively remove a directory.
     *
     * @param string The directory to be removed.
     * @throws Exception on error.
     */
    static function delete($dir) {
        $dir = self::clean($dir);
        $listener = new Directory();
        $listener -> setOperation('updateDelete');
        // Get a private event dispatcher
        $disp = \Apl\Event\Dispatcher::getInstance(__CLASS__);
        $disp -> setOption('queue', false);
        $disp -> listen($listener);
        // Create a directory listing, set the dispatcher
        $listing = new Listing();
        $listing -> setDispatcher($disp);
        try {
            // Get the listing
            $listing -> execute($dir);
        } catch (Exception $err) {
            // Stop listening
            $disp -> unlisten($listener);
            throw $err;
        }
        // Stop listening
        $disp -> unlisten($listener);
    }

    /**
     * Get a simple list of directories in a directory.
     *
     * @param string The base directory to start scanning from.
     * @param options Processing options:
     * <ul><li>"exclude" a regular expression that will exclude an entry from the
     * return results we matched. Optional, default is to include all.
     * </li><li>"include" a regular expression that must be matched before an entry
     * is included in the return results. Optional, default is to include all.
     * </li><li>"relative" return results relative to the base directory if set,
     * absolute paths if false (default true).
     * </li><li>"sort" if set, the returned file list is sorted (default true).
     * </li><li>"transform" optional filename transform. If set, this must be an
     * array of two elements, the match expression and the replacement text. These are
     * passed to the preg_replace() function.
     * </li></ul>
     * @return array|false If files are captured, the file list is returned. If
     * only directories are captured, then they are returned. If neither are
     * selected then the result is false.
     */
    static function dirList($dir, $options = array()) {
        /*
         * Process the options.
         */
        $relativePath = isset($options['relative']) ? $options['relative'] : true;
        $sort = isset($options['sort']) ? $options['sort'] : true;
        if (
            isset($options['transform'])
            && (!is_array($options['transform']) || count($options['transform']) < 2)
        ) {
            throw new \Apl\Exception(
                'The "transform" option value must be a two element array.', 1
            );
        }
        // Get the list
        $dir = self::clean($dir);
        $path = $dir;
        if (! @is_dir($path)) {
            throw new \Apl\Exception('Scan error. ' . $path . ' is not a directory.', 1);
        }
        if (! ($dh = self::_opendir($path))) {
            throw new \Apl\Exception('Scan error. Unable to open ' . $path, 2);
        }
        $prefix = ($relativePath ? '' : $path);
        /*
         * Walk through the directory collecting matching files.
         */
        $list = array();
        while (($fileName = self::_readdir($dh)) !== false) {
            $fid = $path . $fileName;
            if (filetype($fid) == 'dir') {
                if ($fileName == '.' || $fileName == '..') {
                    continue;
                }
                if (
                    isset($options['include'])
                    && !preg_match($options['include'], $fileName)
                ) {
                    continue;
                }
                if (
                    isset($options['exclude'])
                    && preg_match($options['exclude'], $fileName)
                ) {
                    continue;
                }
                $entry = $prefix . $fileName;
                if (isset($options['transform'])) {
                    $entry = preg_replace(
                        $options['transform'][0], $options['transform'][1], $entry
                    );
                }
                $list[] = $entry;
            }
        }
        closedir($dh);
        if ($sort) {
            sort($list);
        }
        return $list;
    }

    /**
     * Get a simple list of files in a directory.
     *
     * @param string The base directory to start scanning from.
     * @param options Processing options:
     * <ul><li>"exclude" a regular expression that will exclude an entry from the
     * return results we matched. Optional, default is to include all.
     * </li><li>"include" a regular expression that must be matched before an entry
     * is included in the return results. Optional, default is to include all.
     * </li><li>"relative" return results relative to the base directory if set,
     * absolute paths if false (default true).
     * </li><li>"sort" if set, the returned file list is sorted (default true).
     * </li><li>"transform" optional filename transform. If set, this must be an
     * array of two elements, the match expression and the replacement text. These are
     * passed to the preg_replace() function.
     * </li></ul>
     * @return array|false If files are captured, the file list is returned. If
     * only directories are captured, then they are returned. If neither are
     * selected then the result is false.
     */
    static function fileList($dir, $options = array()) {
        /*
         * Process the options.
         */
        $relativePath = isset($options['relative']) ? $options['relative'] : true;
        $sort = isset($options['sort']) ? $options['sort'] : true;
        if (
            isset($options['transform'])
            && (!is_array($options['transform']) || count($options['transform']) < 2)
        ) {
            throw new \Apl\Exception(
                'The "transform" option value must be a two element array.', 1
            );
        }
        // Get the list
        $dir = self::clean($dir);
        $path = $dir;
        if (! @is_dir($path)) {
            throw new \Apl\Exception('Scan error. ' . $path . ' is not a directory.', 1);
        }
        if (! ($dh = self::_opendir($path))) {
            throw new \Apl\Exception('Scan error. Unable to open ' . $path, 2);
        }
        $prefix = ($relativePath ? '' : $path);
        /*
         * Walk through the directory collecting matching files.
         */
        $list = array();
        while (($fileName = self::_readdir($dh)) !== false) {
            $fid = $path . $fileName;
            if (filetype($fid) == 'file') {
                if (
                    isset($options['include'])
                    && !preg_match($options['include'], $fileName)
                ) {
                    continue;
                }
                if (
                    isset($options['exclude'])
                    && preg_match($options['exclude'], $fileName)
                ) {
                    continue;
                }
                $entry = $prefix . $fileName;
                if (isset($options['transform'])) {
                    $entry = preg_replace(
                        $options['transform'][0], $options['transform'][1], $entry
                    );
                }
                $list[] = $entry;
            }
        }
        closedir($dh);
        if ($sort) {
            sort($list);
        }
        return $list;
    }

    /**
     * Set a destination directory.
     *
     * @param string The target diretory
     */
    function setDestination($dest) {
        $this -> _dest = self::clean($dest);
    }

    /**
     * Set the current operation.
     *
     * @param string The operation name.
     */
    function setOperation($oprn) {
        $this -> _oprn = $oprn;
    }

    /**
     * Receive a file event.
     *
     * This method uses events from the directory walk.
     *
     * @param \Apl\Event\Notification The event object.
     */
    function update(\Apl\Event\Notification $subject) {
        $method = $this -> _oprn;
        $this -> $method($subject);
    }

    /**
     * Receive a file event.
     *
     * This method uses events from the directory walk to copy files.
     *
     * @param \Apl\Event\Notification The event object.
     */
    function updateCopy(\Apl\Event\Notification $subject) {
        switch ($subject -> getName()) {
            case Listing::EVENT_ADD_FILE: {
                $info = $subject -> getInfo();
                copy($info['fullname'], $this -> _dest . $info['relname']);
            }
            break;

            case Listing::EVENT_LAST_DIR: {
                $info = $subject -> getInfo();
                // This will throw warnings on permissions errors
                @mkdir($this -> _dest . $info['relname']);
            }
            break;

        }
    }

    /**
     * Receive a file event.
     *
     * This method uses events from the directory walk to delete files.
     *
     * @param \Apl\Event\Notification The event object.
     */
    function updateDelete(\Apl\Event\Notification $subject) {
        switch ($subject -> getName()) {
            case Listing::EVENT_ADD_FILE: {
                $info = $subject -> getInfo();
                unlink($info['fullname']);
            }
            break;

            case Listing::EVENT_LAST_DIR: {
                $info = $subject -> getInfo();
                if (\Apl::DS == '\\') {
                    // Windows is special. Try flushing a subdir delete...
                    if (($dh = opendir($info['fullname']))) {
                        closedir($dh);
                    }
                }
                // This will throw warnings on permissions errors
                rmdir($info['fullname']);
            }
            break;

        }
        clearstatcache();
    }

}
