<?php
/**
 * Very simple HTTP client.
 *
 * @package AP5L
 * @subpackage Http
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Client.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */


/**
 * A simple HTTP client
 */
class AP5L_Http_Client {
    const BASE_VERSION = '0.1';

    /**
     * Application-supplied request body
     *
     * @var string
     */
    protected $_body;

    /**
     * Connection (handle, object, etc)
     *
     * @var mixed
     */
    protected $_connection;

    static protected $_contentTypes = array(
        'POST' => 'application/x-www-form-urlencoded',
        'PROPFIND' => 'text/xml; charset="utf-8"',
    );

    /**
     * Debugging level.
     *
     * @var integer
     */
    protected $_debug = 0;

    /**
     * Socket error message.
     *
     * @var string
     */
    protected $_errMsg;

    /**
     * Socket error code.
     *
     * @var int
     */
    protected $_errNum;

    /**
     * Array of entries to be added to the header.
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * Name of the host for this request.
     *
     * @var string
     */
    protected $_host;

    /**
     * HTTP version to use when sending request
     *
     * @var string
     */
    protected $_httpVersion = 'HTTP/1.1';

    /**
     * The request method, currently either GET or POST.
     *
     * @var string
     */
    protected $_method = 'GET';

    /**
     * Delimiter for request paramaters.
     *
     * @var string
     */
    protected $_paramDelim = '&';

    /**
     * Parameters for the request.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Password for authentication.
     *
     * @var string
     */
    protected $_password;

    /**
     * Path to the resource on the host server.
     *
     * @var string
     */
    protected $_path = '/';

    /**
     * Port to connect to on server.
     *
     * @var int
     */
    protected $_port = 80;

    /**
     * The last message sent
     *
     * @var Message
     */
    protected $_request = '';

    /**
     * Response object.
     *
     * @var Messgae
     */
    protected $_response;

    /**
     * The amount of time to wait for a response
     *
     * @var int
     */
    protected $_timeout = 30;

    /**
     * String to send as the user agent
     *
     * @var string
     */
    protected $_userAgent;

    /**
     * User namefor authentication.
     *
     * @var string
     */
    protected $_userName;

    static $baseUserAgent = 'Abivia HTTP PHP Library';

    var $dumpCallback;                  // Callback for diagnostic/trace output

    /**
     * HTTP client constructor.
     *
     * @param array Options. Optional (go figure). {@see setOptions()}
     */
    function __construct($options = array()) {
        $this -> userAgent = self::$baseUserAgent . $this -> getVersion();
        $this -> setOptions($options);
    }

    /**
     * Close the communications link.
     */
    protected function _close() {
        fclose($this -> _connection);
    }

    /**
     * Create the body of a http request.
     */
    protected function _createRequest() {
        /*
         * Format the path/body depending on the request
         */
        $request = new AP5L_Http_Message();
        if ($this -> _method == 'GET' || $this -> _method == 'POST') {
            $plist = '';
            $delim = '';
            foreach ($this -> _params as $param => $val) {
                $plist .= $delim . urlencode($param) . '=' . urlencode($val);
                $delim = $this -> _paramDelim;
            }
        }
        $path = $this -> _path;
        switch ($this -> _method) {
            case 'HEAD':
            case 'GET': {
                $path .= ($plist !== '') ? '?' . $plist : '';
            }
            break;

            case 'POST': {
                $request -> body = $plist;
            }
            break;

            case 'PROPFIND': {
                // The calling application should have provided a body.
                $request -> body = $this -> _body;
            }
            break;

            default: {
                throw new AP5L_Http_Exception('Unknown method: ' . $this -> _method);
            }
        }
        /*
         * Now assemble the message
         */
        $r = chr(13) . chr(10);
        $request -> start  = $this -> _method . ' ' . $path
            . ' ' . $this -> _httpVersion;
        $request -> headers['Host'] = $this -> _host;
        if ($this -> _userName || $this -> _password) {
            $request -> headers['Authorization'] = 'Basic '
                . base64_encode($this -> _userName . ':' . $this -> _password);
        }
        $request -> headers['User-Agent'] = $this -> _userAgent;
        /*
         * Add application headers
         */
        foreach ($this -> _headers as $key => $val) {
            $request -> headers[$key] = $val;
        }
        /*
         * If this is a post, add content
         */
        if (isset(self::$_contentTypes[$this -> _method])) {
            $request -> headers['Content-Type'] = self::$_contentTypes[$this -> _method];
            $request -> headers['Content-Length'] = strlen($request -> body);
        }
        $this -> _request = $request;
    }

