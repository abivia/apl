<?php
/**
 * Redirect to secure/non-secure pages.
 * 
 * @package AP5L
 * @subpackage Http
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: SecureRedirect.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

class SecureRedirect {
    var $hostMaps;
    var $noHttp;                        // Hosts that won't support HTTP
    var $noHttps;                       // Hosts that won't support HTTPS
    
    function __construct() {
        $this -> hostMaps = array();
    }
    
    /**
     *  Maps source hosts and redirects a page to a
     *  secure/insecure protocol, as requested.
     **/
    function redirect($toSecure) {
        if (is_string($toSecure)) {
            $toSecure = strtolower($toSecure) == 'https';
        }
        $secure = isset($_SERVER['HTTPS']);
        $host = strtolower($_SERVER['HTTP_HOST']);
        $redirect = false;
        if (count($this -> hostMaps)) {
            foreach ($this -> hostMaps as $mapFrom => $mapTo) {
                if (preg_match('/' . $mapFrom . '/i', $host)) {
                    $host = $mapTo;
                    $redirect = true;
                }
            }
        }
        if (isset($this -> noHttps[$host])) {    // This config doesn't support https
            $toSecure = false;
        }
        if (isset($this -> noHttp[$host])) {    // This config doesn't support http
            $toSecure = true;
        }
        if ($toSecure) {
            $protocol = "https://";
        } else {
            $protocol = "http://";
        }
        $target = $protocol . $host . $_SERVER['REQUEST_URI'];
        if ($redirect || $secure != $toSecure) {
            header("Location: $target");
        }
    }
    
    function setMap($from, $to) {
        if (preg_match('/' . $from . '/i', $to)) {
            // The to expression matches the from expression.
            // This is a circular reference, which is bad.
            return false;
        }
        foreach ($this -> hostMaps as $mapFrom => $mapTo) {
            if (preg_match('/' . $mapFrom . '/i', $to)) {
                // The to expression matches an existing from expression.
                // This is a circular reference, which is bad.
                return false;
            }
        }
        $this -> hostMaps[$from] = $to;
        return true;
    }
    
}
?>
