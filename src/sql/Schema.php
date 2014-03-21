<?php
/**
 * Classes to support SQL schema creation.
 * 
 * @package AP5L
 * @subpackage Sql
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Schema.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

class SchemaColumn {
    var $attributes;
    var $autoIncrement = false;
    var $collate;
    var $defaultValue;
    var $lengthValues;                  // Length/value text
    var $name;
    var $nulls = false;
    var $sequence;                      // Order of generation
    var $table = null;
    var $triggers = array();
    var $type;
    
    function clear() {
        $this -> attributes = '';
        $this -> autoIncrement = false;
        $this -> collate = '';
        $this -> defaultValue = null;
        $this -> lengthValues = '';
        $this -> name = '';
        $this -> nulls = false;
        $this -> table = null;
        $this -> type = '';
    }
    
    function toSql() {
    }
}

class SchemaIndex {
    var $cols = array();
    var $fullText = false;
    var $name;
    var $table = null;
    var $unique = false;
    
    function addColumn($colName, $index = -1) {
        if (! $colName) {
            // Missing column name
            return false;
        }
        if ($this -> table && ! array_key_exists($colName, $this -> table -> cols)) {
            // Unknown column name
            return false;
        }
        if (($old = array_search($colName, $this -> cols)) != false) {
            // Column already exists, remove the old column.
            unset($this -> cols[$old]);
            if ($index > $old) {
                --$index;
            }
        }
        if (! ($this -> name || count($this -> cols))) {
            $this -> name = $colName;
        }
        if ($index == -1 || $index >= count($this -> cols)) {
            $this -> cols[] = $colName;
        } else {
            array_splice($this -> cols, $index, 0, $colName);
        }
    }
    
}

class SchemaTable {
    var $autoIncrement = 1;
    var $charset;
    var $collate;
    var $comment;
    var $cols = array();
    var $engine;
    var $keys = array();
    var $name;
    var $schema = null;
    
    function &addColumn($name, $type = '', $length = '', $defaultValue = null) {
        if (isset($this -> cols[$name])) {
            return false;
        }
        if ($type) {
            $refType = $type;
        } else {
            $refType = $name;
        }
        if ($refType && $refType{0} == '*') {
            if (! $this -> schema) {
                return false;
            }
            $refType = substr($refType, 1);
            if (! isset($this -> schema -> commonTypes[$refType])) {
                return false;
            }
            $col = $this -> schema -> commonTypes[$refType];
            if ($type) {
                $col -> name = $name;
            }
        } else {
            $col = new SchemaColumn;
            $col -> name = $name;
            $col -> type = $type;
            $col -> lengthValues = $length;
            $col -> defaultValue = $defaultValue;
        }
        $col -> sequence = count($this -> cols);
        $col -> table = &$this;
        $this -> cols[$name] = &$col;
        return $col;
    }
    
    function &addIndex($name = '') {     // additional arguments are column names
        $key = new SchemaIndex();
        $key -> name = $name;
        for ($ind = 1; $ind < func_num_args(); $ind++) {
            $key -> addColumn(func_get_arg($ind));
        }
        $this -> keys[] = &$key;
        return $key;
    }
    
    function &getIndex($name) {
        foreach ($this -> keys as $keyInd => $key) {
            if ($key -> name == $name) {
                return $this -> keys[$keyInd];
            }
        }
        return null;
    }
    
}

class Schema {
    var $charset;
    var $collate;
    var $commonTypes = array();         // Array[typename] of common field types
    var $tables = array();              // Array[tablename]
    
    function __construct() {
    }
    
    function Schema() {
        $this -> __construct();
    }
    
    function &addTable($name) {
        if (isset($this -> tables[$name])) {
            return false;
        } else {
            $tab = new SchemaTable();
            $tab -> name = $name;
            $tab -> schema = &$this;
            $this -> tables[$name] = &$tab;
            return $tab;
        }
    }
    
    function &getTable($name) {
        if (isset($this -> tables[$name])) {
            return $this -> tables[$name];
        } else {
            return null;
        }
    }
    
    function setCommonType($name, $def) {
        $this -> commonTypes[$name] = $def;
    }
    
    function toSql() {
    }
    
}

class SchemaFormatter {
    var $optDropTable;
    var $optIfTableExists;
    var $optQuoteIdentifiers;
    var $optSortTables = true;
    var $target;                        // Database type we're generating for (e.g. "MySQL")
    var $targetVersion;                 // Version of the selected DB type (e.g. "4.1")
    
    function id($ident) {
        if ($this -> optQuoteIdentifiers) {
            $ident = '`' . $ident . '`';
        }
        return $ident;
    }
    
    function renderColumn($column) {
        $type = strtolower($column -> type);
        $sql = $this -> id($column -> name) . ' ' . $type;
        $collate = '';
        $dv = '\'\'';
        $nulls = $column -> nulls ? '' : ' NOT NULL';
        switch ($type) {
            case 'char': 
            case 'varchar': 
            {
                $sql .= '(' . $column -> lengthValues . ')';
                $dv = $column -> defaultValue ? '\'' . addslashes($column -> defaultValue) . '\'' : '\'\'';
                if ($column -> collate != $column -> table -> collate) {
                    $collate = ' collate ' . $column -> collate;
                }
            } break;
            
            case 'date':
            {
                $dv = $column -> defaultValue ? '\'' . $column -> defaultValue . '\'' : '\'0000-00-00\'';
            } break;
            
            case 'datetime':
            {
                $dv = $column -> defaultValue ? '\'' . $column -> defaultValue . '\'' : '\'0000-00-00 00:00:00\'';
            } break;
            
            case 'decimal':
            case 'int':
            {
                $sql .= '(' . $column -> lengthValues . ')';
                $dv = $column -> defaultValue ? '\'' . $column -> defaultValue . '\'' : '\'0\'';
            } break;
            
            case 'float': {
                $dv = $column -> defaultValue ? $column -> defaultValue  : '0';
            }
            break;
            
            case 'text':
            {
                $dv = null;
                if ($column -> collate != $column -> table -> collate) {
                    $collate = ' collate ' . $column -> collate;
                }
            } break;

            case 'timestamp':
            {
                $dv = $column -> defaultValue;
            } break;
            
        }
        $sql .= $collate . $nulls;
        if ($column -> autoIncrement !== false) {
            $sql .= ' auto_increment';
        } else if (! is_null($dv)) {
            $sql .= ' default ' . $dv;
        }
        foreach ($column -> triggers as $trig => $val) {
            $sql .= ' on ' . $trig . ' ' . $val;
        }
        return $sql;
    }
    
    function renderIndex($index) {
        $sql = '';
        $name = $index -> name;
        if (strtolower($name) == 'primary') {
            $sql .= 'PRIMARY ';
            $name = '';
        } else {
            $name = $this -> id($name) . ' ';
            if ($index -> fullText) {
                $sql .= 'FULLTEXT ';
            } else if ($index -> unique) {
                $sql .= 'UNIQUE ';
            }
        }
        $sql .= 'KEY ' . $name . '(';
        $delim = '';
        foreach ($index -> cols as $col) {
            $sql .= $delim . $this -> id($col);
            $delim = ', ';
        }
        $sql .= ')';
        return $sql;
    }
    
    function renderSchema($schema) {
        $sql = '';
        $delim = '';
        $tableRef = array_keys($schema -> tables);
        if ($this -> optSortTables) {
            sort($tableRef);
        }
        foreach ($tableRef as $currTable) {
            $sql .= $delim . $this -> renderTable($schema -> tables[$currTable]);
            $delim = chr(10) . chr(10);
        }
        return $sql;
    }
    
    function renderTable($table) {
        //
        // Create table
        //
        $sql = '';
        $glue = ',' . chr(10) . '    ';
        $tabName = $this -> id($table -> name);
        if ($this -> optDropTable) {
            $sql .= 'DROP TABLE IF EXISTS ' . $tabName . ';' . chr(10);
        }
        $sql .= 'CREATE TABLE ';
        if ($this -> optIfTableExists) {
            $sql .= 'IF NOT EXISTS ';
        }
        $sql .= $tabName . ' (' . chr(10);
        //
        // Column list
        //
        $delim = '    ';
        $autoInc = false;
        foreach ($table -> cols as $currCol) {
            $sql .= $delim . $this -> renderColumn($currCol);
            if ($currCol -> autoIncrement !== false) {
                $autoInc = $currCol -> autoIncrement;
            }
            $delim = $glue;
        }
        //
        // Index list
        //
        $keys = array();
        $primary = -1;
        foreach ($table -> keys as $keyid => $currKey) {
            if (strtolower($currKey -> name) == 'primary') {
                $primary = $keyid;
            } else {
                $keys[$currKey -> name] = $keyid;
            }
        }
        ksort($keys);
        if ($primary != -1) {
            $sql .= $delim . $this -> renderIndex($table -> keys[$primary]);
            $delim = $glue;
        }
        foreach ($keys as $currKey) {
            $sql .= $delim . $this -> renderIndex($table -> keys[$currKey]);
            $delim = $glue;
        }
        //
        // Table attributes
        //
        $attr = ' ENGINE=' . ($table -> engine ? $table -> engine : 'MyISAM');
        $attr .= ' DEFAULT CHARSET=' . ($table -> charset ? $table -> charset : $table -> schema -> charset);
        $attr .= ' COLLATE=' . ($table -> collate ? $table -> collate : $table -> schema -> collate);
        if ($table -> comment) {
            $attr .= ' COMMENT=\'' . addslashes($table -> comment) . '\'';
        }
        if ($autoInc !== false) {
            $attr .= ' AUTO_INCREMENT=' . $autoInc;
        }
        $sql .= chr(10) . ')' . $attr . ';';
        return $sql;
    }
    
}

?>