    /**
     * Gateway for diagnostic output.
     */
    function _dump($str, $type = 'text/plain') {
        if ($this -> dumpCallback) {
            call_user_func($this -> dumpCallback, $str, $type);
        } else {
            echo '<pre>' . htmlentities($str) . '</pre>';
        }
    }

    /**
     * Compute the elapsed time between two microtime values.
     *
     * @param string Start time.
     * @param string Optional. Stop time. If omitted, the current time is used.
     */
    function _elapsedTime($start, $finish = '') {
        $start = explode(' ', $start);
        $finish = explode(' ', $finish ? $finish : microtime());
        $elapsed = ($finish[0] - $start[0]) + ($finish[1] - $start[1]);
        return $elapsed;
    }

    /**
     * Open a socket.
     *
     * The primary purpose of this method is to facilitate testing. Updates the
     * _errMsg and _errNum properties.
     *
     * @param string Address of the system to connect to.
     * @param int The port to use.
     * @param float The maximum number of seconds to wait for a connection.
     */
    protected function _fsockopen($target, $port, $timeout = null) {
        $this -> _connection = @fsockopen(
            $target, $port,
            $this -> _errNum, $this -> _errMsg,
            $timeout
        );
    }

    /**
     * Pretend to be a well-known browser
     *
     * @param string Browser specification. String containing the following
     * elements: Browser, version, platform.
     * @return boolean true if matching browser found.
     */
    protected function _masquerade($setting) {
        $details = explode(' ', $setting);
        $clean = array();
        foreach ($details as $component) {
            if ($component !== '') {
                $clean[] = strtolower($component);
            }
        }
        if (! isset($clean[0])) {
            return false;
        }
        switch ($clean[0]) {
            case 'ff':
            case 'ff3': {
                /*
                 * Fairly basic FF 3 on WinXP
                 */
                $this -> _userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US;'
                    .' rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3';
                $this -> _headers['Accept'] = 'text/html,application/xhtml+xml,'
                    . 'application/xml;q=0.9,*/*;q=0.8';
                $this -> _headers['Accept-Language'] = 'en-us;q=0.7,en;q=0.3';
                $this -> _headers['Accept-Encoding'] = 'gzip,deflate';
                $this -> _headers['Accept-Charset'] = 'ISO-8859-1,utf-8;q=0.7,*;q=0.7';
                $this -> _headers['Keep-Alive'] = '300';
                $this -> _headers['Connection'] = 'keep-alive';
                return true;
            }
            break;

            case 'ff2': {
                /*
                 * We're a generic FF 2.0.0.9 on WinXP (we didn't update,
                 * shame!)
                 */
                $this -> _userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US;'
                    .' rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.9';
                $this -> _headers['Accept'] = 'text/xml,application/xml,'
                    .'application/xhtml+xml,text/html;q=0.9,text/plain'
                    .';q=0.8,image/png,*/*;q=0.5';
                $this -> _headers['Accept-Language'] = 'en-us;q=0.7,en;q=0.3';
                $this -> _headers['Accept-Encoding'] = 'gzip,deflate';
                $this -> _headers['Accept-Charset'] = 'ISO-8859-1,utf-8;q=0.7,*;q=0.7';
                $this -> _headers['Keep-Alive'] = '300';
                $this -> _headers['Connection'] = 'keep-alive';
                return true;
            }
            break;
        }
        return false;
    }

