<?php
/**
 * File sync server.
 *
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: FileSyncServer.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

//require_once('xmlrpc/XmlRpc.php');

/**
 * AP5L_XmlRpc_FileSyncServer: support for remote file transfer and update
 *
 * This is an XML-RPC server that implements the following:
 *
 * int      getFileDate(session, serverPath)
 *                  Returns the file's modification date (as timestamp)
 * struct   getFileInfo(session, serverPath)
 *                  Return file size, mod date, MD5
 * string   getFileMD5(session, serverPath)
 *                  Returns a file's MD5 (text or binary)
 * string   getOS()
 *                  Get the server's operating system. Returns '*nix' or 'win'
 * int      getServerTime()
 *                  Returns the server's current timestamp
 * int      filePut(session, serverPath, data[, MD5])
 *                  Write data to a file serverPath (if MD5 is same)
 * sessid   sessionCreate()
 *                  Start a new session, return ID
 * int      sessionDestroy($id)
 *                  Destroy a session
 * int      setBasePath(session, basePath)
 *                  Set the base for serverPath in other calls
 */
class AP5L_XmlRpc_FileSyncServer {
    /**
     * Default mode for new directories.
     *
     * @var int
     */
    static $dirMode = 0775;

    /**
     * Default mode for new files.
     *
     * @var int
     */
    static $fileMode = 0664;

