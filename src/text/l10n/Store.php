<?php
/**
 * AP5L The Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Store.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

interface AP5L_Text_L10n_Store {

    /**
     * Add or replace a hint.
     *
     * @param AP5L_Text_L10n_Hint Hint definition.
     */
    function addHint($hint);

    /**
     * Add or replace a message.
     *
     * @param AP5L_Text_L10n_Message The message object.
     */
    function addMessage($message);

    /**
     * Disconnect.
     */
    function close();
    
    /**
     * Get a specific hint.
     * 
     * @param string Message code of the hint to be loaded.
     * @return AP5L_Text_L10n_Hint|false The hint object, if found.
     */
    function getHint($messageCode);

    /**
     * Get a list of hints.
     *
     * Hints are language independent.
     *
     * @param string Optional match filter. A message code, wildcards allowed.
     * @return array AP5L_Text_L10n_Hint
     */
    function getHints($filter = '');

    /**
     * Get a list of all defined message codes.
     *
     * @return array Unique message codes.
     */
    function getMessageCodeList();

    /**
     * Get a list of all defined message codes and languages.
     *
     * @return array Unique message codes.
     */
    function getMessageLanguageList();
    
    /**
     * Return the data store connection state.
     * 
     * @return boolean True if the store object is connected (open). 
     */
    function isOpen();

    /**
     * Connect to the data store.
     *
     * @param string Data store name. Syntax is dependent on the implementation.
     * @param array Connection options. Options are implementation dependent.
     */
    function open($dsn, $options = array());

    /**
     * Read a block of records that match a set of keys.
     *
     * @param string Language specifier.
     * @param array List of message selectors.
     * @return array Matching messages.
     */
    function readBlock($language, $selectors);
    
    /**
     * Remove a hint.
     * 
     * @param string Message code of the hint to be removed.
     */
    function removeHint($messageCode);
    
    /**
     * Remove a message.
     *
     * @param string The message code to be removed. Wildcard characters are
     * allowed.
     * @param string Optional language specifier. If provided, only messages
     * that match the message code and language are removed. Wildcard characters
     * are allowed. An empty language means any language.
     */
    function removeMessage($messageCode, $language = '');
    
    /**
     * Set the data store name.
     * 
     * @param string Data store name.
     */
    function setDsn($dsn);

}
