<?php
/**
 * XML-RPC Client.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Client.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * RPC client
 */
class AP5L_XmlRpc_Client {
    const XMLRPC_BASE_VERSION = 1.2;

    static $baseUserAgent = 'Abivia XML-RPC PHP Library';
    var $debug = false;
    var $dumpCallback;                  // Callback for diagnostic/trace output
    var $eol = '';                      // EOL char for XML output
    var $error = false;                 // Error object
    var $header;                        // The HTTP header for the query
    var $message = false;
    var $password;
    var $path;
    var $port;
    var $request;                       // AP5L_XmlRpc_Request object for the query
    var $response;
    var $server;
    var $timeout = 60;
    var $userAgent;
    var $userName;

    /**
     * XML-RPC client constructor.
     *
     * @param string server Server host name or full URI for the server. If this
     * parameter is a URI, all other parameters are ignored.
     * @param string path Path to the server on the host. Optional id server is
     * a URI.
     * @param integer port Port number on the server. Optional. Default is 80.
     * @param string userName User name in the event of a restricted access
     * server. Optional.
     * @param string password Password in the event of a restricted access
     * server. Optional.
     */
    function __construct($server, $path = '', $port = 80, $userName = '', $password = '') {
        if (! $path) {
            // Assume we have been given a URL instead
            if (! $server) {
                return;
            }
            $elements = parse_url($server);
            if (! isset($elements['host'])) {
                return;
            }
            $this -> server = $elements['host'];
            if (! isset($elements['scheme'])) {
                $elements['scheme'] = 'http';
            }
            $this -> port = isset($elements['port']) ? $elements['port'] : 80;
            $this -> path = isset($elements['path']) ? $elements['path'] : '/';
            // Make absolutely sure we have a path
            if (! $this -> path) {
                $this -> path = '/';
            }
            if (isset($elements['user'])) {
                $this -> userName = urldecode($elements['user']);
            }
            if (isset($elements['pass'])) {
                $this -> password = urldecode($elements['pass']);
            }
        } else {
            $this -> server = $server;
            $this -> path = $path;
            $this -> port = $port;
            $this -> userName = $userName;
            $this -> password = $password;
        }
        $this -> userAgent = self::$baseUserAgent . $this -> getVersion();
    }

    /**
     * Gateway for diagnostic output.
     */
    function _dump($str, $type = 'text/plain') {
        if ($this -> dumpCallback) {
            call_user_func($this -> dumpCallback, $str, $type);
        } else {
            echo $str;
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
     * Send a query to an XML-RPC server and return the decoded response.
     */
    function call() {
        if (! $this -> query(func_get_args())) {
            return false;
        }
        return $this -> getResponse();
    }

    /**
     * Get a version string that incorporates the last revision number.
     */
    function getVersion() {
        $rev = '$LastChangedRevision: 91 $';
        $rev = trim(substr($rev, 21, strlen($rev) - 22));
        return '/' . self::XMLRPC_BASE_VERSION . '.' . $rev;
    }

    /**
     * Send a query to an XML-RPC server and get a response.
     */
    function query() {
        $this -> error = false;
        $args = func_get_args();
        if (is_array($args[0]) && func_num_args() == 1) {    // Args passed as an array
            $args = $args[0];
        }
        $method = array_shift($args);
        if ($this -> debug >= 2) {
            $this -> _dump('AP5L_XmlRpc_Client::query: method=' . $method
                . ' args=' . print_r($args, true));
        }
        $this -> request = new AP5L_XmlRpc_Request($method, $args, $this -> eol);
        $length = $this -> request -> getLength();
        $r = chr(10);
        $this -> header  = 'POST ' . $this -> path . ' HTTP/1.0' . $r
            . 'Host: ' . $this -> server . $r;
        if ($this -> userName || $this -> password) {
            $this -> header .= 'Authorization: Basic ' . base64_encode($this -> userName . ':' . $this -> password) . $r;
        }
        $this -> header .= 'Content-Type: text/xml' . $r
            . 'User-Agent: ' . $this -> userAgent . $r
            . 'Content-length: ' . $length . $r . $r;
        $requestXml = $this -> request -> getXml();
        // Now send the request
        if ($this -> debug) {
            $start = microtime();
            $this -> _dump($this -> header);
            $this -> _dump($requestXml, 'text/xml');
            $this -> _dump('Timeout: ' . $this -> timeout . 's @ ' . $start . chr(10) . chr(10));
        }
        $fp = @fsockopen($this -> server, $this -> port, $errNum, $errStr, $this -> timeout);
        if (!$fp) {
            $this -> error = new AP5L_XmlRpc_Error(-32300,
                'Transport error '. $errNum . ': ' . $errStr . chr(10)
                . 'Could not open socket on ' . $this -> getHost() . chr(10));
            return false;
        }
        stream_set_timeout($fp, $this -> timeout);
        fwrite($fp, $this -> header . $requestXml);
        $contents = '';
        $error = false;
        $gotFirstLine = false;
        $gettingHeaders = true;
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if (!$gotFirstLine) {
                // Check line for '200'
                if (strstr($line, '200') === false) {
                    $this -> error = new AP5L_XmlRpc_Error(-32300,
                        'Transport error - HTTP status code was not 200: "' . $line . '"'
                        . ' T/O=' . $this -> timeout);
                    $error = true;
                }
                $gotFirstLine = true;
            }
            if (trim($line) == '') {
                $gettingHeaders = false;
            }
            if (! $gettingHeaders) {
                $contents .= trim($line);
            }
        }
        if ($this -> debug) {
            $this -> _dump('Response t=' . $this -> _elapsedTime($start));
            $this -> _dump($contents, 'text/xml');
        }
        if ($error) return false;
        // Now parse what we've got back
        $this -> message = new AP5L_XmlRpc_Message($contents);
        if (!$this -> message -> parse()) {
            // XML error
            $this -> error = new AP5L_XmlRpc_Error(-32700, 'parse error. not well formed: ' . $this -> message -> faultString);
            return false;
        }
        // Is the message a fault?
        if ($this -> message -> messageType == 'fault') {
            $this -> error = new AP5L_XmlRpc_Error($this -> message -> faultCode, $this -> message -> faultString);
            return false;
        }
        // Message must be OK
        if ($this -> debug) {
            $this -> _dump('Return t=' . $this -> _elapsedTime($start));
        }
        return true;
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
        return $this -> header;
    }

    /**
     * Return a string identifying the host server and timeout
     */
    function getHost() {
        return $this  -> server . ':' . $this -> port . ' T/O=' . $this -> timeout;
    }

    /**
     * Return the last message received from a server
     */
    function getMessage() {
        if (! is_object($this -> message)) {
            return false;
        }
        return $this -> message -> message;
    }

    /**
     * Return the body of the last method call
     */
    function getRequest() {
        return $this -> request;
    }

    /**
     * Return the decoded response from a server.
     */
    function getResponse() {
        // methodResponses can only have one param - return that
        return $this -> message -> params[0];
    }

    /**
     * Return true if the client has an error status.
     */
    function isError() {
        return (is_object($this -> error));
    }

}

?>