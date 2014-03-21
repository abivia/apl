<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: Exception.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Forms Exception class.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Exception extends Exception {
    /**
     * General case for attempts to do bad things, such as adding a choice to a
     * text field.
     */
    const ERR_BADOP = 1;
    /**
     * Attempt to find a non-existent field.
     */
    const ERR_NO_FIELD = 2;

    protected $pearError;

    function __construct($message, $code = 0, $pearError = null) {
        parent::__construct($message, $code);
        $this -> pearError = $pearError;
    }

    function __toString() {
        $pearPart = PEAR::isError($this -> pearError)
            ? (' ' . $this -> pearError -> toString()) : '';
        return parent::__toString() . $pearPart;
    }

    function isPearError() {
        return PEAR::isError($this -> pearError);
    }
}
