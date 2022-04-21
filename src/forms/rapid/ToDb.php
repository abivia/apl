<?php
/**
 * Rapid form with resuts saved in database.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ToDb.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 *
 * @todo Refactor to AP5L naming conventions
 * @todo Consider moving to a data store model.
 * @todo complete phpdocs
 */

/**
 * AP5L_Forms_Rapid object that saves results in a database.
 *
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Rapid_ToDb extends AP5L_Forms_Rapid {
    /**
     * Column type information; indexed by colname, or '*' for default.
     *
     * @var array
     */
    private $_dataTypes = array('*' => 'text');
    /**
     * Optional mapping of form fields to table columns; map[field] = column.
     *
     * @var array
     */
    private $_fieldMap = array();
    /**
     * Database connection to use.
     *
     * @var object
     */
    protected $dbc;
    /**
     * Values to be inserted into the database, formatted for insert.
     *
     * @var array
     */
    protected $dbRecord = array();
    /**
     * Name of the table where results are stored.
     *
     * @var string
     */
    protected $table;

    function onDone() {
        return '';
    }

    function onError() {
        if ($this -> _debug) {
            if ($this -> _error instanceof Exception) {
                return (string) $this -> _error;
            } elseif (PEAR::isError($this -> _error)) {
                return $this -> _error -> toString();
            }
            return print_r($this -> _error, true);
        }
        return parent::onError();
    }

    /**
     * Get an array of columns for the insert operation. Override this to add
     * application specific data (for example, a timestamp). Save the
     * information in dbRecord as quoted, escaped column values, indexed by
     * column name.
     *
     * @return boolean True on successful formatting
     * @throws AP5L_Forms_Exception MathException thrown if an error occurs; this is
     * most likely a database connection issue.
     */
    function onPrepareData() {
        // Clear any previous results
        $this -> dbRecord = array();
        // Get all persistent fields
        $stuff = $this -> getResults();
        $cols = array_keys($stuff);
        $vals = array_values($stuff);
        // Map fields to column names
        foreach ($cols as $key => $fieldName) {
            if (isset($this -> _fieldMap[$fieldName])) {
                $cols[$key] = $this -> _fieldMap[$fieldName];
            }
        }
        // Map columns to data types
        foreach ($vals as $key => $fieldValue) {
            if (isset($this -> _dataTypes[$cols[$key]])) {
                $vals[$key] = $this -> quote(
                    $fieldValue, $this -> _dataTypes[$cols[$key]]);
            } elseif (isset($this -> _dataTypes['*'])) {
                $vals[$key] = $this -> quote(
                    $fieldValue, $this -> _dataTypes['*']);
            }
        }
        // Save the values, keyed by column
        $this -> dbRecord = array_combine($cols, $vals);
        return true;
    }

    function onSave() {
        //
        // Create an insert query from the record information
        //
        if (! count($this -> dbRecord)) return true;
        $sql = 'INSERT INTO ' . $this -> table . ' ('
            . implode(',', array_keys($this -> dbRecord)) . ') VALUES ('
            . implode(',', $this -> dbRecord) . ')';
        $result = $this -> dbc -> query($sql);
        $this -> throwOnError($result, 'DB error');
        return true;
    }

    function throwOnError($result, $msg = 'PEAR Error') {
        if (PEAR::isError($result)) {
            throw new AP5L_Forms_Exception($msg, 0, $result);
        }
    }

    function quote($value, $type) {
        $result = $this -> dbc -> quote($value, $type);
        if (PEAR::isError($result)) {
            throw new AP5L_Forms_Exception('Unable to save data', 0, $result);
        }
        return $result;
    }

    function setDb(&$dbc) {
        $this -> dbc = $dbc;
    }

    function setDataType($columnName, $dataType) {
        $this -> _dataTypes[$columnName] = $dataType;
    }

    function setColumn($fieldName, $columnName, $dataType = '') {
        $this -> _fieldMap[$fieldName] = $columnName;
        if ($dataType) {
            $this -> setDataType($columnName, $dataType);
        }
    }

    function setTable($tableName) {
        $this -> table = $tableName;
    }

    function sqlInsert($table, $cols) {
        //
        // Create an insert query from the column information
        //
        if (! count($cols)) return '';
        $sql = 'INSERT INTO ' . $table . ' ('
            . implode(',', array_keys($cols)) . ') VALUES ('
            . implode(',', $cols) . ')';
        return $sql;
    }

    function sqlReplace($table, $cols) {
        //
        // Create a replace query from the column information
        //
        if (! count($cols)) return '';
        $sql = 'REPLACE INTO ' . $table . ' ('
            . implode(',', array_keys($cols)) . ') VALUES ('
            . implode(',', $cols) . ')';
        return $sql;
    }

    function sqlUpdate($table, $cols) {
        //
        // Create an update query from the column information
        //
        if (! count($cols)) return '';
        $sql = 'UPDATE ' . $table;
        $delim = ' SET ';
        foreach ($cols as $col => $value) {
            $sql .= $delim . $cols . '=' . $value;
            $delim = ',';
        }
        return $sql;
    }

    function sqlWhere($criteria, &$cols, $removeCols = true) {
        //
        // Create a where clause from the specified criteria columns
        //
        $sql = '';
        $delim = ' WHERE ';
        foreach ($criteria as $col) {
            $sql .= $delim . $col . '=' . $cols[$col];
            $delim = ' AND ';
            if ($removeCols) {
                unset($cols[$col]);
            }
        }
        return $sql;
    }

}