    /**
     * Open a connection
     */
    protected function _open() {
        $this -> _fsockopen(
            $this -> _host, $this -> _port, $this -> _timeout
        );
        if (! $this -> _connection) {
            throw new AP5L_Http_Exception(
                'Transport error '. $this -> _errNum . ': ' . $this -> _errMsg . chr(10)
                . 'Could not open socket on '
                . $this -> _host . ':' . $this -> _port
                . ' (T/O= ' . $this -> _timeout . ')');
        }
        $this -> _stream_set_timeout($this -> _connection, $this -> _timeout);
    }

    /**
     * Set timeout on the host connection.
     *
     * This is a
     */
    protected function _stream_set_timeout($connection, $timeout) {
        stream_set_timeout($connection, $timeout);
    }

    /**
     * Read the response
     */
    protected function _read() {
        $this -> _response = new AP5L_Http_Message();
        $firstLine = true;
        $expected = 2147483647;
        $chunkMode = false;
        $trailer = '';
        $key = '';
        /*
         * Start by reading the header block
         */
        $inHeaders = true;
        while (! feof($this -> _connection) && $inHeaders) {
            $line = fgets($this -> _connection, 4096);
            $line = preg_replace('/[\n\r]+$/', '', $line);
            if ($firstLine) {
                $this -> _response -> start = $line;
                $firstLine = false;
            } elseif ($line == '') {
                $inHeaders = false;
            } elseif (($line[0] == ' ' || $line[0] == chr(9)) && $key) {
                $this -> _response -> headers[$key] = ' ' . trim($line);
            } else {
                $posn = strpos($line, ':');
                /*
                 * RFC2616 section 4.2: field names are case-insensitive.
                 */
                $key = strtolower(substr($line, 0, $posn));
                $val = trim(substr($line, $posn + 1));
                $this -> _response -> headers[$key] = $val;
            }
        }
        /*
         * Extract relevant header information
         */
        foreach ($this -> _response -> headers as $key => $val) {
            switch ($key) {
                case 'content-length': {
                    $expected = (int) $val;
                }
                break;

                case 'transfer-encoding': {
                    $chunkMode = $val == 'chunked';
                }
                break;

                case 'trailer': {
                    $trailer = $val;
                }
                break;
            }
        }
        /*
         * Read the rest of the response.
         */
        if ($chunkMode) {
            while (! feof($this -> _connection)) {
                $line = fgets($this -> _connection, 4096);
                $line = preg_replace('/[\n\r]+$/', '', $line);
                $expected = hexdec($line);
                if (! $expected) {
                    break;
                }
                $this -> _readChunk($this -> _response -> body, $expected);
                if (! feof($this -> _connection)) {
                    fread($this -> _connection, 2);
                }
            }
            if ($trailer) {
                $line = fgets($this -> _connection, 4096);
                $line = preg_replace('/[\n\r]+$/', '', $line);
                $posn = strpos($line, ':');
                $key = substr($line, 0, $posn);
                $val = trim(substr($line, $posn + 1));
                $this -> _response -> headers[$key] = $val;
            }
        } else {
            $this -> _readChunk($this -> _response -> body, $expected);
        }
    }

    /**
     * Read a "chunk" of data from the server.
     *
     * @var string Buffer for accumuating data.
     * @var int Number of bytes expected.
     */
    function _readChunk(&$chunk, $size) {
        $received = 0;
        while (! feof($this -> _connection) && ($received < $size)) {
            $buf = fread($this -> _connection, $size - $received);
            $chunk .= $buf;
            $received += strlen($buf);
        }
    }

    /**
     * Write the request
     */
    protected function _write() {
        fwrite($this -> _connection, $this -> _request -> start . chr(13) . chr(10));
        foreach ($this -> _request -> headers as $key => $val) {
            fwrite($this -> _connection, $key . ': ' . $val . chr(13) . chr(10));
        }
        fwrite($this -> _connection, chr(13) . chr(10). $this -> _request -> body);
    }

