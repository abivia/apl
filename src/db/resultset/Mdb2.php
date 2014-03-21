<?php
/**
 * AP5L: Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Mdb2.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Result sets for MDB2 databases
 *
 * The intention of this class is to provide facilities for both traditional
 * relational databases (flat tables) and for object based data stores that deal
 * with hierarchically structured results.
 * 
 * @package AP5L
 * @subpackage Db
 */
class AP5L_Db_ResultSet_Mdb2 extends AP5L_Db_ResultSet {
    /**
     * Map valid modes into native modes.
     *
     * @var array
     */
    static protected $_modeMap = array(
        AP5L_Db_ResultSet::MODE_DEFAULT => MDB2_FETCHMODE_DEFAULT,
        AP5L_Db_ResultSet::MODE_ORDINAL => MDB2_FETCHMODE_ORDERED,
        AP5L_Db_ResultSet::MODE_ASSOC => MDB2_FETCHMODE_ASSOC,
        AP5L_Db_ResultSet::MODE_OBJECT => MDB2_FETCHMODE_OBJECT,
    );

    /**
     * The "native" result set.
     *
     * @var MDB2_Result
     */
    protected $_resultSet;

    function __construct(&$resultSet) {
        $this -> _resultSet = $resultSet;
    }

    /**
     * Extract a mode specification and map it to a native value.
     *
     * @param array The current options.
     * @return int The native mode
     * @throws AP5L_Db_Exception If the mode is not mapped.
     */
    protected function _getMode($options) {
        $mode = isset($options['mode'])
            ? $options['mode'] : AP5L_Db_ResultSet::MODE_DEFAULT;
        if (! isset(self::$_modeMap[$mode])) {
            throw new AP5L_Db_Exception('Unable to map mode ' . $mode);
        }
        return self::$_modeMap[$mode];
    }

    /**
     * Return everything in the result set.
     *
     * @param array Options. [mode] a fetch mode constant.
     * @return mixed An array or object set representing the query results.
     */
    function fetchAll($options = array()) {
        $mode = $this -> _getMode($options);
        $result = $this -> _resultSet -> fetchAll($mode);
        if ($result instanceof PEAR_Error) {
            throw AP5L_Db_Exception::fromPear($result);
        }
        return $result;
    }

    /**
     * Return the next member of the result set.
     *
     * @param array Options. [mode] a fetch mode constant.
     * @return mixed An array or object set representing the next element in the
     * result set.
     */
    function fetchNext($options = array()) {
        $mode = $this -> _getMode($options);
        $result = $this -> _resultSet -> fetchRow($mode);
        if ($result instanceof PEAR_Error) {
            throw AP5L_Db_Exception::fromPear($result);
        }
        return $result;
    }

    /**
     * Return a single element from the result set.
     *
     * @param array Options. [column] a column number (integer) or name
     * (string). Defaults to 0. [row] a row number (integer) optional.
     * @return mixed An array or object set representing the first element in
     * the result set.
     */
    function fetchOne($options = array()) {
        $result = $this -> _resultSet -> fetchOne(
            isset($options['col']) ? $options['col'] : 0,
            isset($options['row']) ? $options['row'] : null
        );
        if ($result instanceof PEAR_Error) {
            throw AP5L_Db_Exception::fromPear($result);
        }
        return $result;
    }

    /**
     * Return a slice of the result set.
     *
     * In relational data stores, a slice is a column. In hierarchical stores,
     * it is (probably) an axis.
     *
     * @param mixed A specification of the requested slice, e.g. column
     * identifier.
     * @param array Options. Implementation specific.
     * @return mixed An array, object, or result set representing the slice.
     */
    function fetchSlice($slice = null, $options = array()) {
        $result = $this -> _resultSet -> fetchCol($slice);
        if ($result instanceof PEAR_Error) {
            throw AP5L_Db_Exception::fromPear($result);
        }
        return $result;
    }

    /**
     * Free internal resources.
     */
    function free() {
        if ($this -> _resultSet) {
             $this -> _resultSet -> free();
             $this -> _resultSet = false;
        }
    }

}
