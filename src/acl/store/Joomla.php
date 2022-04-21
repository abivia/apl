<?php
/**
 * Abivia PHP Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl\Acl\Store;

/**
 * ACL Storage manager for "Joomla!".
 *
 * @package Apl
 * @subpackage Acl
 */
class Joomla extends Sql {

    /**
     * Create a table and indicies
     *
     * @param string The base table name (without any prefix).
     * @param array Table definition from the $_schema table.
     * @throws \Apl\Db\Exception
     */
    protected function _createTable($baseTable, $tableDef, $options = array()) {
        $charset = isset($options['charset']) ? $options['charset'] : 'utf8';
        $collate = isset($options['collate']) ? $options['collate'] : '';
        $tableName = $this -> _prefix . $baseTable;
        $sql = 'CREATE TABLE ' . $tableName . ' (';
        $glue = '';
        foreach ($tableDef['cols'] as $colName => $col) {
            $colDef = $glue . $colName;
            $glue = ',' . \Apl::LF;
            if (! isset($col['null'])) {
                $col['null'] = false;
            }
            $default = '';
            switch ($col['type']) {
                case 'int': {
                    if (! isset($col['signed'])) {
                        $col['signed'] = false;
                    }
                    if (! isset($col['size'])) {
                        $col['size'] = $col['signed'] ? 10 : 11;
                    }
                    $colDef .= ' INT(' . $col['size'] . ')';
                    if (! $col['signed']) {
                        $colDef .= ' unsigned';
                    }
                    if (isset($col['default'])) {
                        $default .= 'default ' . $col['default'];
                    }
                }
                break;

                case 'varchar': {
                    $mdbCol['type'] = 'text';
                    if (isset($col['default'])) {
                        $default .= 'default \''
                            . addcslashes($col['default'], '\'') . '\'';
                    }
                }
                break;
            }
            if (! $col['null']) {
                $colDef .= 'NOT NULL ';
            }
            $dolDef .= $default;
            if (isset($col['comment'])) {
                $colDef .= 'COMMENT \'' . addcslashes($col['comment'], '\'') . '\'';
            }
            $sql .= $colDef;
        }
        /*
         * Now create indicies
         */
        foreach ($tableDef['keys'] as $keyName => $key) {
            if ($keyName == 'PRIMARY') {
                $keySql = 'PRIMARY KEY';
            } elseif ($keyName[0] == '!') {
                $keySql = 'UNIQUE KEY ' . substr($keyName, 1);
            } else {
                $keySql = 'KEY ' . $keyName;
            }
            $keySql = 'CREATE ' . $keySql . ' ON ' . $tableName;
            $keyGlue = ' (';
            foreach ($key as $keyCol) {
                if (is_array($keyCol)) {
                    $col = $keyCol[0] . $keyCol[1] >= 0 ? ' ASC' : ' DESC';
                } else {
                    $col = $keyCol;
                }
                $keySql .= $keyGlue . $col;
                $keyGlue = ',';
            }
            $sql .= $glue . $keySql . ')';
        }
        $sql .= \Apl::LF . ') ENGINE='
            . (isset($tableDef['engine'])
                ? $tableDef['engine'] : 'InnoDB')
            . ' DEFAULT CHARSET' . (isset($tableDef['charset'])
                ? $tableDef['charset'] : $charset)
            ;
        $tcollate = isset($tableDef['collate']) ? $tableDef['collate'] : $collate;
        if ($tcollate != '') {
            $sql .= ' COLLATE ' . $tcollate;
        }
        if (isset($tableDef['comment'])) {
            $sql .= ' COMMENT ' . addcslashes($tableDef['comment'], '\'');
        }
        $db = &JFactory::getDBO();
        $db -> setQuery($sql);
        if (! $db -> query()) {
            $err = $db -> getErrorMsg();
            JError::raiseError(500, $err);
            throw new \Apl\Db\Exception($err);
        }
    }

    /**
     * Apply a list of schema changes to the database.
     *
     * @param array List of change instructions.
     */
    protected function _schemaChange($list) {
    }

    /**
     * Record the schema version in the database
     *
     * @param string Version identifier
     * @throws \Apl\Db\Exception
     */
    protected function _setSchemaVersion($version) {
        $sql = 'REPLACE ' . $this -> _prefix . 'permission'
            . ' SET domainID=0, assetID=0, requesterID=0, permissionDefID=0'
            . ', permissionValue=\'' . $db -> getEscaped($version) . '\''
            . ',info=\'Schema Version\''
            ;
        $db = &JFactory::getDBO();
        $db -> setQuery($sql);
        if (! $db -> query()) {
            $err = $db -> getErrorMsg();
            JError::raiseError(500, $err);
            throw new \Apl\Db\Exception($err);
        }
    }

    /**
     * Table truncation.
     *
     * @param string The name of the table to be emptied.
     */
    protected function _truncate($table) {
        $db = &JFactory::getDBO();
        $db -> setQuery('TRUNCATE ' . $this -> _prefix . $table);
        if (! $db -> query()) {
            $err = $db -> getErrorMsg();
            JError::raiseError(500, $err);
            throw new \Apl\Db\Exception($err);
        }
    }

    function assetMerge($fromAsset, $toAsset) {
        $db = &JFactory::getDBO();
        $className = get_class($fromAsset);
        if (! isset($this -> _classToTable[$className])) {
            throw new \Apl\Db\Exception('Can\'t handle class name ' . $className . '.');
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
        $db -> setQuery($sql);
        if (! $db -> query()) {
            $err = $db -> getErrorMsg();
            JError::raiseError(500, $err);
            throw new \Apl\Db\Exception($err);
        }
        /*
         * Update the remaining records. We don't need the join fields because
         * the assetID implies matching domains and sections.
         */
        $sql = 'UPDATE ' . $this -> _prefix . 'permission'
            . ' SET ' . $assetKey . '=' . $toAsset -> getID()
            . ' WHERE ' . $assetKey . '=' . $fromAsset -> getID()
            ;
        $db -> setQuery($sql);
        if (! $db -> query()) {
            $err = $db -> getErrorMsg();
            JError::raiseError(500, $err);
            throw new \Apl\Db\Exception($err);
        }
        $this -> delete($fromAsset);
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
        $db = &JFactory::getDBO();
        $db -> setQuery($sql);
        $result = $db -> loadResult();
        if ($result === null) {
            return false;
        }
        return $result;
    }

}
