<?php
/**
 * Data store for MDB2 database interface.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Mdb2.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * ACL Storage manager for PEAR MDB2.
 *
 * The current implementation pretty much assumes that the MDB2 connection is to
 * MySQL. Other connection types might benefit from changes to the schema
 * tables.
 *
 * @package AP5L
 * @subpackage Acl
 * @todo move method defs to AP5L_Acl_Store
 */
class AP5L_Acl_Store_Mdb2 extends AP5L_Acl_Store_Sql {
    /**
     * Database connection.
     *
     * @var MDB2_Driver_*
     */
    protected $_dbc;

    /**
     * Map data types to MDB2 types for quoting.
     *
     * @var array
     */
    static protected $_quoteTypeMap = array(
        'auto' => 'integer',
        'string' => 'text',
    );

    /**
     * Create a table and indicies
     *
     * @param string The base table name (without any prefix).
     * @param array Table definition from the $_schema table.
     * @throws AP5L_Db_Exception On any database error.
     */
    protected function _createTable($baseTable, $tableDef, $options = array()) {
        $tableName = $this -> _prefix . $baseTable;
        $mdbCols = array();
        $hasAuto = false;
        foreach ($tableDef['cols'] as $colName => $col) {
            $mdbCol = array('type' => $col['type']);
            $mdbCol['length'] = empty($col['size']) ? false : $col['size'];
            switch ($col['type']) {
                case 'int': {
                    $mdbCol['type'] = 'integer';
                    $mdbCol['length'] = 4;
                    $mdbCol['unsigned'] = 1;
                }
                break;

                case 'varchar': {
                    $mdbCol['type'] = 'text';
                }
                break;
            }
            $mdbCol['notnull'] = isset($col['null']) ? ! $col['null'] : true;
            if (isset($col['auto'])) {
                $mdbCol['autoincrement'] = true;
                $hasAuto = true;
            }
            if (isset($col['comment'])) {
                $mdbCol['comment'] = $col['comment'];
            }
            $mdbCols[$colName] = $mdbCol;
        }
        $mdbOpts = array();
        $mdbOpts['charset'] = isset($options['charset']) ? $options['charset'] : 'utf8';
        $mdbOpts['collate'] = isset($options['collate']) ? $options['collate'] : '';
        if (isset($tableDef['comment'])) {
            $mdbOpts['comment'] = $tableDef['comment'];
        }
        $result = $this -> _dbc -> manager -> createTable(
            $tableName, $mdbCols, $mdbOpts
        );
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        /*
         * MDB2's create index is PATHETIC; we do it ourselves.
         */
        foreach ($tableDef['keys'] as $keyName => $key) {
            if ($keyName == 'PRIMARY') {
                if ($hasAuto) {
                    continue;
                }
                $sql = 'PRIMARY KEY';
            } elseif ($keyName[0] == '!') {
                $sql = 'UNIQUE INDEX ' . substr($keyName, 1);
            } else {
                $sql = 'INDEX ' . $keyName;
            }
            $sql = 'CREATE ' . $sql . ' ON ' . $tableName;
            $glue = ' (';
            foreach ($key as $keyCol) {
                if (is_array($keyCol)) {
                    $col = $this -> _dbc -> quoteIdentifier($keyCol[0], true)
                        . $keyCol[1] >= 0 ? ' ASC' : ' DESC';
                } else {
                    $col = $this -> _dbc -> quoteIdentifier($keyCol, true);
                }
                $sql .= $glue . $col;
                $glue = ',';
            }
            $sql .= ')';
            $this -> _dbc -> exec($sql);
        }
    }

    /**
     * Install set-up: get the schema management functions.
     */
    protected function _onBeforeInstall() {
        $this-> _dbc -> loadModule('Manager', null, true);
    }

    /*
     * Wrap and transform data types for a database query.
     *
     * @param mixed The value to be encoded/quoted.
     * @param string The data type. Any MDB2 data type or one of <ul><li>flag --
     * a boolean value converted to a one character string.
     * </li><li>json -- JSON encode an object as a string.
     * </li><li>tick_auto -- The current UNIX timestamp, in seconds.
     * </li><li>tick_to_timestamp -- Convert a UNIX timestamp to a date
     * string (MySQL format).
     * </li><li>utick_auto -- The current UNIX timestamp with microseconds.
     * </li></ul>
     */
    protected function _quote($value, $type) {
        switch ($type) {
            case 'flag': {
                $value = $value ? 'T' : 'F';
                $type = 'text';
            }
            break;

            case 'json': {
                $value = json_encode($value);
                $type = 'text';
            }
            break;

            case 'tick_auto': {
                $value = time();
                $type = 'integer';
            }
            break;

            case 'tick_to_timestamp': {
                if (is_numeric($value)) {
                    $value = gmdate('Y-m-d H:i:s', $value);
                }
                $type = 'text';
            }
            break;

            case 'utick_auto': {
                $work = microtime();
                $bpos = strpos($work, ' ');
                $value = str_pad(substr($work, $bpos + 1), 10, '0', STR_PAD_LEFT)
                    . str_pad(substr($work, 1, $bpos - 1), 9, '0');
                $type = 'text';
            }
            break;

            default: {
                if (isset(self::$_quoteTypeMap[$type])) {
                    $type = self::$_quoteTypeMap[$type];
                }
            }
            break;

        }
        return $this -> _dbc -> quote($value, $type);
    }

