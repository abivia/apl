<?php
/**
 * Filter HTTP data.
 * 
 * @package AP5L
 * @subpackage Http
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: SafeRequest.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Class to support more secure loading of request data
 */
class SafeRequest {
    const TYPE_NUMBER = 1;
    const TYPE_REGEX = 2;
    const TYPE_TEXT = 3;
    
    static $exceptionClass = 'Exception';
    
    static function get($default, $source, $varName, $type, $expr = '', $sub = null) {
        if (! isset($source[$varName])) {
            return $default;
        }
        $work = $source[$varName];
        if (get_magic_quotes_gpc()) {
            $work = stripslashes($work);
        }
        switch ($type) {
            case self::TYPE_NUMBER: {
                if (! is_numeric($work)) {
                    throw new $exceptionClass($varName . ' not numeric.');
                }
            }
            break;
            
            case self::TYPE_REGEX: {
                if (is_null($sub)) {
                    if (! preg_match($expr, $work)) {
                        throw new $exceptionClass($varName . ' did not match regex.');
                    }
                } else {
                    $work = preg_replace($expr, $sub, $work);
                }
            }
            break;
            
            case self::TYPE_TEXT: {
                // strip slashes already did the work
            }
            break;
            
        }
        return $work;
    }
}

?>