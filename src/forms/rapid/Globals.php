<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: Globals.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Global form system settings singleton.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Rapid_Globals extends AP5L_Php_InflexibleObject {
    /**
     * Text of class global messages to generate when creating the form.
     * 
     * @var array 
     */
    private $_classMessages;

    /**
     * Reference to a singleton for this class.
     * 
     * @var AP5L_Forms_Rapid_Globals 
     */
    static private $_instance;

    /**
     * External message generation handler. Defaults to none.
     * 
     * @var method 
     */
    private $_messageHandler = null;

    function __construct() {
        $this -> _classMessages = array(
            'button.back' => 'Go Back',
            'button.check' => 'Submit',
            'button.save' => 'Save',
            'check.checked' => 'Selected:',
            'check.unchecked' => 'Not selected:',
            'comment.heading' => 'Comment',
            'confirm.heading' => 'Please check your information to ensure it is correct.'
            . ' Click the "',
            'confirm.heading2' => '" button to make any corrections.',
            'div.footer' => '',
            'div.header' => '',
            'edit.heading' => '',
            'email.bad' => 'This is not a valid e-mail address.',
            'email.heading' => 'E-Mail Address',
            'firstname.heading' => 'First Name',
            'lastname.heading' => 'Last Name',
            'required' => 'Required fields are marked with',
            'required.bad' => 'A value is required.',
            'save_error' => 'Unexpected error while saving data.',
            'text.empty' => '(not supplied)',
            'title' => 'Send us a Comment',
            'verifier.bad' => 'Sorry your answer was incorrect. Please try again.',
            'verifier.heading' => 'Enter the text in the image (not case sensitive)'
            );
    }

    function getMessage($messageID, $language) {
        if (isset($this -> _classMessages[$messageID])) {
            return $this -> _classMessages[$messageID];
        } else {
            return '[' . $messageID . ']';
        }
    }

    function sessionRestore($key) {
        if (! isset($_SESSION[$key])) {
            $_SESSION[$key] = &self::singleton();
        } else {
            self::$_instance = &$_SESSION[$key];
        }
    }

    function setMessage($id, $language, $text) {
        $this -> _classMessages[$id] = $text;
    }

    static function &singleton() {
        if (! self::$_instance) {
            self::$_instance = &new AP5L_Forms_Rapid_Globals();
        }
        return self::$_instance;
    }

}