    /**
     * Remove asset records that have a parent that has been removed from the
     * data store.
     *
     * @throws AP5L_Db_Exception On any database error.
     */
    protected function _removeAssetOrphans() {
        $orphans = 'SELECT count(*) FROM '
            . $this -> _prefix . 'asset AS a1 LEFT JOIN '
            . $this -> _prefix . 'asset AS a2'
            . ' ON a1.parentID=a2.assetID WHERE a2.assetID IS NULL';
        $sql = 'DELETE FROM ' . $this -> _prefix . 'asset AS'
            . ' USING ' . $this -> _prefix . 'asset'
            . ' LEFT JOIN ' . $this -> _prefix . 'asset AS a2'
            . ' ON ' . $this -> _prefix . 'asset.parentID=a2.assetID'
            . ' WHERE a2.assetID IS NULL';
        while (true) {
            $result = $this -> _dbc -> queryOne($orphans);
            if (PEAR::isError($result)) {
                throw new AP5L_Db_Exception($result -> toString());
            }
            if (! $result) break;
            $result = $this -> _dbc -> query($sql);
            if (PEAR::isError($result)) {
                throw new AP5L_Db_Exception($result -> toString());
            }
        }
    }

    /**
     * Remove permission records that are associated with an asset or requester
     * that has been removed from the data store.
     *
     * @throws AP5L_Db_Exception On any database error.
     */
    protected function _removePermissionOrphans() {
        // Protect domainID==0 to retain setup information
        $sql = 'DELETE FROM ' . $this -> _prefix . 'permission'
            . ' USING ' . $this -> _prefix . 'permission'
            . ' LEFT JOIN ' . $this -> _prefix . 'asset AS a'
            . ' ON ' . $this -> _prefix . 'permission.assetID=a.assetID'
            . ' WHERE a.assetID IS NULL'
            . ' AND ' . $this -> _prefix . 'permission.domainID!=0';
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        // Protect domainID==0 to retain setup information
        $sql = 'DELETE FROM ' . $this -> _prefix . 'permission'
            . ' USING ' . $this -> _prefix . 'permission'
            . ' LEFT JOIN ' . $this -> _prefix . 'requester AS r'
            . ' ON ' . $this -> _prefix . 'permission.assetID=r.requesterID'
            . ' WHERE r.requesterID IS NULL'
            . ' AND ' . $this -> _prefix . 'permission.domainID!=0';
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
    }

    /**
     * Convert values in a data row into corresponding PHP types.
     *
     * This function handles two conversions: flags as strings into booleans,
     * and JSON objects into PHP equivalents.
     *
     * @param array The database row, an array of values indexed by column.
     * @param array Data types to be converted, indexed by column. Only columns
     * that need to be cast need be present.
     */
    static protected function _rowCast(&$row, $types) {
        foreach ($row as $col => &$value) {
            if (isset($types[$col])) {
                switch ($types[$col]) {
                    case 'flag': {
                        $value = ($value == 'T');
                    }
                    break;

                    case 'json': {
                        $value = json_decode($value, true);
                    }
                    break;

                }
            }
        }
    }

    /**
     * Apply a list of schema changes to the database.
     *
     * @param array List of change instructions.
     */
    protected function _schemaChange($list) {
        // TODO: list needs to be defined in the SQL store
    }

    /**
     * Record the schema version in the database
     *
     * @param string Version identifier
     * @throws AP5L_Db_Exception On any database error.
     */
    protected function _setSchemaVersion($version) {
        $sql = 'REPLACE ' . $this -> _prefix . 'permission'
            . ' SET domainID=0, assetID=0, requesterID=0, permissionDefID=0'
            . ', permissionValue=' . $this ->_quote($version, 'text')
            . ',info=\'Schema Version\''
            ;
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
    }

