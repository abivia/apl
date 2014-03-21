<?php
/**
 * Introspection capable server.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: IntrospectionServer.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Introspection server.
 */
class AP5L_XmlRpc_IntrospectionServer extends AP5L_XmlRpc_Server {
    var $signatures;
    var $help;
    
    function __construct() {
        $this -> setCallbacks();
        $this -> setCapabilities();
        $this -> capabilities['introspection'] = array(
            'specUrl' => 'http://xmlrpc.usefulinc.com/doc/reserved.html',
            'specVersion' => 1
        );
        $this -> addCallback(
            'system.methodSignature',
            'this:methodSignature',
            array('array', 'string'),
            'Returns an array describing the return type and required parameters of a method'
        );
        $this -> addCallback(
            'system.getCapabilities',
            'this:getCapabilities',
            array('struct'),
            'Returns a struct describing the XML-RPC specifications supported by this server'
        );
        $this -> addCallback(
            'system.listMethods',
            'this:listMethods',
            array('array'),
            'Returns an array of available methods on this server'
        );
        $this -> addCallback(
            'system.methodHelp',
            'this:methodHelp',
            array('string', 'string'),
            'Returns a documentation string for the specified method'
        );
    }
    
    function addCallback($method, $callback, $args, $help) {
        $this -> callbacks[$method] = $callback;
        $this -> signatures[$method] = $args;
        $this -> help[$method] = $help;
    }
    
    function call($methodName, $args) {
        // Make sure it's in an array
        if ($args && !is_array($args)) {
            $args = array($args);
        }
        // Over-rides default call method, adds signature check
        if (!$this -> isMethod($methodName)) {
            return new AP5L_XmlRpc_Error(-32601, 'Server error. Requested method "' . $this -> message -> methodName . '" not specified.');
        }
        $method = $this -> callbacks[$methodName];
        $signature = $this -> signatures[$methodName];
        $returnType = array_shift($signature);
        // Check the number of arguments
        if (count($args) != count($signature)) {
            // print 'Num of args: ' . count($args) . ' Num in signature: ' . count($signature);
            return new AP5L_XmlRpc_Error(-32602, 'Server error. Wrong number of method parameters');
        }
        // Check the argument types
        $ok = true;
        $argsbackup = $args;
        for ($i = 0, $j = count($args); $i < $j; $i++) {
            $arg = array_shift($args);
            $type = array_shift($signature);
            switch ($type) {
                case 'int':
                case 'i4':
                    if (is_array($arg) || !is_int($arg)) {
                        $ok = false;
                    }
                    break;
                case 'base64':
                case 'string':
                    if (!is_string($arg)) {
                        $ok = false;
                    }
                    break;
                case 'boolean':
                    if ($arg !== false && $arg !== true) {
                        $ok = false;
                    }
                    break;
                case 'float':
                case 'double':
                    if (!is_float($arg)) {
                        $ok = false;
                    }
                    break;
                case 'date':
                case 'dateTime.iso8601':
                    if (!$arg instanceof AP5L_XmlRpc_Date) {
                        $ok = false;
                    }
                    break;
            }
            if (!$ok) {
                return new AP5L_XmlRpc_Error(-32602, 'Server error. Invalid method parameters');
            }
        }
        // It passed the test - run the "real" method call
        return parent::call($methodName, $argsbackup);
    }

    function methodSignature($method) {
        if (!$this -> isMethod($method)) {
            return new AP5L_XmlRpc_Error(-32601, 'Server error. Requested method "' . $method . '" not specified.');
        }
        // We should be returning an array of types
        $types = $this -> signatures[$method];
        $return = array();
        foreach ($types as $type) {
            switch ($type) {
                case 'string':
                    $return[] = 'string';
                    break;
                case 'int':
                case 'i4':
                    $return[] = 42;
                    break;
                case 'double':
                    $return[] = 3.14159;
                    break;
                case 'dateTime.iso8601':
                    $return[] = new AP5L_XmlRpc_Date(time());
                    break;
                case 'boolean':
                    $return[] = true;
                    break;
                case 'base64':
                    $return[] = new AP5L_XmlRpc_Base64('base64');
                    break;
                case 'array':
                    $return[] = array('array');
                    break;
                case 'struct':
                    $return[] = array('struct' => 'struct');
                    break;
            }
        }
        return $return;
    }

    function methodHelp($method) {
        return $this -> help[$method];
    }

}

?>