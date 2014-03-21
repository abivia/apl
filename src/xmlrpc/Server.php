<?php
/**
 * XML-RPC Server
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Server.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Server class.
 */
class AP5L_XmlRpc_Server {
    /**
     * Name of the current/last method. Useful for catch-all method handlers.
     *
     * @var string
     */
    protected $_methodName;
    var $callbacks = array();
    var $capabilities;
    var $_classDefs = array();
    var $convertSingleArgument = true;
    var $data;
    var $eol = '';
    var $message;
    var $traceCallback;                 // Routine to write trace logs

    function __construct($callbacks = false, $data = false, $start = true) {
        $this -> setCapabilities();
        if ($callbacks) {
            $this -> callbacks = $callbacks;
        }
        $this -> setCallbacks();
        if ($start) {
            $this -> serve($data);
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

    function call($methodName, $args) {
        $this -> _methodName = $methodName;
        if ($this -> traceCallback) {
            $start = microtime();
            call_user_func($this -> traceCallback, 'call', $methodName, $args);
        }
        if (! $method = $this -> getMethod($methodName)) {
            $error = new AP5L_XmlRpc_Error(-32601, 'Server error. Requested method "' . $methodName . '" does not exist.');
            if ($this -> traceCallback) {
                call_user_func($this -> traceCallback, 'error', $methodName, $error -> __toString());
            }
            return $error;
        }
        // Perform the callback and send the response
        if (count($args) == 1 && $this -> convertSingleArgument) {
            // If only one parameter just send that instead of the whole array
            $args = $args[0];
        }
        // Are we dealing with a function, a method, or a static method?
        if (substr($method, 0, 5) == 'this:') {
            // It's a class method - check it exists
            $method = substr($method, 5);
            if (! method_exists($this, $method)) {
                $error = new AP5L_XmlRpc_Error(-32601,
                    'Server error. Requested class method "'
                    . $method . '" does not exist.');
                if ($this -> traceCallback) {
                    call_user_func($this -> traceCallback, 'error', $methodName,
                        $error -> __toString());
                }
                return $error;
            }
            // Call the method
            try {
                $result = $this -> $method($args);
            } catch (Exception $e) {
                $error = new AP5L_XmlRpc_Error(-32500,
                    'Application error. ' . (string) $e
                );
                if ($this -> traceCallback) {
                    call_user_func(
                        $this -> traceCallback, 'error', $methodName,
                        (string) $e
                    );
                }
                return $error;
            }
        } else if (($posn = strpos($method, '::')) !== false) {
            // It's a static class method. Does it exist?
            $className = substr($method, 0, $posn);
            $method = substr($method, $posn + 2);
            if (version_compare(PHP_VERSION, '5.0.0', '<')) {
                $method = strtolower($method);
            }
            if ($thisClass = ($className == '')) {
                $className = get_class($this);
            } else if (! class_exists($className, false)) {
                // dynamic class load
                if (isset($this -> _classDefs[$className])) {
                    $file = $this -> _classDefs[$className];
                    @include_once($file);
                    if (! class_exists($className)) {
                        if (! file_exists($file)) {
                            $error = new AP5L_XmlRpc_Error(-32601,
                                'Server error. Unable to load class "'
                                . $className . '" from "' . $file . '".');
                        } else {
                            $error = new AP5L_XmlRpc_Error(-32601,
                                'Server error. Syntax error in "' . $file
                                . '" when loading "' . $className . '".');
                        }
                        if ($this -> traceCallback) {
                            call_user_func($this -> traceCallback, 'error',
                                $methodName, $error -> __toString());
                        }
                        return $error;
                    }
                } elseif (! class_exists($className)) {     // try __autoload()
                    $error = new AP5L_XmlRpc_Error(-32601, 'Server error. Class "'
                        . $className . '" not defined and not loadable.');
                    if ($this -> traceCallback) {
                        call_user_func($this -> traceCallback, 'error', $methodName, $error -> __toString());
                    }
                    return $error;
                }
            }
            $methodList = get_class_methods($className);
            if (! in_array($method, $methodList)) {
                $error = new AP5L_XmlRpc_Error(-32601,
                    'Server error. Requested class static method "'
                    . $className . '::' . $method . '" does not exist.');
                if ($this -> traceCallback) {
                    call_user_func($this -> traceCallback, 'error',
                        $methodName, $error -> __toString());
                }
                return $error;
            }
            // Call the method
            try {
                if ($thisClass) {
                    $result = $this -> $method($args);
                } else {
                    $result = call_user_func(array($className, $method), $args);
                }
            } catch (Exception $e) {
                $error = new AP5L_XmlRpc_Error(-32500,
                    'Application error. ' . (string) $e
                );
                if ($this -> traceCallback) {
                    call_user_func(
                        $this -> traceCallback, 'error', $methodName,
                        (string) $e
                    );
                }
                return $error;
            }
        } else {
            // It's a function - does it exist?
            if (!function_exists($method)) {
                $error = new AP5L_XmlRpc_Error(-32601,
                    'Server error. Requested function "'
                    . $method . '" does not exist.');
                if ($this -> traceCallback) {
                    call_user_func($this -> traceCallback, 'error',
                        $methodName, $error -> __toString());
                }
                return $error;
            }
            // Call the function
            try {
                $result = $method($args);
            } catch (Exception $e) {
                $error = new AP5L_XmlRpc_Error(-32500,
                    'Application error. ' . (string) $e
                );
                if ($this -> traceCallback) {
                    call_user_func(
                        $this -> traceCallback, 'error', $methodName,
                        (string) $e
                    );
                }
                return $error;
            }
        }
        if ($this -> traceCallback) {
            call_user_func($this -> traceCallback, 'return', $methodName,
                't=' . $this -> _elapsedTime($start));
        }
        return $result;
    }

    function error($error, $message = false) {
        // Accepts either an error object or an error code and message
        if ($message && !is_object($error)) {
            $error = new AP5L_XmlRpc_Error($error, $message);
        }
        $this -> output($error -> getXml());
    }

    function getCapabilities($args) {
        return $this -> capabilities;
    }

     function getMethod($method) {
        if (isset($this -> callbacks[$method])) {
            return $this -> callbacks[$method];
        }
        if (isset($this -> callbacks[''])) {
            return $this -> callbacks[''];
        }
        return false;
    }

    function listMethods($args) {
        // Returns a list of methods - uses array_reverse to ensure user defined
        // methods are listed before server defined methods
        return array_reverse(array_keys($this -> callbacks));
    }

    function multiCall($methodcalls) {
        // See http://www.xmlrpc.com/discuss/msgReader$1208
        $return = array();
        foreach ($methodcalls as $call) {
            $method = $call['methodName'];
            $params = $call['params'];
            if ($method == 'system.multicall') {
                $result = new AP5L_XmlRpc_Error(-32600, 'Recursive calls to system.multicall are forbidden');
            } else {
                $result = $this -> call($method, $params);
            }
            if ($result instanceof AP5L_XmlRpc_Error) {
                $return[] = array(
                    'faultCode' => $result -> code,
                    'faultString' => $result -> message
                );
            } else {
                $return[] = array($result);
            }
        }
        return $return;
    }

    function output($xml) {
        $hdr = '<?xml version="1.0"?>' . chr(10);
        $length = strlen($hdr) + strlen($xml);
        header('Connection: close');
        header('Content-Length: ' . $length);
        header('Content-Type: text/xml');
        header('Date: ' . gmdate('r'));
        echo $hdr;
        echo $xml;
    }

    function serve($data = false) {
        if (!$data) {
            $data = file_get_contents('php://input');
            if (! $data) {
               die('XML-RPC server accepts POST requests only.');
            }
        }
        $this -> message = new AP5L_XmlRpc_Message($data);
        if (!$this -> message -> parse()) {
            $this -> error(-32700, 'parse error. not well formed');
        }
        if ($this -> message -> messageType != 'methodCall') {
            $this -> error(-32600, 'Server error. Invalid XML-RPC. not conforming to spec. Request must be a methodCall');
        }
        $result = $this -> call($this -> message -> methodName, $this -> message -> params);
        // Is the result an error?
        if ($result instanceof AP5L_XmlRpc_Error) {
            $this -> error($result);
            return;
        }
        // Encode the result
        $r = new AP5L_XmlRpc_Value($result);
        $resultxml = $r -> getXml($this -> eol);
        //
        // Format and send a response
        //
        $xml = '<methodResponse>' . $this -> eol . '<params>' . $this -> eol
            . '<param><value>' . $resultxml . '</value></param>'
            . $this -> eol . '</params>' . $this -> eol . '</methodResponse>' . chr(10);
        $this -> output($xml);
    }

    function setCallbacks() {
        $this -> callbacks['system.getCapabilities'] = 'this:getCapabilities';
        $this -> callbacks['system.listMethods'] = 'this:listMethods';
        $this -> callbacks['system.multicall'] = 'this:multiCall';
    }

    function setCapabilities() {
        // Initialises capabilities array
        $this -> capabilities = array(
            'xmlrpc' => array(
                'specUrl' => 'http://www.xmlrpc.com/spec',
                'specVersion' => 1
            ),
            'faults_interop' => array(
                'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
                'specVersion' => 20010516
            ),
            'system.multicall' => array(
                'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
                'specVersion' => 1
            ),
            /*------------------------ Still looking for a definitive reference for this
            'system.nilvalue' => array(
                'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$????',
                'specVersion' => 1
            ),
            -------------------------*/
            'system.structclass' => array(
                'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$2523',
                'specVersion' => 1
            ),
        );
    }

    function setClassDefs(&$defs) {
        $this -> _classDefs = $defs;
    }

}

?>