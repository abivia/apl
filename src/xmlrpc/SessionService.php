<?php
/**
 * Session capable XML-RPC Server.
 * 
 * @package AP5L
 * @subpackage XmlRpc
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: SessionService.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Adds session capabilities to a XML-RPC based Web services provider.
 * 
 * This class must be used as a singleton. Once a session is created, session
 * data is stored in the session. {@see self::$sessionVarName}.
 */
class AP5L_XmlRpc_SessionService extends AP5L_XmlRpc_Server {
    /**
     * Name of the session variable to use for storing session context
     * (authentication status, nonce, etc.)
     * 
     * @var string
     */
    static $sessionVarName = 'AP5L_XmlRpc_SessionService';
    
    function __construct($callbacks = false, $data = false, $start = true) {
        parent::__construct($callbacks, $data, $start);
        /*
         * Set a catch-all method. If an explicit method match is not found,
         * this one is used.
         */ 
        $this -> callbacks[''] = 'this:dispatch';
    }
    
    /**
     * Internal RPC call dispatcher. Derived classes should override and chain
     * to this method.
     * 
     * @param array Command verbs.
     * @param array Command arguments.
     * @return mixed Return value depends on invoked method.
     */
    protected function _dispatch($command, $args) {
        //
        // The first argument is a command string to determine which sub-functions to call.
        //
        $cmd = array_shift($command);
        switch ($cmd) {
            case 'session': {
                return $this -> handleSession($command, $args);
            } break;
            
            default: {
                return __CLASS__ . ': Unable to dispatch "' . $cmd . '"';
            } break;
        }
    }
    
    /**
     * Create a session if none exists.
     * @return string Session ID of new session, if created. Existing session ID
     * if not created.
     */
    function _sessionStart() {
        if (! session_id()) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Authentication process. Should check user and digest arguments for
     * validity. This stub always returns true.
     * 
     * @param array Session status settings.
     * @param string User identifier.
     * @param string Hash of user password and nonce.
     * @return boolean True if authentication is successful.
     */
    function authenticate(&$status, $user, $digest) {
        return true;
    }
    
    /**
     * Generate a random string.
     *
     * @param int Length of the string to generate.
     * @param string Optional. The set of characters to use when generating the
     * string. Missing or empty string defaults to upper and lower case ASCII
     * alphanumerics.
     * @return string Random string.
     */
    static function createRandomString($length = 8, $fromSet = '') {
        if (! $fromSet) {
            $fromSet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        }
        $len = strlen($fromSet);
        $random = '';
        for ($posn = 0; $posn < $length; ++$posn) {
            $random .= $fromSet[mt_rand(0, $len - 1)];
        }
        return $random;
    }

    /**
     * Handle and dispatch an RPC call. 
     * 
     * @param array Arguments passed from the RPC server. [0] is sessionID or
     * blank.
     * @return mixed Return value depends on invoked method.
     */
    function dispatch($args) {
        /*
         * Break up the method, which we expect to be a list of verbs delimited
         * by periods, into an array of verbs.
         */
        $command = explode('.', $this -> _methodName);
        /*
         * The first argument is the session
         */
        $this -> loadSession(array_shift($args));
        return $this -> _dispatch($command, $args);
    }
    
    /**
     * Process a command in the "session" group.
     * 
     * This method handles the "auth", "destroy", "isauth" and "start" commands.
     * 
     * @param array Decomposed components of the method, after "session" has
     * been removed.
     * @param array All arguments following the command verb.
     */
    function handleSession($command, $args) {
        $cmd = array_shift($command);
        switch ($cmd) {
            case 'auth': {
                /*
                 * Session authentication request.
                 */
                // auth($user, $digest)
                if (! isset($_SESSION[self::$sessionVarName])) {
                    $_SESSION[self::$sessionVarName] = array('auth' => false);
                }
                $status = &$_SESSION[self::$sessionVarName];
                $user = array_shift($args);
                $digest = array_shift($args);
                $status['auth'] = $this -> authenticate($status, $user, $digest);
                $status['user'] = $status['auth'] ? $user : '';
                return $status['auth'];
            }
            break;
            
            case 'destroy': {
                // destroy()
                $_SESSION = array();
                session_destroy();
                return true;
            } break;

            case 'isauth': {
                // isauth()
                if (! isset($_SESSION[self::$sessionVarName])) {
                    $_SESSION[self::$sessionVarName] = array('auth' => false);
                }
                return $_SESSION[self::$sessionVarName]['auth'];
            }
            break;
            
            case 'start': {
                // start([send_nonce])
                $id = $this -> _sessionStart();
                $sendNonce = count($args) ? array_shift($args) : false;
                if ($sendNonce) {
                    $nonce = md5(uniqid($id . mt_rand(), true));
                    $_SESSION[self::$sessionVarName] = array(
                        'auth' => false,
                        'nonce' => $nonce,
                        'user' => ''
                    );
                    return array($id, $nonce);
                }
                return $id;
            }
            break;
            
            default: {
                return 'Unknown command ' . $cmd;
            }
            break;

        }
    }
    
    /**
     * Start a session based on an ID.
     * @param string Session identifier.
     * @return none
     */
    function loadSession($id) {
        //
        // We assume the user agent doesn't see or send cookies
        //
        ini_set('session.use_cookies', '0');
        // Make sure the ID is present and valid before using it
        if (preg_match('/^[0-9a-z]+$/i', $id)) {
            session_id($id);
        }
        session_start();
    }

}

?>