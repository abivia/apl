<?php
/**
 * Abivia PHP5 Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl\Filesystem\Listing;

/**
 * Walk a directory tree, accumulating a list of files.
 */
class Ftp extends Apl\Filesystem\Listing {
    /**
     * FTP connection resource
     * @var resource
     */
    protected $_ftpc;

    /**
     * Host system
     * @var string
     */
    protected $_host;

    protected $_password = '';
    protected $_port;
    protected $_user = '';

    /**
     * Walk a directory tree, accumulating file paths.
     *
     * @param string Scan mode. If set to "tests", accumulates files of the form
     * "*-test.php"
     */
    protected function _dirWalk($dir, $leaf = '') {
        // This is in the wrong place, should be in execute; one the parent class is
        // refactored to put local filesystem stuff into a different class.
        $this -> _getOptions();
        $path = $this -> _baseDir . $dir;
        if (! @is_dir($path)) {
            throw new \Apl\Exception('Scan error. ' . $path . ' is not a directory.', 1);
        }
        if(! ($dh = @opendir($path))) {
            throw new \Apl\Exception('Scan error. Unable to open ' . $path, 2);
        }
        $prefix = ($this -> _relativePath ? $dir : $path);
        $this -> _dirs[] = $prefix;
        if ($this -> _dispatcher) {
            $dirInfo = array('fullname' => $path, 'name' => $leaf, 'relname' => $dir);
            $this -> _dispatcher -> notify(
                new \Apl\Event\Notification(
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
                        $this -> _filterMask & \Apl\FileSystem::TYPE_FILE
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
                    new \Apl\Event\Notification(
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
                new \Apl\Event\Notification($this, self::EVENT_LAST_FILE, $dirInfo)
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
                new \Apl\Event\Notification($this, self::EVENT_LAST_DIR, $dirInfo)
            );
        }
    }

    protected function _ftpClose() {
        if ($this -> _ftpc) {
            ftp_close($this -> _ftpc);
            $this -> _ftpc = false;
        }
    }

    protected function _ftpConnect() {
        $this -> _ftpc = ftp_connect($this -> _host, $this -> _port);
        if ($this -> _ftpc) {
            return ftp_login($this -> _ftpc, $this -> _user, $this -> _password);
        }
        return false;
    }

    protected function _getOptions() {
        if (!isset($this -> _options['host'])) {
            throw \Apl\Exception::factory('application', 'Host not provided');
        }
        $this -> _host = $this -> _options['host'];
        if (isset($this -> _options['port'])) {
            $this -> _port = $this -> _options['port'];
        }
        if (isset($this -> _options['user'])) {
            $this -> _user = $this -> _options['user'];
        }
        if (isset($this -> _options['password'])) {
            $this -> _password = $this -> _options['password'];
        }
    }

}
