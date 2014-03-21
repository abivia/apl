<?php
/**
 * Abivia PHP5 Library
 *
 * @package AP5L
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ApplicationException.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Application exception.
 *
 * Application exceptions are thrown when something isn't set up correctly
 * within an application. This is usually a programming error.
 *
 */
class AP5L_ApplicationException extends AP5L_Exception {
    /**
     * Default factory method
     */
    static function &factory($message = '', $code = 0, $details = array()) {
        $e = new AP5L_ApplicationException($message, $code);
        $e -> details = $details;
        return $e;
    }

}
