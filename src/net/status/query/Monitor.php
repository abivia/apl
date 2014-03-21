<?php
/**
 * A basic query only status monitor (contradiction, what contradiction?)
 *
 * @package AP5L
 * @subpackage Net.Status
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Monitor.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('PEAR.php');

/**
 * Query only status monitor.
 */
class AP5L_Net_Status_Query_Monitor {
    var $_config;                       // A StatusConfig structure
    var $configPath;                    // Path to configuration information

    function initialize($force = false) {
        $this -> _config = new AP5L_Net_Status_Config();
        if ($this -> _config -> read($this -> configPath) === false) {
            return new PEAR_Error($this -> _config -> errMsg, 1);
        }
        return true;
    }

    function query($serviceName = '') {
        if ($serviceName) {
            if (isset($this -> _config -> services[$serviceName])) {
                return $this -> queryService($this -> _config -> services[$serviceName]);
            } else {
                return new PEAR_Error('Unknown service name: ' . $serviceName, 1);
            }
        } else {
            $reports = array();
            foreach ($this -> _config -> services as $key => $service) {
                    $reports[] = $this -> queryService($service);
            }
            return $reports;
        }
    }

    function queryService($service) {
        if (isset($service -> checkProcess['php'])) {
            $method = $service -> checkProcess['php'];
            if (($posn = strpos($method, '::')) !== false) {
                // It's a static class method. Does it exist?
                $className = substr($method, 0, $posn);
                $method = strtolower(substr($method, $posn + 2));
                $thisClass = $className == '';
                if ($thisClass) {
                    $className = get_class($this);
                }
                if (! @in_array($method, get_class_methods($className))) {
                    return new PEAR_Error('Class static method "'
                        . $className . '::' . $method . '" referenced in service '
                        . $service -> serviceName . ' does not exist.', 1);
                }
                // Call the method
                if ($thisClass) {
                    $result = $this -> $$method($service -> serviceName);
                } else {
                    $result = call_user_func(array($className, $method), $service -> serviceName);
                }
            } else {
                // It's a function - does it exist?
                if (! function_exists($method)) {
                    return new PEAR_Error('Function "' . $method . '" referenced in service '
                        . $service -> serviceName . ' does not exist.');
                }
                // Call the function
                $result = $method($service -> serviceName);
            }
            // Copy service data to the report
            $result -> _description = $service -> description;
        } else {
            return new PEAR_Error('Service ' .$service -> serviceName
                . ' does not have a check process defined for PHP', 1);
        }
        return $result;
    }

}
?>