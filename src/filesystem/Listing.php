<?php
/**
 * Abivia PHP5 Library
 *
 * @package AP5L
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Listing.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Walk a directory tree, accumulating a list of files.
 *
 * @package AP5L
 */
class AP5L_Filesystem_Listing extends AP5L_Php_InflexibleObject {
    const EVENT_ADD_DIR = 'addDir';
    const EVENT_ADD_FILE = 'addFile';
    const EVENT_LAST_DIR = 'lastDir';
    const EVENT_LAST_FILE = 'lastFile';

    /* These constants deprecated in favour of copies in AP5L_Filesystem */
    const TYPE_DIRECTORY = 1;
    const TYPE_FILE = 2;
    /**
     * Base directory for the listing. Files in the listing are relative to this
     * directory.
     *
     * @var string
     */
    protected $_baseDir = '';

    /**
     * The directories in the listing.
     *
     * @var array
     */
    protected $_dirs = array();

    /**
     * Directory sequence control. Only meaningful value is "first".
     *
     * @var string
     */
    protected $_dirSeq = '';

    /**
     * The directory tree structure
     *
     * @var array
     */
    protected $_dirTree = array();

    /**
     * Optional event dispatcher.
     *
     * @var callback
     */
    protected $_dispatcher = null;

    /**
     * The files in the listing.
     *
     * @var array
     */
    protected $_files = array();

    /**
     * File filter callback function.
     *
     * @var string|array
     */
    protected $_filterCallback;

    /**
     * Filter type mask.
     *
     * @var integer
     */
    protected $_filterMask;
    
    /**
     * User's option seclections
     */
    protected $_options;

    /**
     * Relative paths fag.
     *
     * @var boolean
     */
    protected $_relativePath = false;

    /**
     * Sort results flag.
     *
     * @var boolean
     */
    protected $_sort = true;

    /**
     * Walk a directory tree, accumulating file paths.
     *
     * @param string Scan mode. If set to "tests", accumulates files of the form
     * "*-test.php"
     */
    protected function _dirWalk($dir, $leaf = '') {
        $path = $this -> _baseDir . $dir;
        if (! @is_dir($path)) {
            throw new AP5L_Exception('Scan error. ' . $path . ' is not a directory.', 1);
        }
        if(! ($dh = @opendir($path))) {
            throw new AP5L_Exception('Scan error. Unable to open ' . $path, 2);
        }
        $prefix = ($this -> _relativePath ? $dir : $path);
        $this -> _dirs[] = $prefix;
        if ($this -> _dispatcher) {
            $dirInfo = array('fullname' => $path, 'name' => $leaf, 'relname' => $dir);
            $this -> _dispatcher -> notify(
                new AP5L_Event_Notification(
                    $this,
                    self::EVENT_ADD_DIR,
                    $dirInfo
                )
            );
        }
        /*
         * Walk through the directory collecting files and subdirectories. Then
         * optionally sort them to get the correct sequence.
         */
        $list = array();
        $subList = array();
        while (($fileName = readdir($dh)) !== false) {
            $fid = $path . $fileName;
            switch (filetype($fid)) {
                case 'dir': {
                    if ($fileName != '.' && $fileName != '..') {
                        $subList[] = $fileName;
                    }
                } break;

                case 'file': {
                    //echo $fileName . PHP_EOL;
                    /*
                     * If we're capturing files, add file paths that don't get
                     * filtered to the results.
                     */
                    if (
                        $this -> _filterMask & AP5L_FileSystem::TYPE_FILE
                        && $this -> isInList($fid)
                    ) {
                        $list[] = $fileName;
                    }
                } break;
            }
        }
        closedir($dh);
        if ($this -> _sort) {
            sort($list);
            sort($subList);
        }
        if ($this -> _dirSeq == 'first') {
            foreach ($subList as $subDir) {
                $subPath = $dir . $subDir . '/';
                if ($this -> isInDirList($subPath)) {
                    $this -> _dirWalk($subPath, $subDir);
                }
            }
        }
        foreach ($list as $fileName) {
            $filePath = $prefix . $fileName;
            $this -> _files[] = $prefix . $fileName;
            if ($this -> _dispatcher) {
                $this -> _dispatcher -> notify(
                    new AP5L_Event_Notification(
                        $this,
                        self::EVENT_ADD_FILE,
                        array(
                            'fullname' => $path . $fileName,
                            'name' => $fileName,
                            'reldir' => $dir,
                            'relname' => $dir . $fileName
                        )
                    )
                );
            }
        }
        if ($this -> _dispatcher) {
            // End of the file list
            $this -> _dispatcher -> notify(
                new AP5L_Event_Notification($this, self::EVENT_LAST_FILE, $dirInfo)
            );
        }
        if ($this -> _dirSeq != 'first') {
            foreach ($subList as $subDir) {
                $subPath = $dir . $subDir . '/';
                if ($this -> isInDirList($subPath)) {
                    $this -> _dirWalk($subPath, $subDir);
                }
            }
        }
        if ($this -> _dispatcher) {
            // End of the directory list
            $this -> _dispatcher -> notify(
                new AP5L_Event_Notification($this, self::EVENT_LAST_DIR, $dirInfo)
            );
        }
    }
    
