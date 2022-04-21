<?php
/**
 * Abivia PHP5 Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl\Filesystem;

/**
 * Not ready... this is just a copy of Listing at this point.
 */
class Iterator {
    /**
     * Base directory for the listing. Files in the listing are relative to this
     * directory.
     *
     * @var string
     */
    protected $_baseDir = '';

    /**
     * Elements in the directory stack
     *
     * @var array
     */
    protected $_dirStack = array();

    protected $_filterCallback;

    protected $_sort = true;

    /**
     * Walk a directory tree, accumulating file paths.
     *
     * @param string Scan mode. If set to "tests", accumulates files of the form
     * "*-test.php"
     */
    protected function _dirWalk($dir) {
        $path = $this -> _startDir . $dir;
        if (! @is_dir($path)) {
            throw new \Apl\Exception('Scan error. ' . $path . ' is not a directory.', 1);
        }
        if(! ($dh = @opendir($path))) {
            throw new \Apl\Exception('Scan error. Unable to open ' . $path, 2);
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
                     * Add matching file paths to the results
                     */
                    if (! $this -> _isInList($fid)) {
                        continue;
                    }
                    $list[] = $fileName;
                } break;
            }
        }
        if ($this -> _sort) {
            sort($list);
        }

        foreach ($list as $fileName) {
            $this -> _files[] = $path . $fileName;
        }
        if ($this -> _sort) {
            sort($subList);
        }
        foreach ($subList as $subDir) {
            $this -> _dirWalk($dir . $subDir . '/');
        }
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
            return call_user_func($this -> _filterCallback, $fid);
        }
        return true;
    }

    /**
     * Open an iterator
     *
     * @param string The base directory to start scanning from. The resulting
     * file list will be relative to this directory.
     * @param options Processing options: "callback" a function/method to
     * determine if a file should be included (see {@see isInList()}); "sort" if
     * set, the returned file list is sorted (default true).
     */
    function open($baseDir, $options = array()) {
        $this -> _filterCallback = isset($options['callback']) ? $options['callback'] : '';
        $this -> _sort = isset($options['sort']) ? $options['sort'] : true;
        $this -> _baseDir = Directory::clean($baseDir);
        $this -> _dirStack = array();
    }

    /**
     * Get the next fie
     */
    function next() {
    }

}