    /**
     * Add one or more parameters to the request.
     *
     * The passed parameters are merged with any existing parameters. If the
     * request uses the GET method, these will be added to the URI; if the
     * method is POST, they will be added to the message body.
     *
     * @param array Associative array of key, value pairs.
     */
    function addParameters($params) {
        foreach ($params as $param => $val) {
            $this -> _params[$param] = $val;
        }
    }

    /**
     * Create a HTTP request without sending it.
     *
     * @param array Options (optional). {@see setOptions()}
     * @return AP5L_Http_Request A HTTP Request message.
     */
    function createRequest($options = array()) {
        $this -> error = false;
        $this -> setOptions($options);
        $this -> _createRequest();
        return $this -> _request;
    }

    /**
     * Send a query to a HTTP server and get a response.
     *
     * @param array Options (optional). {@see setOptions()}
     */
    function execute($options = array()) {
        $this -> error = false;
        $this -> setOptions($options);
        $this -> _createRequest();
        /*
         * Send the request
         */
        if ($this -> _debug) {
            $start = microtime();
            $this -> _dump($this -> _request -> start);
            $this -> _dump(print_r($this -> _request -> headers, true));
            $this -> _dump($this -> _request -> body);
            $this -> _dump('Timeout: ' . $this -> _timeout . 's @ ' . $start . chr(10) . chr(10));
        }
        $this -> _open();
        try {
            $this -> _write();
            if ($this -> _debug) {
                $this -> _dump('Request t=' . $this -> _elapsedTime($start));
            }
            $this -> _read();
        } catch (Exception $e) {
            $this -> _close();
            throw $e;
        }
        $heads = &$this -> _response -> headers;
        if (isset($heads['Content-Encoding'])) {
            switch($heads['Content-Encoding']) {
                case 'deflate': {
                    $this -> _response -> body = gzinflate($this -> _response -> body);
                }
                break;

                case 'gzip': {
                    $body = $this -> _response -> body;
                    if ($body[0] == chr(31) && $body[1] == chr(139)) {
                        // This is simply a matter of stripping a header
                        $this -> _response -> body = gzinflate(substr($body, 10));
                    }
                }
                break;
            }
        }
        if ($this -> _debug) {
            $this -> _dump('Response t=' . $this -> _elapsedTime($start));
            $this -> _dump($this -> _response -> start);
            $this -> _dump(print_r($this -> _response -> headers, true));
            $this -> _dump($this -> _response -> body);
        }
    }

    /**
     * Return the body of the last method call
     */
    function getBody() {
        return $this -> _body;
    }

    /**
     * Return the error code of any error result
     */
    function getErrorCode() {
        if ($this -> error) {
            return $this -> error -> code;
        }
        return 0;
    }

    /**
     * Return the message of any error result
     */
    function getErrorMessage() {
        if ($this -> error) {
            return $this -> error -> message;
        }
        return '';
    }

    /**
     * Return the HTTP header of the most recent request
     */
    function getHeader() {
        return $this -> _request -> headers;
    }

    /**
     * Return a string identifying the host server and timeout
     */
    function getHost() {
        return $this  -> _host . ':' . $this -> port . ' T/O=' . $this -> _timeout;
    }

    /**
     * Return the decoded response from a server.
     */
    function getResponse() {
        return $this -> _response;
    }

    /**
     * Get the server response code
     */
    function getResponseCode() {
        if (! $this -> _response) {
            throw new AP5L_Http_Exception(
                'No response available.');
        }
        $bits = explode(' ', $this -> _response -> start);
        array_shift($bits);
        $code = (int) array_shift($bits);
        return $code;
    }

    /**
     * Get a version string that incorporates the last revision number.
     */
    function getVersion() {
        $rev = '$LastChangedRevision: 100 $';
        $rev = trim(substr($rev, 21, strlen($rev) - 22));
        return '/' . self::BASE_VERSION . '.' . $rev;
    }

    /**
     * Return true if the client has an error status.
     */
    function isError() {
        return (is_object($this -> error));
    }

