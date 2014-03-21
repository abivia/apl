<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Pdo.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

class AP5L_Text_L10n_Store_Pdo implements AP5L_Text_L10n_Store {
    /**
     * Parameters for a read block query.
     *
     * @var array of string
     */
    protected $_blockData;

    /**
     * SQL glue for a read block query.
     *
     * @var array of string
     */
    protected $_blockGlue;

    /**
     * Select clauses for a read block query.
     *
     * @var array of string
     */
    protected $_blockSel;

    /**
     * The database connection
     *
     * @var PDO
     */
    protected $_dbc;

    /**
     * The database connection string
     *
     * @var string
     */
    protected $_dsn;

    /**
     * A prefix for table names.
     *
     * @var string
     */
    protected $_prefix = '';

    function _addBlockSelect($select) {
        $cType = $select -> getType();
        if ($cType == AP5L_Text_L10n_Hint::TYPE_UNDEFINED) {
            return;
        }
        /*
         * Add clauses for equality to the message code or for membership in a
         * dotted submessage that uses this message code.
         */
        $slot = ':p' . count($this -> _blockData);
        $this -> _blockData[$slot] = $select -> messageCode;
        $this -> _blockSel[$cType] .= $this -> _blockGlue[$cType] . '(messageCode=' . $slot . ')';
        $this -> _blockGlue[$cType] = ' OR ';
        $slot = ':p' . count($this -> _blockData);
        $this -> _blockData[$slot] = $select -> messageCode . '.%';
        $this -> _blockSel[$cType] .= $this -> _blockGlue[$cType] . '(messageCode LIKE ' . $slot . ')';
        $this -> _blockGlue[$cType] = ' OR ';
        foreach ($this -> subHints as $subSel) {
            $this -> _addBlockSelect($subSel);
        }
    }

    /**
     * Add or replace a hint.
     *
     * @param AP5L_Text_L10n_Hint Hint definition.
     */
    function addHint($hint) {
        $sql = 'REPLACE ' . $this -> _prefix . 'message_hint'
            . ' SET messageCode=:p1, hint=:p2';
        $stmt = $this -> _dbc -> prepare($sql);
        $stmt -> bindValue('p1', $hint -> messageCode, PDO::PARAM_STR);
        $stmt -> bindValue('p2', $hint -> hint, PDO::PARAM_INT);
        $stmt -> execute();
    }