    protected function _getOptions() {
        
    }

    /**
     * Get a file listing.
     *
     * @param string The base directory to start scanning from.
     * @param options Processing options:
     * <ul><li>"callback" a function/method to determine which files / direcrories
     * should be included. Parameters are the path and the type (file or directory)
     * (see {@see isInList()}).
     * </li><li>"directories" When to fire directory traversal, first or last.
     * Default is last.
     * </li><li>"filter" mask for return types (use the AP5L_FileSystem::TYPE_
     * constants). Default is both files and directories.
     * </li><li>"relative" return results relative to the base directory if set,
     * absolute paths if false (default false).
     * </li><li>"sort" if set, the returned file list is sorted (default true).
     * </li></ul>
     * @return array|false If files are captured, the file list is returned. If
     * only directories are captured, then they are returned. If neither are
     * selected then the result is false.
     */
    function execute($baseDir, $options = array()) {
        $this -> _dirs = array();
        $this -> _files = array();
        /*
         * Process the options.
         */
        $this -> _options = $options;
        $this -> _getOptions();
        $this -> _filterCallback = isset($options['callback']) ? $options['callback'] : '';
        $this -> _filterMask = isset($options['filter'])
            ? $options['filter']
            : AP5L_FileSystem::TYPE_DIRECTORY | AP5L_FileSystem::TYPE_FILE
        ;
        if (! $this -> _filterMask) {
            // Everything is masked, this is simple!
            return false;
        }
        $this -> _dirSeq = isset($options['directories'])
            ? strtolower($options['directories']) : 'last';
        $this -> _relativePath = isset($options['relative']) ? $options['relative'] : false;
        $this -> _sort = isset($options['sort']) ? $options['sort'] : true;
        /*
         * Get the list
         */
        $this -> _baseDir = AP5L_Filesystem_Directory::clean($baseDir);
        $this -> _dirWalk('');
        if ($this -> _filterMask & AP5L_FileSystem::TYPE_FILE) {
            $result = $this -> _files;
        } else {
            $result = $this -> _dirs;
        }
        return $result;
    }
    
    /**
     * Create a filesystem listing object.
     * 
     * @param string Filesystem type. Should be "local" or "ftp"
     * @returns object Lsisting object.
     * @throws AP5L_Exception If the filesystem type is not recognized.
     */
    static function &factory($fsType = 'local') {
        $fsType = ucfirst(strtolower($fsType));
        if ($fsType != '' && $fsType != 'Local') {
            $fsType = __CLASS__ . '_' . $fsType;
        } else {
            $fsType = __CLASS__;
        }
        if (!class_exists($fsType)) {
            throw AP5L_Exception::factory('', 'Class ' . $fsType . ' does not exist.');
        }
        return new $fsType;
    }

    /**
     * Return the most recent directory list.
     *
     * @return array Directories found by the last execute call.
     */
    function getDirs() {
        return $this -> _dirs;
    }

    /**
     * Return the most recent file list.
     *
     * @return array Files found by the last execute call.
     */
    function getFiles() {
        return $this -> _files;
    }

    /**
     * Return the most recent file list.
     *
     * @deprecated
     * @return array Files found by the last execute call.
     */
    function getList() {
        return $this -> getFiles();
    }

    /**
     * Direcrory inclusion filter.
     *
     * The default filter includes all direcrories. This functionality can be changed
     * by either passing a callback function to execute() via the "callback"
     * option, or by overriding the method.
     *
     * @param string The full file path of the directory to be filtered.
     * @return boolean True if the directory should be included.
     */
    function isInDirList($fid) {
        if ($this -> _filterCallback) {
            return call_user_func(
                $this -> _filterCallback, $fid, AP5L_FileSystem::TYPE_DIRECTORY
            );
        }
        return true;
    }

    /**
     * File inclusion filter.
     *
     * The default filter includes all files. This functionality can be changed
     * by either passing a callback function to execute() via the "callback"
     * option, or by overriding the method.
     *
     * @param string The full file path of the file to be filtered.
     * @return boolean True if the file should be included.
     */
    function isInList($fid) {
        if ($this -> _filterCallback) {
            return call_user_func(
                $this -> _filterCallback, $fid, AP5L_FileSystem::TYPE_FILE
            );
        }
        return true;
    }

    /**
     * Set a dispatcher to receive event notifications.
     *
     * @param AP5L_Event_Dispatcher A dispatcher object.
     * @return void
     */
    function setDispatcher($dispatcher) {
        $this -> _dispatcher = $dispatcher;
    }

}