    /**
     * Set client options.
     *
     * @param array Options, an associative array keyed by option name. Options
     * are processed sequentially, overwriting any previously conflicting
     * settings.
     * <ul><li>"body" The request body (for WEBDAV methods).
     * </li><li>"debug" Debugging output level. Integer. Defaults to none (0).
     * </li><li>"header" A header setting. Pass an array with the header name as
     * index, header value as content. This is merged with the existing header
     * array.
     * </li><li>"headers" Request headers. Pass an array with the header name as
     * index, header value as content. This replaces any existing header array.
     * </li><li>"host" Server host name, eg. www.example.com.
     * </li><li>"masquerade" Masquerade as another browser type. A string of
     * "browser version platform", eg. "FF 2 WinXP"
     * </li><li>"method" The request method, currently one of "GET", "HEAD",
     * "POST". This value is not case sensitive. The default method is GET.
     * </li><li>"params" Request parameters.
     * </li><li>"password" Password for authenticated connections.
     * </li><li>"path" The request path on the host server.
     * </li><li>"uri" or "url" A complete URI for the server. Can include
     * protocol, host, path, user, password, and query parameters. If any of
     * these are present the overwrite any previous settings.
     * </li><li>"user" User for authenticated connections.
     * </li></ul>
     *
     */
    function setOptions($options) {
        foreach ($options as $option => $setting) {
            switch ($option) {
                case 'body': {
                    $this -> _body = $setting;
                }
                break;

                case 'debug': {
                    $this -> _debug = $setting;
                }
                break;

                case 'header': {
                    foreach ($setting as $head => $data) {
                        $this -> _headers[$head] = $data;
                    }
                }
                break;

                case 'headers': {
                    $this -> _headers = $setting;
                }
                break;

                case 'host': {
                    $this -> _host = $setting;
                }
                break;

                case 'masquerade': {
                    $this -> _masquerade($setting);
                }
                break;

                case 'method': {
                    $setting = strtoupper($setting);
                    if (strpos('.GET.HEAD.POST.PROPFIND', '.' . $setting) !== false) {
                        $this -> _method = $setting;
                    }
                }
                break;

                case 'params': {
                    $this -> setParameters($setting);
                }
                break;

                case 'password': {
                    $this -> _password = $setting;
                }
                break;

                case 'path': {
                    $this -> _path = $setting;
                }
                break;

                case 'port': {
                    $this -> _port = $setting;
                }
                break;

                case 'uri':
                case 'url': {
                    $elements = parse_url($setting);
                    if (isset($elements['host'])) {
                        $this -> _host = $elements['host'];
                    }

                    if (! isset($elements['scheme'])) {
                        $elements['scheme'] = 'http';
                    }
                    if (isset($elements['port'])) {
                        $this -> _port = $elements['port'];
                    }
                    if (isset($elements['path'])) {
                        $this -> _path =  $elements['path'];
                    }
                    if (isset($elements['query'])) {
                        $this -> _params = array();
                        $params = explode('&', $elements['query']);
                        foreach ($params as $param) {
                            if (($posn = strpos($param, '=')) !== false) {
                                $key = urldecode(substr($param, 0, $posn));
                                $this -> _params[$key] =
                                    urldecode(substr($param, $posn + 1));
                            } else {
                                $this -> _params[urldecode($param)] = '';
                            }
                        }
                    }
                    if (isset($elements['user'])) {
                        $this -> _userName = urldecode($elements['user']);
                    }
                    if (isset($elements['pass'])) {
                        $this -> _password = urldecode($elements['pass']);
                    }
                }
                break;

                case 'user': {
                    $this -> _userName = $setting;
                }
                break;

            }
        }
        // Make absolutely sure we have a path
        if (! $this -> _path) {
            $this -> _path = '/';
        }
    }

    /**
     * Define request parameters.
     *
     * The passed parameters will be used when executing the request. If the
     * request uses the GET method, these will be added to the URI; if the
     * method is POST, they will be added to the message body.
     *
     * @param array Associative array of key, value pairs.
     */
    function setParameters($params) {
        $this -> _params = $params;
    }

}

?>