<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Exception.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * DB exceptions are thrown on errors originating within the data storage layer.
 * Other modules can throw these if there is a database related issue.
 *
 * @package AP5L
 * @subpackage Db
 * @todo Define common error codes.
 */
class AP5L_Db_Exception extends AP5L_Exception {
    /**
     * Default factory method
     */
    static function &factory($message = '', $code = 0, $details = array()) {
        $e = new AP5L_Db_Exception($message, $code);
        $e -> details = $details;
        return $e;
    }

}