    /**
     * Add or replace a message.
     *
     * @param AP5L_Text_L10n_Message The message object.
     */
    function addMessage($message) {
        $sql = 'REPLACE ' . $this -> _prefix . 'message'
            . ' SET messageCode=:p1, message=:p2, longMessage=:p3, updateDate=:p4';
        $stmt = $this -> _dbc -> prepare($sql);
        $stmt -> bindValue('p1', $message -> messageCode, PDO::PARAM_STR);
        if (strlen($message -> messageText) > 255) {
            $stmt -> bindValue('p2', '', PDO::PARAM_STR);
            $stmt -> bindValue('p3', $message -> messageText, PDO::PARAM_STR);
        } else {
            $stmt -> bindValue('p2', $message -> messageText, PDO::PARAM_STR);
            $stmt -> bindValue('p3', null, PDO::PARAM_NULL);
        }
        $stmt -> bindValue('p4', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt -> execute();
    }

    /**
     * Disconnect.
     */
    function close() {
        $this -> _dbc = null;
    }

    /**
     * Get a specific hint.
     * 
     * @param string Message code of the hint to be loaded.
     * @return AP5L_Text_L10n_Hint|false The hint object, if found.
     */
    function getHint($messageCode) {
        $sql = 'SELECT * FROM ' . $this -> _prefix . 'message_hint'
            . ' WHERE messageCode LIKE :p1';
        $stmt = $this -> _dbc -> prepare($sql);
        $stmt -> bindValue('p1', $messageCode, PDO::PARAM_STR);
        if ($rec = $stmt -> fetch(PDO::FETCH_NUM)) {
            $rec = AP5L_Text_L10n_Hint::factory($rec[0], $rec[1]);
        }
        return $rec;
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
        $sql = 'SELECT * FROM ' . $this -> _prefix . 'message_hint';
        if ($filter) {
            $sql .= ' WHERE messageCode LIKE :p1';
        }
        $sql .= ' ORDER BY messageCode';
        $stmt = $this -> _dbc -> prepare($sql);
        if ($filter) {
            $stmt -> bindValue('p1', $filter, PDO::PARAM_STR);
        }
        $recs = $stmt -> fetchAll(PDO::FETCH_NUM);
        $results = array();
        foreach ($recs as $rec) {
            $results[] = AP5L_Text_L10n_Hint::factory($rec[0], $rec[1]);
        }
        return $results;
    }

    /**
     * Get a list of all defined message codes.
     * 
     * @return array Unique message codes.
     */
    function getMessageCodeList() {
        $sql = 'SELECT DISTINCT messageCode FROM ' . $this -> _prefix . 'message'
            . ' ORDER BY messageCode';
        $result = $this -> _dbc -> query($sql);
        return $result;
    }
    
    /**
     * Get a list of all defined message codes and languages.
     * 
     * @return array Unique message codes.
     */
    function getMessageLanguageList() {
        $sql = 'SELECT messageCode, language FROM ' . $this -> _prefix . 'message'
            . ' ORDER BY messageCode, language';
        $result = $this -> _dbc -> query($sql);
        return $result;
    }
    
    /**
     * Return the data store connection state.
     * 
     * @return boolean True if the store object is connected (open). 
     */
    function isOpen() {
    }

    /**
     * Connect to the data store.
     *
     * @param string Data store name.
     * @param array Connection options. Options include "prefix", a table name
     * prefix.
     */
    function open($options = array()) {
        $this -> _dbc = new PDO($this -> _dsn);
        $this -> _dbc -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this -> _prefix = isset($options['prefix']) ? $options['prefix'] : '';
    }

    /**
     * Read a block of records that match a set of keys.
     *
     * @param array Language specifiers.
     * @param array List of message selectors.
     * @return array Matching messages.
     */
    function readBlock($language, $selectors) {
        /*
         * Break down each selector into include/exclude and short/full lists.
         */
        $this -> _blockData = array();
        $this -> _blockSel = array(
            AP5L_Text_L10n_Hint::TYPE_NEVER => '',
            AP5L_Text_L10n_Hint::TYPE_NORMAL => '',
            AP5L_Text_L10n_Hint::TYPE_SHORT => '',
        );
        $this -> _blockGlue = $this -> _blockSel;
        foreach ($selectors as $select) {
            $this -> _addBlockSelect($select);
        }
        $where = $this -> _blockSel[AP5L_Text_L10n_Hint::TYPE_NORMAL];
        if (! empty($this -> _blockData[AP5L_Text_L10n_Hint::TYPE_SHORT])) {
            $where .= ' OR (longMessage IS NULL AND ('
                . $this -> _blockSel[AP5L_Text_L10n_Hint::TYPE_SHORT] . '))';
        }
        // Trap the degenerate empty set
        if ($where == '') {
            return array();
        }
        if (! empty($this -> _blockData[AP5L_Text_L10n_Hint::TYPE_NEVER])) {
            $where = '(' . $where . ')'
                .' AND NOT (' . $this -> _blockSel[AP5L_Text_L10n_Hint::TYPE_NEVER] . ')';
        }
        $lTerms = array();
        foreach ($language as $lSel) {
            $breakdown = explode('-', $lSel);
            while (! empty($breakdown)) {
                $slot = ':p' . count($this -> _blockData);
                $lTerms[] = $slot;
                $this -> _blockData[$slot] = implode('-', $breakdown);
                array_pop($breakdown);
            }
        }
        $where .= ' AND language IN (' . implode(',', $lTerms) . ')';
        $sql = 'SELECT DISTINCT messageCode, message, longMessage'
            . ' FROM ' . $this -> _prefix . 'message'
            . ' WHERE ' . $where
            . ' ORDER BY messageCode, language DESC'
            ;
        $stmt = $this -> _dbc -> prepare($sql);
        $result = $stmt -> execute($this -> _blockData);
        // process, set isLong, message size, return
    }

    /**
     * Remove a hint.
     * 
     * @param string Message code of the hint to be removed.
     */
    function removeHint($messageCode) {
        $sql = 'DELETE FROM ' . $this -> _prefix . 'message_hint'
            . ' WHERE messageCode=:p1';
        $stmt = $this -> _dbc -> prepare($sql);
        $stmt -> bindValue('p1', $messageCode, PDO::PARAM_STR);
        $this -> _dbc -> execute($sql);
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
        $sql = 'DELETE FROM ' . $this -> _prefix . 'message'
            . ' WHERE messageCode LIKE :p1';
        if ($language != '') {
            $sql .= ' AND language like :p2';
        }
        $stmt = $this -> _dbc -> prepare($sql);
        $stmt -> bindValue('p1', $messageCode, PDO::PARAM_STR);
        if ($language != '') {
            $stmt -> bindValue('p2', $language, PDO::PARAM_STR);
        }
        $this -> _dbc -> execute($sql);
    }

    /**
     * Set the data store name.
     * 
     * @param string Data store name.
     */
    function setDsn($dsn) {
        $this -> _dsn = $dsn;
    }

}
