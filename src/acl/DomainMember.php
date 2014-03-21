<?php
/**
 * Domain members: any object contained within a domain.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: DomainMember.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A domain member is any object maintained within a domain.
 * 
 * @package AP5L
 * @subpackage Acl
 */
abstract class AP5L_Acl_DomainMember extends AP5L_Php_InflexibleObject {
    /**
     * Domain identifier.
     *
     * @var int
     */
    protected $_domainID = 0;

    /**
     * Generic descriptive text associated with the object.
     *
     * @var string
     */
    protected $_info = '';

    /**
     * Hide object from UI flag.
     *
     * @var boolean
     */
    protected $_isHidden = false;

    /**
     * Reference to data store.
     *
     * @var AP5L_Acl_Store
     */
    protected $_store;

    /**
     * Map user-friendly sort identifiers into class properties.
     * 
     * Invalid or mismatched identifiers are silently dropped.
     * 
     * @param mixed Either a string sort identifier or an array of identifiers.
     * @param array A mapping table from user identifiers to properties.
     * @return array Sort orders mapped to class properties.
     */
    protected function _mapUserSort($sort, $userMap) {
        $mapped = array();
        if (is_array($sort)) {
            foreach ($sort as &$col) {
                if ($order = self::_mapUserSortCol($col, $userMap)) {
                    $mapped[] = $order;
                }
            }
        } else {
            if ($order = self::_mapUserSortCol($sort, $userMap)) {
                $mapped[] = $order;
            }
        }
        return $mapped;
    }

    /**
     * Map a user-friendly sort identifier into class property.
     * 
     * @param string A sort identifier, optionally followed by "asc" or "desc".
     * @param array A mapping table from user identifiers to properties.
     * @return string|boolean The sort expression with the user identifer mapped
     * to a class property, or false if no mapping is available.
     */
    protected function _mapUserSortCol($col, $userMap) {
        $col = explode(' ', $col);
        $order = strtolower($col[0]);
        if (! isset($userMap[$order])) {
            return false;
        }
        $col[0] = $userMap[$order];
        return implode(' ', $col);
    }

    /**
     * Get the object's identifier.
     * 
     * @return int Identifier for this object.
     */
    abstract function getID();

    /**
     * Get descriptive text.
     * 
     * @return string Descriptive text associated with this object.
     */
    function getInfo() {
        return $this -> _info;
    }

    /**
     * Return hidden object status.
     * 
     * @return boolean True if this object is to be hidden from user interfaces.
     */
    function isHidden() {
        return $this -> _isHidden;
    }

    /**
     * Load information from a data store into this object.
     * 
     * @param array Either an array of keys (member names), or an associative
     * array of values, indexed by member name.
     * @param array Optional. If the first parameter is a list of keys, this is
     * a corresponding list of values.
     */
    function load($keysOrBoth, $vals = null) {
        if (is_null($vals)) {
            foreach ($keysOrBoth as $member => $value) {
                $this -> $member = $value;
            }
        } else {
            reset($vals);
            foreach ($keysOrBoth as $member) {
                $this -> $member = current($vals);
                next($vals);
            }
        }
    }

    /**
     * Map user-friendly sort identifiers into class properties.
     * 
     * Invalid or mismatched identifiers are silently dropped.
     * 
     * @param mixed Either a string sort identifier or an array of identifiers.
     * @return array Sort orders mapped to class properties.
     */
    static function mapUserSort($sort) {
        return self::_mapUserSort($sort, self::$_fieldMap);
    }

    /**
     * Set hidden object status.
     * 
     * @param boolean True if this object is to be hidden from user interfaces.
     */
    function setHidden($hidden) {
        $this -> _isHidden = $hidden;
    }

    /**
     * Set the object's identifier.
     * 
     * @param int Identifier for this object.
     */
    abstract function setID($id);

    /**
     * Set descriptive text.
     * 
     * @param string Descriptive text associated with this object.
     */
    function setInfo($info) {
        $this -> _info = $info;
    }

    /**
     * Set the data store for this object.
     * 
     * @param AP5L_Acl_Store The data storage object.
     */
    function setStore(&$store) {
        $this -> _store = $store;
    }

    /**
     * Save object data into an array for storage.
     * 
     * @param array Mapping from object properties to column names.
     * @return array Associative array of object values, indexed by column name.
     */
    function &unload($colMap) {
        $rec = array();
        foreach ($colMap as $property => $col) {
            if (property_exists($this, $property)) {
                $rec[$col] = $this -> $property;
            }
        }
        return $rec;
    }

}
