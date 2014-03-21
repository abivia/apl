<?php
/**
 * AP5L The Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Store.php 61 2008-06-01 17:20:30Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

class AP5L_Text_L10n_ implements AP5L_Text_L10n_Store {
    /**
     * Root of the message file structure.
     * 
     * @var string
     */
    protected $_fileRoot;
    
    /**
     * Flag to track open status.
     * 
     * @var boolean
     */
    protected $_isOpen;

    /**
     * Add or replace a hint.
     *
     * @param AP5L_Text_L10n_Hint Hint definition.
     */
    function addHint($hint) {
        throw AP5L_Exception::factory('Text', '_err.whatever');
    }

    /**
     * Add or replace a message.
     *
     * @param AP5L_Text_L10n_Message The message object.
     */
    function addMessage($message) {
        throw AP5L_Exception::factory('Text', '_err.whatever');
    }

    /**
     * Disconnect.
     */
    function close() {
        $this -> _isOpen = false;
    }

    /**
     * Get a specific hint.
     *
     * @param string Message code of the hint to be loaded.
     * @return AP5L_Text_L10n_Hint|false The hint object, if found.
     */
    function getHint($messageCode) {
        return false;
    }

    /**
     * Get a list of hints.
     *
     * Hints are language independent.
     *
     * @param string Optional match filter. A message code, wildcards allowed.
     * @return array AP5L_Text_L10n_Hint
     */
    function getHints($filter = '') {
        return array();
    }

    /**
     * Get a list of all defined message codes.
     *
     * @return array Unique message codes.
     */
    function getMessageCodeList() {
        // this is a little brutal, requires loading everything.
    }

    /**
     * Get a list of all defined message codes and languages.
     *
     * @return array Unique message codes.
     */
    function getMessageLanguageList() {
        // also brutal.
    }

    /**
     * Return the data store connection state.
     *
     * @return boolean True if the store object is connected (open).
     */
    function isOpen() {
        return $this -> _isOpen;
    }

    /**
     * Connect to the data store.
     *
     * @param string Data store name. Syntax is dependent on the implementation.
     * @param array Connection options. Options are implementation dependent.
     */
    function open($dsn, $options = array()) {
        /*
         * Parse_url fails horribly on file-like URLs that don't start with
         * file, so we try to regex off any scheme specifier.
         */
        $dsn = preg_replace('|^[a-z0-9]+/{2,3}|c', '');
        $dsn = str_replace('\\', '/', $dsn);
        if (! is_dir($dsn)) {
            throw AP5L_Exception::factory('Text', '_err.whatever');
        }
        $dsn .= $dsn[strlen($dsn) - 1] == '/' ? '' : '/';
        $this -> _fileRoot = $dsn;
        $this -> _isOpen = true;
    }

    /**
     * Read a block of records that match a set of keys.
     *
     * @param string Language specifier.
     * @param array Array of Hint objects as  message selectors.
     * @return array Matching messages.
     */
    function readBlock($language, $selectors) {
        $lTerms = array();
        foreach ($language as $lSel) {
            $breakdown = explode('-', $lSel);
            while (! empty($breakdown)) {
                $lTerms[] = implode('-', $breakdown);
                array_pop($breakdown);
            }
        }
        foreach ($selectors as $hint) {
            
        }
    }

    /**
     * Remove a hint.
     *
     * @param string Message code of the hint to be removed.
     */
    function removeHint($messageCode) {
    }

    /**
     * Remove a message.
     *
     * @param string The message code to be removed. Wildcard characters are
     * allowed.
     * @param string Optional language specifier. If provided, only messages
     * that match the message code and language are removed. Wildcard characters
     * are allowed. An empty language means any language.
     */
    function removeMessage($messageCode, $language = '') {
    }

    /**
     * Set the data store name.
     *
     * @param string Data store name.
     */
    function setDsn($dsn) {
    }

}
