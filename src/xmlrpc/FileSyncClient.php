<?php
/**
 * XML-RPC File synchronization client.
 *
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: FileSyncClient.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

//require_once('PEAR.php');
//require_once('xmlrpc/XmlRpc.php');

/**
 * File synchronization client.
 */
class AP5L_XmlRpc_FileSyncClient {
    var $_client;
    var $_basePath = '';
    var $_dryRun;                       // Flag, when set, no actual transfer occurs.
    var $_hostOS;
    var $_hostPath = '';
    var $_hostSession;
    var $_hostTimeDelta;
    var $_localOS;
    var $_messages = array();

    /**
     * Records the start of a command for benchmark/timing purposes.
     *
     * @var float
     */
    protected $_startTime;

    var $_textFiles;
    var $_transport = 'xml';
    var $_transportID = 0;
    var $_url;
    var $_xmlTimeout = 60;

    /**
     * Controls the recording of timing information
     */
    var $showTiming = false;

    function __construct() {
        $path = ':';
        foreach ($_ENV as $var => $val) {
            if (strtolower($var) == 'path') {
                $path = $_ENV[$var];
                break;
            }
        }
        $this -> _localOS = (strchr($path, '\\') === false) ? '*nix' : 'win';
        $this -> _textFiles = explode(
            ';',
            '*.*htm*;*.bas;*.c;*.cfg;*.cgi;*.cpp;*.css;*.h;.htaccess;*.inc;'
            . '*.ini;*.js;*.pas;*.php*;*.pl;*.sh;*.tex;*.txt;*.xml'
        );
        $this -> _transport = array('scheme' => 'xml');
    }

    function _clientInit() {
        $this -> _client = false;
        $this -> _hostOS = '';
        $this -> _hostPath = '';
        $this -> _hostSession = '';
        $this -> _hostTimeDelta = 0;
    }

    /**
     * Format a PEAR error from a XML RPC library error
     **/
    function _clientError() {
        return new PEAR_Error('XML-RPC call failure', $this -> _client -> getErrorCode(), null,
            null, $this -> _client -> getErrorMessage() . chr(10)
            . 'Message Contents:' . chr(10) . $this -> _client -> getMessage());
    }

    function _commandStart() {
        $this -> _startTime = microtime(true);
        if ($this -> showTiming) {
            $this -> _messages[] = 'Operation start at ' . $this -> _startTime;
        }
    }

    function _commandStop() {
        if ($this -> showTiming) {
            $this -> _messages[] = 'Operation took '
                . (microtime(true) - $this -> _startTime)
                . ' seconds.';
        }
    }

    function _debugInfo() {
        $info = 'Remote Connection: ' . $this -> _url . '<br/>';
        $info .= 'Client: ' . ($this -> _client ? 'defined' : 'none') . ' ';
        $info .= 'SessionID=' . $this -> _hostSession . '<br/>';
        $info .= 'OS: local=' . $this -> _localOS . ' host=' . $this -> _hostOS . '<br/>';
        $info .= 'basePath: ' . $this -> _basePath . '<br/>';
        $info .= 'hostPath: ' . $this -> _hostPath . '<br/>';
        $info .= 'HostTimeDelta: ' . $this -> _hostTimeDelta . '<br/>';
        return $info;
    }