    /**
     * Table truncation.
     *
     * @param string The name of the table to be emptied.
     * @throws AP5L_Db_Exception On any database error.
     */
    protected function _truncate($table) {
        $result = $this -> _dbc -> query('TRUNCATE ' . $this -> _prefix . $table);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
    }

    /**
     * Merge two assets into one.
     *
     * Any permissions in the source asset that exist in the destination will be
     * lost.
     *
     * @param AP5L_Acl_Asset The source asset. This asset will be merged into
     * the target and then deleted.
     * @param AP5L_Acl_Asset The destination asset. This asset will receive
     * permissions from the source.
     * @throws AP5L_Db_Exception On any database error.
     */
    function assetMerge($fromAsset, $toAsset) {
        $className = get_class($fromAsset);
        if (! isset($this -> _classToTable[$className])) {
            throw new AP5L_Db_Exception('Can\'t handle class name ' . $className . '.');
        }
        $assetKey = self::$_primaryKey[$className][0];
        /*
         * Eliminate any duplicates in the target asset.
         */
        $permissionKeys = self::$_primaryKey['AP5L_Acl_Permission'];
        $join = '';
        $glue = ' ON ';
        foreach ($permissionKeys as $key) {
            if ($key != $assetKey) {
                $join .= $glue . 'p1.' . $key . '=p2.' . $key;
                $glue = ' AND ';
            }
        }
        $sql = 'DELETE FROM ' . $this -> _prefix . 'permission'
            . ' USING ' . $this -> _prefix . 'permission'
            . ' INNER JOIN ' . $this -> _prefix . 'permission AS p2'
            . $join
            . ' WHERE ' . $this -> _prefix . 'permission.' . $assetKey
            . '=' . $toAsset -> getID()
            . ' AND p2.' . $assetKey . '=' . $fromAsset -> getID()
            ;
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        /*
         * Update the remaining records. We don't need the join fields because
         * the assetID implies matching domains and sections.
         */
        $sql = 'UPDATE ' . $this -> _prefix . 'permission'
            . ' SET ' . $assetKey . '=' . $toAsset -> getID()
            . ' WHERE ' . $assetKey . '=' . $fromAsset -> getID()
            ;
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        $this -> delete($fromAsset);
    }

