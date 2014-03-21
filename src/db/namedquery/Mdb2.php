<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Mdb2.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Named queries for PEAR MDB2 connections
 * 
 * @package AP5L
 * @subpackage Db
 */
abstract class AP5L_Db_NamedQuery_Mdb2 {
    /**
     * Connection to the database.
     *
     * @var MDB2_Driver_Common
     */
    protected $_dbc;

    /**
     * Array of query range limits. Indexed by query name. Optional.
     *
     * @var array
     */
    protected $_limits = array();

    /**
     * Array of options.
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Datatypes for the parameters. Array indexed by query name. Each element
     * is an array of arrays of (parameter name, datatype). This is required
     * because the same parameter can occur multiple times in a query.
     *
     * @var array
     */
    protected $_paramTypes = array();

    /**
     * Parameterized SQL queries, indexed by name.
     *
     * @var array
     */
    protected $_queries = array();

    /**
     * Result data types, indexed by query name. Each element is an array of
     * data types. An entry is not required for each query.
     *
     * @var array
     */
    protected $_resultTypes = array();

    /**
     * Resource identifiers of prepared statements, indexed by query name.
     *
     * @var array
     */
    protected $_statements = array();

    function __construct(&$dbc, $options = array()) {
        foreach ($this -> _options as $key => $setting) {
            if (isset($options[$key])) {
                $setting = $options[$key];
            }
        }
        $this -> _dbc = $dbc;
    }

    /**
     * On-demand population of query-related tables.
     *
     * @param string The query name.
     * @return boolean True if a definition exists for this query.
     */
    abstract protected function _initializeQuery($name);

    /**
     * Perform a direct SQL query.
     */
    function &directQuery(
        string $sql, $types = null, array $options = array()
    ) {
        $result = $this -> _dbc -> query($sql, $types);
        if ($result instanceof PEAR_Error) {
            throw AP5L_Db_Exception::fromPEAR($result);
        }
        $set = &new AP5L_Db_ResultSet_Mdb2($result);
        return $set;
    }

    /**
     * Perform a named query.
     *
     * This method transparently prepares queries to optimize performance.
     *
     * @param string The name of the query.
     * @param array Optional. Associative array of parameters, keyed by name.
     * @param array Optional. Associative array of options.
     * @return AP5L_Db_ResultSet
     * @throws AP5L_ApplicationException If the query doesn't exist or the
     * parameters are wrong.
     * @throws AP5L_Db_Exception If MDB2 reports an error.
     */
    function &query(
        string $name, array $params = array(), array $options = array()
    ) {
        if (! isset($this -> _queries[$name])
            && !$this -> _initializeQuery($name)
        ) {
            throw new AP5L_ApplicationException(
                'Query "' . $name . '" is not defined.'
            );
        }
        if (! isset($this -> _statements[$name])) {
            if (isset($this -> _limits[$name])) {
                $this -> _dbc -> setLimit(
                    $this -> _limits[$name][0], $this -> _limits[$name][1]
                );
            }
            $result = $this -> _dbc -> prepare(
                $this -> _queries[$name],
                null,
                isset($this -> _resultTypes[$name])
                ? $this -> _resultTypes[$name] : null
            );
            if ($result instanceof PEAR_Error) {
                throw AP5L_Db_Exception::fromPEAR($result);
            }
            $this -> _statements[$name] = $result;
        }
        $statement = $this -> _statements[$name];
        if (isset($this -> _paramTypes[$name])) {
            $vals = array();
            $types = array();
            foreach ($this -> _paramTypes[$name] as $pdef) {
                if (! isset($params[$pdef[0]])) {
                    throw new AP5L_ApplicationException(
                        'Missing parameter "' . $pdef[0] . '"'
                        . ' in query "' . $name . '".'
                    );
                }
                $vals[] = $params[$pdef[0]];
                $types[] = $params[$pdef[1]];
            }
            $statement -> bindParamArray($vals, $types);
        }
        $result = $statement -> execute();
        if ($result instanceof PEAR_Error) {
            throw AP5L_Db_Exception::fromPEAR($result);
        }
        $set = &new AP5L_Db_ResultSet_Mdb2($result);
        return $set;
    }

    function queryOne(
        string $name,
        array $params = array(),
        int $col = 0,
        array $options = array()
    ) {
        $result = $this -> query($name, $params, $options);
        $row = $result -> fetchOne($col);
        $result -> free();
        return $row;
    }

    function queryRow(
        string $name,
        array $params = array(),
        $fetchMode = MDB2_FETCHMODE_DEFAULT,
        array $options = array()
    ) {
        $result = $this -> query($name, $params, $options);
        $row = $result -> fetchRow($fetchMode);
        $result -> free();
        return $row;
    }

}