    /**
     * Walk the directory tree, accumulating a list of files
     */
    function _dirWalk(&$fileList, $dir) {
        if (strlen($dir) && ($dir[strlen($dir) - 1] != '/')) {
            $dir .= '/';
        }
        $path = $this -> _basePath . $dir;
        if (! @is_dir($path)) {
            return new PEAR_Error('Update error. ' . $path . ' is not a directory.', 1);
        }
        if(! ($dh = @opendir($path))) {
            return new PEAR_Error('Update error. Unable to open ' . $path, 2);
        }
        //
        // Walk through the directory collecting files and subdirectories
        // Then we sort them just to make the output look nicer
        //
        $list = array();
        $subList = array();
        while (($file = readdir($dh)) !== false) {
            $fid = $path . $file;
            switch (filetype($fid)) {
                case 'dir': {
                    if ($file != '.' && $file != '..') {
                        $subList[] = $file;
                    }
                } break;

                case 'file': {
                    $list[] = $file;
                } break;
            }
        }
        sort($list);
        foreach ($list as $file) {
            $fileList[] = $dir . $file;
        }
        sort($subList);
        foreach ($subList as $subDir) {
            $result = $this -> _dirWalk($fileList, $dir . $subDir);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
    }

    function _filePut($fid, $mode) {
        $mode = strtolower($mode);
        $path = str_replace('\\', '/', $fid);
        if ($mode == 'a') {
            $mode = 'b';
            if (($posn = strrpos($path, '/')) === false) {
                $fileMatch = $path;
            } else {
                $fileMatch = substr($path, $posn + 1);
            }
            $bits = ''; // warning killer
            foreach ($this -> _textFiles as $textMatch) {
                $pattern = '/' . str_replace(array('.', '*', '?'), array('\\.', '.*', '.'), $textMatch) . '/i';
                $isText = preg_match($pattern, $fileMatch, $bits);
                if ($isText && $fileMatch == $bits[0]) {
                    $mode = 't';
                    break;
                }
            }
        }
        $path = $this -> _basePath . $path;
        switch ($this -> _transport['scheme']) {
            case 'ftp':
            case 'ftps': {
                $rpath = $this -> _transport['path'] . $this -> _hostPath;
                if ($rpath[strlen($rpath) - 1] != '/') {
                    $rpath .= '/';
                }
                $fid = $rpath . $fid;
                //
                // FTP requires that we do all of the directory work ourselves
                //
                // Get the target directories, with no filename
                //
                $targetDirs = explode('/', $fid);
                $targetFile = array_pop($targetDirs);
                //
                // Get the FTP server's current directory
                //
                $ftpCwd = ftp_pwd($this -> _transportID);
                $targetCwd = explode('/', $ftpCwd);
                //
                // Change directories, creating if required
                //
                $ftpCwd = '/';
                for ($indx = 1; $indx < count($targetDirs); ++$indx) {
                    if ($indx < count($targetCwd)) {
                        //
                        // As long as directories match, just accumulate the target cwd
                        //
                        if ($targetDirs[$indx] == $targetCwd[$indx]) {
                            $ftpCwd .= $targetCwd[$indx] . '/';
                        } else {
                            $result = ftp_chdir($this -> _transportID, $ftpCwd);
                            if (! $result) {
                                return new PEAR_Error('Failed on chdir to ' . $ftpCwd);
                            }
                            $targetCwd = array();
                        }
                    }
                    if ($indx >= count($targetCwd)) {
                        $ftpCwd .= $targetDirs[$indx] . '/';
                        $result = @ftp_chdir($this -> _transportID, $ftpCwd);
                        if (! $result) {
                            $result = @ftp_mkdir($this -> _transportID, $targetDirs[$indx]);
                            if (! $result) {
                                return new PEAR_Error('Failed on mkdir at ' . $ftpCwd);
                            }
                            $result = @ftp_chdir($this -> _transportID, $ftpCwd);
                            if (! $result) {
                                return new PEAR_Error('Failed on chdir to new ' . $ftpCwd);
                            }
                        }
                    }
                }
                $msg = 'Copied ' . $path . ' to ' . $fid . ' using mode ' . $mode;
                if ($this -> _dryRun) {
                    $msg = '[simulated] ' . $msg;
                } else {
                    $result = ftp_put($this -> _transportID, $targetFile, $path,
                        ($mode == 'b') ? FTP_BINARY : FTP_TEXT);
                    if (! $result) {
                        return new PEAR_Error('Failed attempting to send ' . $path . ' to ' . $fid);
                    }
                }
                $this -> _messages[] = $msg;
            } break;

            case 'xml': {
                $fs = $this -> _readFile($path, $mode);
                if (PEAR::isError($fs)) {
                    return $fs;
                }
                $hash = md5($fs);
                $fs = base64_encode($fs);
                $msg = 'Copied ' . $fid . ' to ' . $this -> _hostPath . ' using mode ' . $mode;
                if ($this -> _dryRun) {
                    $msg = '[simulated] ' . $msg;
                } else {
                    $hostResult = $this -> _rpc('filePut', $fid, $fs, $hash, filemtime($path));
                    if (! $hostResult) {
                        //
                        // Perhaps this is an older server?
                        //
                        $hostResult = $this -> _rpc('putFile', $fid, $fs, $hash, filemtime($path));
                        if (! $hostResult) {
                            return $this -> _clientError();
                        }
                    }
                    $hostResult = $this -> _client -> getResponse();
                    switch ($hostResult[0]) {
                        case 0:
                            break;

                        case 5: {
                            // TODO: Make warning/error in this case configurable
                            $this -> _messages[] = $msg;
                            $msg = 'Failed to set file time on ' . $fid;
                        }
                        break;

                        default: {
                            return new PEAR_Error($hostResult[1], $hostResult[0]);
                        }
                    }
                }
                $this -> _messages[] = $msg;
            } break;
        }
        return true;
    }

    function _readFile($path, $mode) {
        if (($this -> _localOS != $this -> _hostOS) && $mode == 't') {
            if ($this -> _localOS = 'win') {
                $trimSize = 2;
                $eol = chr(10);
                $detectEol = chr(10);
            } else {
                $trimSize = 1;
                $eol = chr(13) . chr(10);
                $detectEol = chr(10);
            }
            //
            // Read strings, convert CR/LF to LF
            //
            $fs = '';
            $fh = @fopen($path, 'r');
            if (!$fh) {
                return new PEAR_Error('Open failure on ' . $path, 1);
            }
            while (! feof($fh)) {
                $buf = fgets($fh);
                $blen = strlen($buf);
                if ($buf[$blen - 1] == $detectEol) {
                    // Handle the case where we have a Win text file
                    // only terminated by LF...
                    if ($trimSize == 2 && $blen > 1 && $buf[$blen - 2] == chr(13)) {
                        $buf = substr($buf, 0, strlen($buf) - $trimSize) . $eol;
                    }
                }
                $fs .= $buf;
            }
            fclose($fh);
        } else {
            if (($fs = @file_get_contents($path)) === false) {
                return new PEAR_Error('Read failure on ' . $path, 2);
            }
        }
        return $fs;
    }

    function _rpc(/* command, arg1, arg2... see inline */) {
        $args = func_get_args();
        $command = array_shift($args);
        array_unshift($args, $command, $this -> _hostSession);
        $hostResult = $this -> _client -> query($args);
        return $hostResult;
    }

    function _transportClose() {
        if ($this -> _transportID) {
            ftp_close($this -> _transportID);
            $this -> _transportID = 0;
        }
    }

    function _transportOpen() {
        switch ($this -> _transport['scheme']) {
            case 'ftp': {
                $this -> _transportID = ftp_connect($this ->_transport['host'], $this ->_transport['port']);
            } break;

            case 'ftps': {
                $this -> _transportID = ftp_ssl_connect($this ->_transport['host'], $this ->_transport['port']);
            } break;

            case 'xml': {
                return;
            }
        }
        if (! $this -> _transportID) {
            return new PEAR_Error('FTP: Unable to connect to ' . $this ->_transport['host']
                . ':' . $this ->_transport['port']);
        }
        $result = @ftp_login($this -> _transportID, $this ->_transport['user'], $this ->_transport['pass']);
        if (! $result) {
            $this -> _transportClose();
            return new PEAR_Error('FTP: login not accepted for ' . $this ->_transport['user']);
        }
    }

    function _update($dir) {
        if (strlen($dir) && ($dir[strlen($dir) - 1] != '/')) {
            $dir .= '/';
        }
        $path = $this -> _basePath . $dir;
        if (! @is_dir($path)) {
            return new PEAR_Error('Update error. ' . $path . ' is not a directory.', 1);
        }
        if(! ($dh = @opendir($path))) {
            return new PEAR_Error('Update error. Unable to open ' . $path, 2);
        }
        //
        // Walk through the directory collecting files and subdirectories
        // Then we sort them just to make the output look nicer
        //
        $list = array();
        $subList = array();
        while (($file = readdir($dh)) !== false) {
            $fid = $path . $file;
            switch (filetype($fid)) {
                case 'dir': {
                    if ($file != '.' && $file != '..') {
                        $subList[] = $file;
                    }
                } break;

                case 'file': {
                    $list[] = $file;
                } break;
            }
        }
        sort($list);
        foreach ($list as $file) {
            $rtime = $this -> getFileDate($dir . $file);
            if (PEAR::isError($rtime)) {
                return $rtime;
            }
            $fid = $path . $file;
            if (filemtime($fid) > $rtime) {
                $result = $this -> _filePut($dir . $file, 'a');
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }
        sort($subList);
        foreach ($subList as $subDir) {
            $result = $this -> _update($dir . $subDir);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
    }

    function fileKill($fid) {
        $hostResult = $this -> _rpc('fileKill', $fid);
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        return $hostResult[1];
    }

    function filePut($fid, $mode = 'a') {
        $this -> _messages = array();
        return $this -> _filePut($fid, $mode);
    }

    function addTextMatch($newMatch) {
        foreach ($this -> _textFiles as $textMatch) {
            if ($newMatch == $textMatch) {
                return true;
            }
        }
        $this -> _textFiles[] = $newMatch;
        return true;
    }

    function deleteTextMatch($newMatch) {
        foreach ($this -> _textFiles as $key => $textMatch) {
            if ($newMatch == $textMatch) {
                unset($this -> _textFiles[$key]);
                return true;
            }
        }
        return false;
    }

    function clearMessages() {
        $this -> _messages = array();
    }

    function getDirList($path) {
        $hostResult = $this -> _rpc('getDirList', $path);
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        return $hostResult[1];
    }

    function getFileDate($fid) {
        $hostResult = $this -> _rpc('getFileDate', $fid);
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        return $hostResult[1];
    }

    function getFileDateBatch($fileList) {
        $hostResult = $this -> _rpc('getFileDateBatch', $fileList);
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        return $hostResult[1];
    }

    function getMessages() {
        return $this -> _messages;
    }

    function getServerTime() {
        $hostResult = $this -> _rpc('getServerTime');
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        $this -> _hostTimeDelta = time() - $hostResult[1];
        return $hostResult[1];
    }

    function sessionCreate() {
        if ($this -> _hostSession) {
            $this -> sessionDestroy();
        }
        $this -> _clientInit();
        $this -> _client = new AP5L_XmlRpc_Client($this -> _url);
        $this -> _client -> timeout = $this -> _xmlTimeout;
        $hostResult = $this -> _rpc('sessionCreate');
        if (! $hostResult) {
            $err = $this -> _clientError();
            $this -> _clientInit();
            return $err;
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            $this -> _clientInit();
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        $this -> _hostSession = $hostResult[1];
        $hostResult = $this -> _rpc('getOS');
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        $this -> _hostOS = $hostResult[1];
        return true;
    }

    function sessionDestroy() {
        if (! $this -> _client) {
            return new PEAR_Error('sessionDestroy failed: no session exists', 1);
        }
        $hostResult = $this -> _rpc('sessionDestroy');
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        $this -> _clientInit();
        return true;
    }

    function setBasePath($path) {
        $path = str_replace('\\', '/', $path);
        if ($path[strlen($path) - 1] != '/') {
            $path .= '/';
        }
        if (! @is_dir($path)) {
            return new PEAR_Error('Base path is not a directory: ' . $path, 1);
        }
        $this -> _basePath = $path;
        return true;
    }

    function setDryRun($dry) {
        $this -> _dryRun = $dry;
    }

    function setRemotePath($path) {
        $hostResult = $this -> _rpc('setBasePath', $path);
        if (! $hostResult) {
            return $this -> _clientError();
        }
        $hostResult = $this -> _client -> getResponse();
        if ($hostResult[0]) {
            return new PEAR_Error($hostResult[1], $hostResult[0]);
        }
        $this -> _hostPath = $path;
        return true;
    }

    function setServer($url) {
        $this -> _url = $url;
    }

    function setTextMatches($matchList) {
        $this -> _textFiles = explode(';', $matchList);
    }

    function setTransport($url='') {
        if ($url) {
            $this -> _transport = parse_url($url);
            foreach ($this -> _transport as $key => $value) {
                $this -> _transport[$key] = urldecode($value);
            }
            if (! isset($this -> _transport['path'])) {
                $this -> _transport['path'] = '/';
            } else if (strlen($this -> _transport['path'])) {
                if ($this -> _transport['path'][strlen($this -> _transport['path'])- 1] != '/') {
                    $this -> _transport['path'] .= '/';
                }
            } else {
                $this -> _transport['path'] = '/';
            }
            if (! isset($this -> _transport['port'])) {
                $this -> _transport['port'] = 21;
            }
            if ($this -> _transport['path'][0] != '/') {
                $this -> _transport['path'] = '/' . $this -> _transport['path'];
            }
        } else {
            $this -> _transport = array('scheme' => 'xml');
        }
    }

    function update($localPath, $remotePath, $serverUrl = '', $serverUser = '', $serverPass = '') {
        $this -> _messages = array();
        $this -> _commandStart();
        try {
            //
            // connect, check each file in path, upload when required
            //
            if ($serverUrl != '') {
                $this -> setServer($serverUrl, $serverUser, $serverPass);
                if ($this -> _hostSession) {
                    $result = $this -> sessionDestroy();
                    if (PEAR::isError($result)) {
                        throw new Exception($result -> toString(), $result -> getCode());
                    }
                }
            }
            if (! $this -> _hostSession) {
                if (PEAR::isError($result = $this -> sessionCreate())) {
                    throw new Exception($result -> toString(), $result -> getCode());
                }
                $newSession = true;
            } else {
                $newSession = false;
            }
            $this -> setBasePath($localPath);
            $result = $this -> setRemotePath($remotePath);
            if (PEAR::isError($result)) {
                throw new Exception($result -> toString(), $result -> getCode());
            }
            $result = $this -> _update('');
            if (PEAR::isError($result)) {
                throw new Exception($result -> toString(), $result -> getCode());
            }
            if ($newSession) {
                $result = $this -> sessionDestroy();
                if (PEAR::isError($result)) {
                    throw new Exception($result -> toString(), $result -> getCode());
                }
            }
        } catch (Exception $anything) {
            $this -> _commandStop();
            throw $anything;
        }
        $this -> _commandStop();
        return true;
    }

    function updateBatch($localPath, $remotePath, $serverUrl = '', $serverUser = '', $serverPass = '') {
        $this -> _messages = array();
        $this -> _commandStart();
        try {
            //
            // connect, prepare file list, get dates, upload when required
            //
            if ($serverUrl != '') {
                $this -> setServer($serverUrl, $serverUser, $serverPass);
                if ($this -> _hostSession) {
                    $result = $this -> sessionDestroy();
                    if (PEAR::isError($result)) {
                        throw new Exception($result -> toString(), $result -> getCode());
                    }
                }
            }
            if (! $this -> _hostSession) {
                if (PEAR::isError($result = $this -> sessionCreate())) {
                    throw new Exception($result -> toString(), $result -> getCode());
                }
                $newSession = true;
            } else {
                $newSession = false;
            }
            $result = $this -> _transportOpen();
            if (PEAR::isError($result)) {
                throw new Exception($result -> toString(), $result -> getCode());
            }
            $this -> setBasePath($localPath);
            $result = $this -> setRemotePath($remotePath);
            if (PEAR::isError($result)) {
                throw new Exception($result -> toString(), $result -> getCode());
            }
            $allFiles = array();
            $result = $this -> _dirWalk($allFiles, '');
            if (PEAR::isError($result)) {
                throw new Exception($result -> toString(), $result -> getCode());
            }
            $rtimes = $this -> getFileDateBatch($allFiles);
            if (PEAR::isError($rtimes)) {
                return $rtimes;
            }
            foreach ($rtimes as $fid => $rtime) {
                $path = $this -> _basePath . $fid;
                //echo 'Checking ' . $path . ' = ' . filemtime($path) . ' r=' . $rtime . chr(10);
                if (filemtime($path) <= $rtime) {
                    unset($rtimes[$fid]);
                }
            }
            $this -> _messages[] = 'Transferring ' . count($rtimes) . ' of ' . count($allFiles) . ' files.';
            foreach ($rtimes as $fid => $rtime) {
                $path = $this -> _basePath . $fid;
                //echo 'Checking ' . $path . ' = ' . filemtime($path) . ' r=' . $rtime . chr(10);
                $result = $this -> _filePut($fid, 'a');
                if (PEAR::isError($result)) {
                    throw new Exception($result -> toString(), $result -> getCode());
                }
            }
            $result = $this -> _transportClose();
            if (PEAR::isError($result)) {
                throw new Exception($result -> toString(), $result -> getCode());
            }
            if ($newSession) {
                $result = $this -> sessionDestroy();
                if (PEAR::isError($result)) {
                    throw new Exception($result -> toString(), $result -> getCode());
                }
            }
        } catch (Exception $anything) {
            $this -> _commandStop();
            throw $anything;
        }
        $this -> _commandStop();
        return true;
    }

}

?>