    /**
     * Delete one or more objects.
     *
     * If an object is passed as the first parameter, then it should be used to
     * provide a primary key for the deletion. If a class name is passed, then
     * the criteria qualify the set of objects to be deleted.
     *
     * @param object|string Either the instance of the object to be deleted or
     * the name of the object's class.
     * @param AP5L_Db_Expr|array|string Either an expression object, an array of
     * key value pairs, or a criteria string. An array is considered to be a
     * list of equalities that need to be satisfied. A string must be handled by
     * the implementation.
     * @param array Any options. Options are implementation specific.
     * @throws AP5L_Db_Exception On error (for example, if the object class
     * cannot be handled by this store).
     */
    function delete($object, $criteria = null, $options = array()) {
        if (is_object($object)) {
            $className = get_class($object);
            switch ($className) {
                case 'AP5L_Acl_Asset': {
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'asset AS a'
                        . ', ' . $this -> _prefix . 'permission AS p'
                        . ' WHERE a.assetID=' . $object -> getID()
                        . ' AND s.assetID=p.assetID';
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $this -> _removeAssetOrphans();
                    return;
                }
                break;

                case 'AP5L_Acl_AssetSection': {
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'assetsection'
                        . ' WHERE assetSectionID=' . $object -> getID();
                   $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'asset'
                        . ' WHERE assetSectionID=' . $object -> getID();
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $this -> _removeAssetOrphans();
                    $this -> _removePermissionOrphans();
                    return;
                }
                break;

                case 'AP5L_Acl_Domain': {
                    $id = $object -> getID();
                    $sql = 'DELETE FROM ' . implode(',', $this -> classToTable);
                    $glue = ' WHERE ';
                    foreach ($this -> classToTable as $table) {
                        $sql .= $glue . $table . '.domainID=' . $id;
                        $glue = ' OR ';
                    }
                }
                break;

                case 'AP5L_Acl_Permission': {
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'permission'
                        . ' WHERE domainID=' . $this -> _domainID
                        . ' AND assetID=' . $this -> _quote($object -> getAssetID(), 'text')
                        . ' AND requesterID=' . $this -> _quote($object -> getRequesterID(), 'text')
                        . ' AND permissionDefID=' . $this -> _quote($object -> getDefinitionID(), 'text')
                        ;
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    return;
                }
                break;

                case 'AP5L_Acl_PermissionDefinition': {
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'permission'
                        . ' WHERE permissionDefID=' . $object -> getID()
                        ;
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'permissiondef'
                        . ' WHERE permissionDefID=' . $object -> getID()
                        ;
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    return;
                }
                break;

                case 'AP5L_Acl_PermissionSection': {
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'permissionsection'
                        . ' WHERE permissionSectionID=' . $object -> getID();
                   $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'permissiondef'
                        . ' WHERE permissionSectionID=' . $object -> getID();
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $this -> _removePermissionOrphans();
                    return;
                }
                break;

                case 'AP5L_Acl_Requester': {
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'requester AS r'
                         .', ' . $this -> _prefix . 'permission AS p'
                        . ' WHERE r.requesterID=' . $object -> getID()
                        . ' AND s.requesterID=p.requesterID';
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    return;
                }
                break;

                case 'AP5L_Acl_RequesterSection': {
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'requestersection'
                        . ' WHERE requesterSectionID=' . $object -> getID();
                   $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $sql = 'DELETE FROM ' . $this -> _prefix . 'requester'
                        . ' WHERE requesterSectionID=' . $object -> getID();
                    $result = $this -> _dbc -> query($sql);
                    if (PEAR::isError($result)) {
                        throw new AP5L_Db_Exception($result -> toString());
                    }
                    $this -> _removePermissionOrphans();
                    return;
                }
                break;

            }
        } elseif (is_string($object)) {
            // Not really sure what makes sense in this case, so...
            return;
            $className = $object;
            if (! isset($this -> _classToTable[$className])) {
                throw new AP5L_Db_Exception('Can\'t handle class name ' . $className . '.');
            }
            if ($className == 'AP5L_Acl_Domain') {
                $where = '';
                $glue = ' WHERE ';
            } else {
                if (! $this -> _domainID) {
                    throw new AP5L_Acl_Exception('Must set domain first.');
                }
                $where = ' WHERE domainID=' . $this -> _domainID;
                $glue = ' AND ';
            }
            if ((is_array($criteria) || is_null($criteria))) {
                $sql = 'DELETE FROM ' . $this -> _classToTable[$className];
                $colMap = &self::$_memberToCol[$className];
                $typeMap = &self::$_colType[$className];
                if (! is_null($criteria)) {
                    foreach ($criteria as $member => $value) {
                        $where .= $glue . $colMap[$member] . '='
                            . $this -> _quote($value, $typeMap[$colMap[$member]]);
                        $glue = ' AND ';
                    }
                }
            }
        } else {
            throw new AP5L_Db_Exception('First parameter must be class name or object.');
        }
        // Run the query
        $result = $this -> _dbc -> query($sql . $where);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
    }

    /**
     * Disconnect from the database.
     */
    function disconnect() {
        if ($this -> _dbc) {
            $this -> _dbc -> disconnect();
        }
    }

    /**
     * Create a database connection for the specified DSN and configure the
     * connection.
     *
     * @param string A MDB2 style DSN.
     * @return AP5L_Acl_Store_Mdb2 The new data store object.
     * @throws AP5L_Db_Exception If the connection fails.
     */
    static function &factory($dsn) {
        $dbc = &MDB2::singleton($dsn);
        if (PEAR::isError($dsn)) {
            throw new AP5L_Db_Exception(
                'Unable to connect with ' . $dsn . ': ' . $dbc -> toString()
            );
        }
        $dbc -> setOption('portability',
            MDB2_PORTABILITY_ALL
            - MDB2_PORTABILITY_EMPTY_TO_NULL
            - MDB2_PORTABILITY_FIX_CASE);
        $store = new AP5L_Acl_Store_Mdb2();
        $store -> setConnection($dbc);
        return $store;
    }

