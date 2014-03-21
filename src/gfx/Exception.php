<?php
/**
 * Graphics package exception.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Exception.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Graphics package exception.
 */
class AP5L_Gfx_Exception extends AP5L_Exception {
    /**
     * Nothing to do.
     * 
     * Returned when there is nothing to do for a requested operation. Replaces
     * returning false instead of 0 from a function and testing with triple
     * equals.
     */
    const NOTHING_TO_DO = 1;
}