    /**
     * Detete the path; if the path is a directory, kill all files and
     * subdirectories.
     *
     * @param string path The path (file/directory) to be deleted
     */
    function _fileKill($path) {
        if (is_dir($path)) {
            // We need to walk the directory, removing all in our path
            if ($dh = @opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file == '.' || $file == '..') continue;
                    $result = AP5L_XmlRpc_FileSyncServer::_fileKill($path . '/' . $file);
                    if (is_array($result)) {
                        return $result;
                    }
                }
                if (! @rmdir($path)) {
                    return array(1, 'Unable to remove ' . $path);
                }
                return 0;
            }
        } else {
            if (! @unlink($path)) {
                return array(1, 'Unable to remove ' . $path);
            }
        }
        return 0;
    }

    /**
     * Load the specified session; if no session is specified, create a new one.
     *
     * @param string id The ID of a pre-existing session.
     */
    function _loadSession($id) {
        ini_set('session.use_cookies', '0');
        if ($id) {
            session_id($id);
            session_start();
            if (! isset($_SESSION['isFileSyncServer'])) {
                return array(-1, 'Server session expired');
            }
        } else {
            session_start();
            $_SESSION['isFileSyncServer'] = true;
        }
        if (! isset($_SESSION['basepath'])) {
            $_SESSION['basepath'] = '';
        }
        return 0;
    }

    /**
     * Create a directory (or path of directories).
     */
    function _mkdir($dir) {
        $root = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'];
        $steps = explode('/', $dir);
        foreach ($steps as $step) {
            if (! @is_dir($root . $step)) {
                if (! @mkdir($root . $step, AP5L_XmlRpc_FileSyncServer::$dirMode)) {
                    return array(1, 'Unable to create ' . $root . $step);
                }
            }
            $root .= $step . '/';
        }
        return 0;
    }

    /**
     * Change a file group
     *
     * @param array args Arguments passed from the client. Args[0] is the
     * session; args[1] is the file path; args[1] is the new group, expressed as
     * a string.
     */
    function fileChgrp($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        if (count($args) < 2) {
            return array(1, '2 arguments required.');
        }
        $fid = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $args[0];
        clearstatcache();
        if (! chgrp($fid, $args[1])) {
            return array(1, 'Call to chgrp(' . $args[1] . ') failed.');
        }
        return array(0, '');
    }

    /**
     * Change a file's access permissions
     *
     * @param array args Arguments passed from the client. Args[0] is the
     * session; args[1] is the file path; args[2] is the file's access
     * permissions, expressed as an integer.
     */
    function fileChmod($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        if (count($args) < 2) {
            return array(1, '2 arguments required.');
        }
        $fid = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $args[0];
        clearstatcache();
        if (! chmod($fid, $args[1])) {
            return array(1, 'Call to chmod(' . $args[1] . ') failed.');
        }
        return array(0, '');
    }

    /**
     * Change a file owner
     *
     * @param array args Arguments passed from the client. Args[0] is the
     * session; args[1] is thefile path; args[2] is the new owner, expressed as
     * a string.
     */
    function fileChown($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        if (count($args) < 2) {
            return array(1, '2 arguments required.');
        }
        $fid = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $args[0];
        clearstatcache();
        if (! chgrp($fid, $args[1])) {
            return array(1, 'Call to chown(' . $args[1] . ') failed.');
        }
        return array(0, '');
    }

    /**
     * Detete the path; if the path is a directory, kill all files and
     * subdirectories.
     *
     * @param array args Arguments passed from the client. Args[0] is the
     * session; args[1] is the file path to delete.
     */
    function fileKill($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        if (count($args) < 1) {
            return array(1, 'No path provided.');
        }
        $fid = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $args[0];
        $len = strlen($fid) - 1;
        if ($fid{$len} == '/') {
            $fid = substr($fid, 0, $len);
        }
        clearstatcache();
        $result = AP5L_XmlRpc_FileSyncServer::_fileKill($fid);
        return $result;
    }

    /**
     * Store a file on the local file system.
     *
     * @param array args Arguments passed from the client. Args[0] is the
     * session; args[1] is thefile path; args[2] is the file's data, encoded in
     * Base64; args[3] is the MD5 hash of the original (binary) data; and the
     * optional args[4] is the file timestamp.
     */
    function filePut($args) {   // file, data, MD5[, time]
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        if (count($args) < 3) {
            return array(1, '3 arguments required.');
        }
        $fid = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $args[0];
        clearstatcache();
        if (($posn = strrpos($fid, '/')) !== false) {
            $inDir = substr($fid, 0, $posn);
            if (! is_dir($inDir)) {
                $trim = strlen($fid) - $posn;
                $result = AP5L_XmlRpc_FileSyncServer::_mkdir(substr($args[0], 0, strlen($args[0]) - $trim));
                if (is_array($result)) return $result;
            }
        }
        $fileData = base64_decode($args[1]);
        $md5 = md5($fileData);
        if ($md5 == $args[2]) {
            $fh = @fopen($fid, 'wb');
            if ($fh) {
                if (fwrite($fh, $fileData) === false) {
                    fclose($fh);
                    return array(4, 'Error writing ' . $fid);
                }
                fclose($fh);
                if (! chmod($fid, AP5L_XmlRpc_FileSyncServer::$fileMode)) {
                    return array(1, 'Unable to set file mode to default.');
                }
                if (isset($args[3])) {
                    if (! touch($fid, $args[3])) {
                        return array(5, 'Unable to set file time to ' . $args[3] . ' for ' . $fid);
                    }
                }
            } else {
                return array(3, 'Unable to open ' . $fid);
            }
        } else {
            return array(2, 'Checksum failure. Data ' . $md5 . ' request ' . $args[2]);
        }
        return array(0, '');
    }

    /**
     * List all the files in a directory, including file size and modification
     * time.
     *
     * @param array args Arguments passed from the client. Args[0] should be the
     * directory path.
     */
    function getDirList($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $args[0];
        if ($path{strlen($path) - 1} != '/') {
            $path .= '/';
        }
        clearstatcache();
        if ($dh = @opendir($path)) {
            $list = array();
            while (($file = readdir($dh)) !== false) {
                $fid = $path . $file;
                $list[] = array($file, filetype($fid), @filemtime($fid), @filesize($fid));
            }
            return array(0, $list);
        }
        return array(1, 'Unable to open ' . $path);
    }

    /**
     * Get the modification date of a file.
     *
     * @param array args Arguments passed from the client. Args[0] should be the
     * file path.
     */
    function getFileDate($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $args[0];
        clearstatcache();
        $mtime = @filemtime($path);
        if ($mtime === false) {
            $mtime = -1;
        }
        return array(0, $mtime);
    }

    /**
     * Get the modification date of a list of files.
     *
     * @param array args Arguments passed from the client. Args[0] should be an
     * array of file paths.
     */
    function getFileDateBatch($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        clearstatcache();
        $mtimes = array();
        foreach ($args[0] as $fid) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $fid;
            $mtime = @filemtime($path);
            if ($mtime === false) {
                $mtime = -1;
            }
            $mtimes[$fid] = $mtime;
        }
        return array(0, $mtimes);
    }

    /**
     * Get the MD5 hash of a file.
     *
     * @param array args Arguments passed from the client. Args[0] should be the
     * file path.
     */
    function getFileMD5($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        $path = $args[0];
        $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['basepath'] . $path;
        if (($md5 = md5_file($path)) === false) {
            return '';
        }
        return array(0, $md5);
    }

    /**
     * Get the native operating system type.
     */
    function getOS() {
        foreach ($_ENV as $var => $val) {
            if (strtolower($var) == 'path') {
                $path = $_ENV[$var];
                break;
            }
        }
        $os = (strchr($path, '\\') === false) ? '*nix' : 'win';
        $_SESSION['localos'] = $os;
        return array(0, $os);
    }
    /**
     * Get the server's current time stamp.
     */
    function getServerTime() {
        return array(0, time());
    }


    /**
     * Start a file synch session.
     */
    function sessionCreate($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession('');
        if (is_array($result)) return $result;
        return array(0, session_id());
    }

    /**
     * Close a file synch session.
     */
    function sessionDestroy($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        $_SESSION = array();
        return (session_destroy() === false) ? array(1, 'Session destroy failed.') : array(0, '');
    }

    /**
     * Set a base path that other file operations are relative to.
     */
    function setBasePath($args) {
        $result = AP5L_XmlRpc_FileSyncServer::_loadSession(array_shift($args));
        if (is_array($result)) return $result;
        if (count($args) < 1) {
            return array(1, 'Argument required');
        }
        $path = $args[0];
        if ($path{strlen($path) - 1} != '/') {
            $path .= '/';
        }
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $path;
        if (! @is_dir($fullPath)) {
            return array(2, 'Server base path does not exist: ' . $fullPath);
        }
        $_SESSION['basepath'] = $path;
        return array(0, '');
    }

}

$methodList = explode(' ', 'getDirList getFileDate getFileDateBatch getFileMD5 getOS getServerTime'
    . ' fileChgrp fileChmod fileChown fileKill filePut sessionCreate sessionDestroy'
    . ' setBasePath');
$callBacks = array();

foreach ($methodList as $method) {
    $callBacks[$method] = 'AP5L_XmlRpc_FileSyncServer::' . $method;
}

$server = new AP5L_XmlRpc_Server($callBacks, false, false);
$server -> convertSingleArgument = false;
$server -> serve();


?>