    /**
     * Retrieve one or more objects.
     *
     * If an object is passed as the first parameter, then it should be used to
     * provide a primary key for the fetch. If a class name is passed, then the
     * criteria qualify the set of objects to be selected.
     *
     * @param object|string Either the instance of the objects to be retrieved
     * (by default by primary key values) or the name of the object's class.
     * @param AP5L_DB_Expr|array|string Either an expression object, an array of
     * key value pairs, or a criteria string. An array is considered to be a
     * list of equalities that need to be satisfied. A string must be handled by
     * the implementation.
     * @param array Options: [first]=boolean: return the first matching object.
     * Other options are implementation specific.
     * @return object|array|boolean Returns either an array of objects, or if
     * the "first" option is set, a single object if a match is found or false
     * if not.
     * @throws AP5L_Db_Exception On any failure.
     */
    function &get($object, $criteria = null, $options = array()) {
        if (is_object($object)) {
            $className = get_class($object);
        } elseif (is_string($object)) {
            $className = $object;
            if (! isset($this -> _classToTable[$className])) {
                throw new AP5L_Db_Exception('Can\'t handle class name ' . $className . '.');
            }
        } else {
            throw new AP5L_Db_Exception('First parameter must be class name or object.');
        }
        /*
         * Build the WHERE clause
         */
        $tableName = $this -> _classToTable[$className];
        if ($className == 'AP5L_Acl_Domain') {
            $where = '';
            $glue = ' WHERE ';
        } else {
            if (! $this -> _domainID) {
                throw new AP5L_Acl_Exception('Must set domain first.');
            }
            $where = ' WHERE domainID=' . $this -> _domainID;
            $glue = ' AND ';
        }
        if ((is_array($criteria) || is_null($criteria))) {
            $sql = 'SELECT * FROM ' . $tableName;
            $colMap = &self::$_memberToCol[$className];
            $typeMap = &self::$_colType[$className];
            if (! is_null($criteria)) {
                foreach ($criteria as $member => $value) {
                    $where .= $glue . $colMap[$member] . '='
                        . $this -> _quote($value, $typeMap[$colMap[$member]]);
                    $glue = ' AND ';
                }
            }
        }
        /*
         * See if we have an ordering requirement
         */
        $order = '';
        if (isset($options['order'])) {
            $glue = ' ORDER BY ';
            foreach ($options['order'] as $orderExpr) {
                $orderCol = explode(' ', $orderExpr);
                $orderCol[0] = $colMap[$orderCol[0]];
                $order .= $glue . implode(' ', $orderCol);
                $glue = ', ';
            }
        }
        // Run the query
        //echo $sql . $where . $order . AP5L::LF;
        $result = $this -> _dbc -> query($sql . $where . $order);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        if (! $row = $result -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
            return false;
        }
        $memberMap = array();
        $c2m = &self::$_colToMember[$className];
        foreach (array_keys($row) as $col) {
            $memberMap[$col] = $c2m[$col];
        }
        $results = array();
        do {
            $this -> _rowCast($row, $typeMap);
            $obj = &call_user_func(array($className, 'factory'));
            $obj -> setStore($this);
            $obj -> load($memberMap, $row);
            if (isset($options['first']) && $options['first']) {
                return $obj;
            }
            $results[] = $obj;
        } while ($row = $result -> fetchRow(MDB2_FETCHMODE_ASSOC));
        return $results;
    }

    /**
     * Get the parent path of a tree-structured object.
     *
     * Some data stores will be able to perform this in a single query.
     *
     * @param AP5L_Acl_Tree|string An instance of the object class to be
     * traced, or a class name.
     * @param int The ID of the starting object. Optional if the first parameter
     * is an object.
     * @param array Options.
     * @return array Returns an array of parent identifiers.
     * @throws AP5L_Db_Exception On any failure.
     */
    function &getParentPath($object, $startID = false, $options = array()) {
        if (! $this -> _domainID) {
            throw new AP5L_Acl_Exception('Must set domain first.');
        }
        if (is_object($object)) {
            $className = get_class($object);
            if ($startID === false) {
                $walkID = $object -> getID();
            } else {
                $walkID = $startID;
            }
        } else {
            $className = $object;
            $walkID = $startID;
        }
        if (! isset(self::$_parentCol[$className])) {
            throw new AP5L_Acl_Exception('Class has no parent relationships.');
        }
        $parents = array();
        if (! $walkID) {
            return;
        }
        $sql = 'SELECT ' . self::$_parentCol[$className]
            . ' FROM ' . $this -> _classToTable[$className];
        /*
         * Follow the parent links to build the path
         */
        do {
            $where = ' WHERE ' . self::$_primaryKey[$className][0] . '=' . $walkID;
            //echo $sql . $where . AP5L::LF;
            $walkID = $this -> _dbc -> queryOne($sql . $where);
            if (PEAR::isError($walkID)) {
                throw new AP5L_Db_Exception($walkID -> toString());
            }
            array_unshift($parents, $walkID);
        } while ($walkID);
        return $parents;
    }

