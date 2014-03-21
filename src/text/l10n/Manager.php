<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Manager.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Text message localization manager.
 *
 * Manage the maintanance of a database and cache of strings and their
 * translations.
 */
class AP5L_Text_L10n_Manager extends AP5L_Php_InflexibleObject {
    /**
     * Data store connection status
     *
     * @var boolean
     */
    protected $_isOpen;

    /**
     * Underlying data store.
     *
     * @var AP5L_Text_L10n_Store
     */
    protected $_store;

    /**
     * Add or replace a hint.
     *
     * @param AP5L_Text_L10n_Hint Hint definition. Only the message code and
     * hint instructions are required.
     */
    function addHint(AP5L_Text_L10n_Hint $hint) {
        $this -> _store -> addHint($hint);
    }

    /**
     * Add or replace a message.
     *
     * @param string The message identifier.
     */
    function addMessage($messageCode, $language, $messageText) {
        $messageCode = AP5L_Text_L10n::messageCodeClean($messageCode);
        if (empty($messageCode)) {
            throw new AP5L_Text_Exception('Message code cannot be empty.');
        }
        $msg = new AP5L_Text_L10n_Message;
        $msg -> messageCode = $messageCode;
        $msg -> language = $language;
        $msg -> messageText = $messageText;
        $this -> _store -> addMessage($msg);
    }

    function close() {
    }

    /**
     * Compare messages for two language codes.
     *
     * Returns a list of all messages in the source language with no equivalent
     * in the target language, messages in the target language with no
     * corresponding source message, and messages in the source language that
     * are more recent than those in the target.
     *
     * @param string Source language code.
     * @param string Target language code.
     * @return array Differences, indexed by message code: -1=present in target,
     * not in source; 0=Source more recent than target; 1=Missing from target.
     */
    function compareLanguages($source, $target) {
    }

    /**
     * Get information on a hint.
     *
     * @param string The message code of the hint to be loaded.
     * @return AP5L_Text_L10n_Hint|false Hint object, if found.
     */
    function getHint($messageCode) {
        $messageCode = AP5L_Text_L10n::messageCodeClean($messageCode);
        return $this -> _store -> getHint($messageCode);
    }

    function getMessageDetails($messageCode, $language = '') {
    }

    /**
     * Get a list of all hints.
     *
     * @param string Optional filter, wildcards allowed.
     */
    function listHints($messageCode) {
        $messageCode = AP5L_Text_L10n::messageCodeClean($messageCode, true);
        return $this -> _store -> getHints($messageCode);
    }

    /**
     * Get an array of all defined message codes, optionally with the languages
     * they are defined in.
     *
     * @param boolean Fetch language flag. If set, language mappings are
     * returned.
     * @return array Array indexed by message code. If no languages are
     * requested, each element is the message code. If languages are requested,
     * each element is an array of languages.
     */
    function listMessageCodes($withLang = false) {
        if ($withLang) {
            $mix = $this -> _store -> getMessageLanguageList();
            $codes = array();
            foreach ($mix as $msg) {
                if (! isset($codes[$msg[0]])) {
                    $codes[$msg[0]] = array($msg[1]);
                } else {
                    $codes[$msg[0]][] = $msg[1];
                }
            }
        } else {
            $codes = $this -> _store -> getMessageCodeList();
            $codes = array_combine($codes, $codes);
        }
        return $codes;
    }

    function open($options = array()) {
        if (isset($options['store']) && $options['store']) {
            $this -> setStore($options['store']);
        } elseif ($this -> _isOpen) {
            throw new AP5L_Text_Exception('Data store is already open.');
        }
        if (! $this -> _store -> isOpen()) {
            $this -> _store -> open($options);
        }
        $this -> _isOpen = true;
    }

    /**
     * Remove a hint.
     *
     * @param string|AP5L_Text_L10n_Hint Either a message code or a Hint
     * definition that contains the message code to be removed. Wildcards are
     * allowed.
     */
    function removeHint($messageCode) {
        if ($messageCode instanceof AP5L_Text_L10n_Hint) {
            $messageCode = $messageCode -> messageCode;
        }
        $messageCode = AP5L_Text_L10n::messageCodeClean($messageCode, true);
        $this -> _store -> removeHint($messageCode);
    }

    /**
     * Remove a message.
     *
     * A blank language is treated as a wildcard.
     *
     * @param string|AP5L_Text_L10n_Message Either a message code or a Message
     * object that contains the message code (and optionally language) to be
     * removed. Wildcard characters are allowed.
     * @param string Optional language specifier. Only used when the first
     * argument is a string. If provided, only messages that match the message
     * code and language are removed.
     */
    function removeMessage($messageCode, $language = '') {
        if ($messageCode instanceof AP5L_Text_L10n_Message) {
            $messageCode = $messageCode -> messageCode;
            $language = $messageCode -> language;
        }
        $messageCode = AP5L_Text_L10n::messageCodeClean($messageCode, true);
        $this -> _store -> removeMessage($messageCode, $language);
    }

    /**
     * Define the data store for the message manager.
     *
     * @param AP5L_Text_L10n_Store The data store object.
     */
    function setStore(AP5L_Text_L10n_Store $store) {
        if ($this -> _isOpen) {
            $this -> _store -> close();
            $this -> _isOpen = false;
        }
        $this -> _store = $store;
        $this -> _isOpen = $store -> isOpen();
    }

}