    /**
     * Get the permissions that intersect asset/requester paths.
     *
     * @param array A list of integer asset IDs in the path.
     * @param array A list of integer requester IDs in the path.
     * @param int An optional permission ID. If not provided, all permissions
     * are returned.
     * @return array All matching AP5L_Acl_Permission objects.
     * @throws AP5L_Db_Exception On any failure.
     */
    function &getPermissions($assets, $requesters, $permission = 0) {
        $colMap = &self::$_memberToCol['AP5L_Acl_Permission'];
        $apk = self::$_primaryKey['AP5L_Acl_Asset'][0];
        $rpk = self::$_primaryKey['AP5L_Acl_Requester'][0];
        $sql = 'SELECT *'
            . ' FROM ' . $this -> _classToTable['AP5L_Acl_Permission']
            . ' WHERE isEnabled=\'T\''
            . ' AND ' . $apk . ' IN (' . implode(',', $assets) . ')'
            . ' AND ' . $rpk . ' IN (' . implode(',', $requesters) . ')'
            ;
        if ($permission) {
            $pdpk = self::$_primaryKey['AP5L_Acl_PermissionDefinition'][0];
            $sql .= ' AND ' . $pdpk . '=' . $permission;
        }
        //echo $sql . AP5L::LF;
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        $typeMap = &self::$_colType['AP5L_Acl_Permission'];
        $first = true;
        $results = array();
        while ($row = $result -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
            if ($first) {
                /*
                 * Build a specific member mapping for this query
                 */
                $memberMap = array();
                $c2m = &self::$_colToMember['AP5L_Acl_Permission'];
                foreach (array_keys($row) as $col) {
                    $memberMap[$col] = $c2m[$col];
                }
                $first = false;
            }
            $this -> _rowCast($row, $typeMap);
            $obj = AP5L_Acl_Permission::factory();
            $obj -> setStore($this);
            $obj -> load($memberMap, $row);
            $results[] = $obj;
        }
        return $results;
    }

    /**
     * Get the schema version from the database.
     *
     * @return string|boolean Version number if any, false if none defined.
     */
    function getSchemaVersion() {
        $sql = 'SELECT permissionValue FROM '. $this -> _prefix . 'permission'
            . ' WHERE domainID=0 AND assetID=0 AND requesterID=0 AND permissionDefID=0'
            ;
        $result = $this -> _dbc -> queryOne($sql);
        if (PEAR::isError($result)) {
            return false;
        }
        return $result;
    }

    /**
     * Get a list of permissions with names.
     *
     * @param AP5L_Db_Expr|array An array of key value pairs, must include asset
     * ID, requester ID, and may include other criteria including permission
     * section. (DB expressions for future).
     * @param array Options.
     * @return array|boolean Returns either an array permission values indexed
     * by name, or false if no permissions match.
     * @throws AP5L_Db_Exception On any failure.
     */
    function &permissionList($criteria, $options = array()) {
        /*
         * Build the WHERE clause
         */
        if (! $this -> _domainID) {
            throw new AP5L_Acl_Exception('Must set domain first.');
        }
        if (! isset($criteria['_assetID']) || ! isset($criteria['_requesterID'])) {
            throw new AP5L_Acl_Exception('Must provide asset and requester.');
        }
        $order = '';
        $orderGlue = ' ORDER BY ';
        $where = ' WHERE ' . $this -> _prefix . 'permission.domainID'
            . '=' . $this -> _domainID;
        $glue = ' AND ';
        $sql = 'SELECT ' . $this -> _prefix . 'permissionsection.permissionSectionName'
            . ', ' . $this -> _prefix . 'permissiondef.permissionName'
            . ', ' . $this -> _prefix . 'permission.permissionValue FROM'
            . ' ' . $this -> _prefix . 'permission'
            . ' INNER JOIN ' . $this -> _prefix . 'permissiondef'
            . ' ON ' . $this -> _prefix . 'permission.permissionDefID'
            . ' = ' . $this -> _prefix . 'permissiondef.permissionDefID'
            . ' INNER JOIN ' . $this -> _prefix . 'permissionsection'
            . ' ON ' . $this -> _prefix . 'permissiondef.permissionSectionID'
            . ' = ' . $this -> _prefix . 'permissionsection.permissionSectionID'
            ;
        $classes = array(
            'AP5L_Acl_Permission',
            'AP5L_Acl_PermissionDefinition',
            'AP5L_Acl_PermissionSection'
        );
        foreach ($classes as $className) {
            $tableName = $this -> _classToTable[$className];
            $colMap = &self::$_memberToCol[$className];
            $typeMap = &self::$_colType[$className];
            foreach ($criteria as $member => $value) {
                if (isset($colMap[$member])) {
                    $where .= $glue . $tableName . '.' . $colMap[$member] . '='
                        . $this -> _quote($value, $typeMap[$colMap[$member]]);
                    $glue = ' AND ';
                    unset($criteria[$member]);
                }
            }
            /*
             * See if we have an ordering requirement
             */
            if (isset($options['order'])) {
                foreach ($options['order'] as $key => $orderExpr) {
                    $orderCol = explode(' ', $orderExpr);
                    if (isset($colMap[$orderCol[0]])) {
                        $orderCol[0] = $colMap[$orderCol[0]];
                        $order .= $orderGlue . $tableName . '.' . implode(' ', $orderCol);
                        $orderGlue = ', ';
                        unset($options['order'][$key]);
                    }
                }
            }
        }
        // Run the query
        //echo $sql . $where . $order . AP5L::LF;
        $result = $this -> _dbc -> query($sql . $where . $order);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        $results = array();
        while ($row = $result -> fetchRow(MDB2_FETCHMODE_ORDERED)) {
            if (! isset($results[$row[0]])) {
                $results[$row[0]] = array();
            }
            $results[$row[0]][$row[1]] = $row[2];
        }
        return $results;
    }

    /**
     * Put an object into the data store.
     *
     * @param object An instance of the object to be saved.
     * @param array Options: {@see AP5L_Db_Store::put}.
     * @throws AP5L_Db_Exception On any failure.
     */
    function put(&$object, $options = array()) {
        $className = get_class($object);
        if (! isset($this -> _classToTable[$className])) {
            throw new AP5L_Db_Exception('Can\'t handle class name ' . $className . '.');
        }
        /*
         * The caller can force either insert or replace mode
         */
        $insertMode = false;
        $replaceMode = false;
        if (isset($options['replace'])) {
            if ($options['replace']) {
                $replaceMode = true;
            } else {
                $insertMode = true;
            }
        }
        /*
         * Request the data to be saved from the object
         */
        $colMap = &self::$_memberToCol[$className];
        $values = $object -> unload($colMap);
        /*
         * Make sure any auto increment makes sense
         */
        $typeMap = &self::$_colType[$className];
        $autoCol = array_search('auto', $typeMap);
        $getNewAuto = $autoCol != false;
        //echo 'colmap:' . print_r($colMap, true);
        //echo 'values:' . print_r($values, true);
        //echo 'types:' . print_r($typeMap, true);
        if ($autoCol && isset($values[$autoCol])) {
            if ($insertMode) {
                // Let the database provide the value
                unset($values[$autoCol]);
            } elseif ($values[$autoCol] == 0) {
                // Zero means we should get a new value
                unset($values[$autoCol]);
            } else {
                // Nonzero means we're replacing data
                $replaceMode = true;
                $getNewAuto = false;
            }
        }
        if ($replaceMode) {
            $insertMode = false;
        }
        /*
         * Validate the domainID
         */
        if ($className == 'AP5L_Acl_Domain' && $replaceMode) {
            // we're good...
        } elseif (isset($values['domainID']) && $values['domainID']) {
            if ($values['domainID'] != $this -> _domainID) {
                throw new AP5L_Acl_Exception('Object is not in current domain.');
            }
        } else {
            $values['domainID'] = $this -> _domainID;
        }
        /*
         * Generate the SQL.
         */
        if ($insertMode) {
            $sql = 'INSERT INTO ';
        } else {
            $sql = 'REPLACE ';
        }
        $sql .= $this -> _classToTable[$className];
        $sql .= ' (' . implode(',', array_keys($values)) . ') VALUES (';
        $glue = '';
        foreach ($values as $col => $value) {
            $sql .= $glue . $this -> _quote($value, $typeMap[$col]);
            $glue = ', ';
        }
        $sql .= ')';
        /*
         * Execute the query.
         */
        //echo $sql . AP5L::LF;
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
        if ($getNewAuto) {
            $object -> setID($this -> _dbc  -> lastInsertID());
        }
    }

    /**
     * Merge two sections into one.
     *
     * Both sections must be of the same type.
     *
     * @param mixed The source section. This section will be merged into the
     * target and then deleted.
     * @param mixed The destination section. This section will receive permissions
     * from the source.
     * @throws AP5L_Db_Exception On any database error.
     */
    function sectionMerge($fromSection, $toSection) {
        $className = get_class($fromSection);
        if (! isset($this -> _classToTable[$className])) {
            throw new AP5L_Db_Exception('Can\'t handle class name ' . $className . '.');
        }
        $sectionKey = self::$_primaryKey[$className][0];
        foreach ($fromSection -> getSectionMemberClasses() as $className) {
            if (! isset($this -> _classToTable[$className])) {
                throw new AP5L_Db_Exception('Can\'t handle class name ' . $className . '.');
            }
            $sql = 'UPDATE ' . $this -> _classToTable[$className]
                . ' SET ' . $sectionKey . '=' . $toSection -> getID()
                . ' WHERE ' . $sectionKey . '=' . $fromSection -> getID()
                ;
            $result = $this -> _dbc -> query($sql);
            if (PEAR::isError($result)) {
                throw new AP5L_Db_Exception($result -> toString());
            }
        }
        $this -> delete($fromSection);
    }

    /**
     * Select a set of objects for retrieval.
     *
     * If an object is passed as the first parameter, then it should be used to
     * provide a primary key for the select. If a class name is passed, then the
     * criteria qualify the set of objects to be selected.
     *
     * @param object|string Either the instance of the objects to be retrieved
     * (by primary key values) or the name of the object's class.
     * @param AP5L_Db_Expr|array|string Either an expression object, an array of
     * key value pairs, or a criteria string. An array is considered to be a
     * list of equalities that need to be satisfied. A string must be handled by
     * the implementation.
     * @param array Options are implementation specific.
     * @return AP5L_Db_RecordSet The set of selected objects.
     * @throws AP5L_Db_Exception On any failure.
     */
    function &select($object, $criteria = null, $options = array()) {
    }

    /**
     * Set a database connection.
     *
     * @param MDB2_Driver_* Database connection.
     */
    function setConnection(&$dbc) {
        $this -> _dbc = $dbc;
    }

    /**
     * Set data store level options.
     *
     * @param array List of option values, indexed by option name.
     */
    function setOptions($options) {
    }

    /**
     * Update an object in the data store.
     *
     * @param object An instance of the object to be saved.
     * @param AP5L_Db_Expr|array|string Either an expression object, an array of
     * key value pairs, or a criteria string. An array is considered to be a
     * list of equalities that need to be satisfied. A string must be handled by
     * the implementation.
     * @param array Options:
     * <ul><li>"property-list" A list of object properties to update.
     * </li><li>"property-mask" A list of object properties to not update.
     * </li></ul>
     * By default all non primary key properties are updated. If property-list
     * is suppled, then only those properties are updated. If property-mask is
     * supplied, then the properties in the mask are removed from the update. If
     * both a list and a mask are supplied, then the mask is applied to the
     * list.
     * @throws AP5L_Db_Exception On any failure.
     */
    function update($object, $criteria = null, $options = array()) {
        $className = get_class($object);
        if (! isset($this -> _classToTable[$className])) {
            throw new AP5L_Db_Exception('Can\'t handle class name ' . $className . '.');
        }
        /*
         * Determine which properties are being updated
         */
        $colMap = self::$_memberToCol[$className];
        if (isset($options['property_list']) && is_array($options['property_list'])) {
            $newMap = array();
            foreach ($options['property_list'] as $prop) {
                if (isset($colMap[$prop])) {
                    $newMap[$prop] = $colMap[$prop];
                }
            }
        }
        if (isset($options['property_mask']) && is_array($options['property_mask'])) {
            $newMap = array();
            foreach ($options['property_list'] as $prop) {
                unset($colMap[$prop]);
            }
        }
        /*
         * Build a map for primary keys and make sure they're not in the value
         * list.
         */
        $keyMap = array();
        foreach (self::$_primaryKey[$className] as $keyCol) {
            $prop = self::$_colToMember[$className][$keyCol];
            $keyMap[$prop] = $keyCol;
            unset($colMap[$prop]);
        }
        /*
         * Get data from the object
         */
        $values = $object -> unload($colMap);
        $keyValues = $object -> unload($keyMap);
        /*
         * Validate the domainID
         */
        if (isset($values['domainID']) && $values['domainID'] != $this -> _domainID) {
            throw new AP5L_Acl_Exception('Object is not in current domain.');
        }
        if (isset($keyValues['domainID']) && $keyValues['domainID'] != $this -> _domainID) {
            throw new AP5L_Acl_Exception('Object is not in current domain.');
        }
        /*
         * Generate the SQL.
         */
        $typeMap = &self::$_colType[$className];
        $sql = 'UPDATE ' . $this -> _classToTable[$className];
        $glue = ' SET ';
        foreach ($values as $col => $value) {
            $sql .= $glue . $col . '=' . $this -> _quote($value, $typeMap[$col]);
            $glue = ', ';
        }
        $glue = ' WHERE ';
        foreach ($keyValues as $col => $value) {
            $sql .= $glue . $col . '=' . $this -> _quote($value, $typeMap[$col]);
            $glue = ' AND ';
        }
        /*
         * Execute the query.
         */
        $result = $this -> _dbc -> query($sql);
        if (PEAR::isError($result)) {
            throw new AP5L_Db_Exception($result -> toString());
        }
    }

}
