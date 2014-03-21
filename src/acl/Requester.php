<?php
/**
 * ACL Requester support.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Requester.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A requester represents an entity requesting access to an asset.
 * 
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_Requester extends AP5L_Acl_Tree {
    protected $_requesterID;
    protected $_requesterName;
    protected $_requesterSectionID;

    static protected $_fieldMap = array(
        'id' => '_requesterID',
        'name' => '_requesterName',
        'sectionid' => '_requesterSectionID',
    );

    static protected $_template = array(
        'className' => __CLASS__,
        'displayName' => 'Requester',
        'name' => '_requesterName',
        'sectionClass' => 'AP5L_Acl_RequesterSection',
        'sectionID' => '_requesterSectionID',
    );

    /**
     * Add a new requester.
     *
     * Adds a requester to the ACL store. If no parent path is provided, a top
     * level requester is created.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Requester section name. Must be the name of an existing requester
     * section.
     * @param string|array The new requester name, or a path to the new requester
     * relative to the parent. All elements of the path except the last must
     * exist, unless the "create" option is specified.
     * @param array Optional path to the requester's parent. If provided, each
     * element in the array names a requester in the provided section.
     * @param array Options. Defined options are: [create]=boolean: if set, the
     * entire requester path is created.
     */
    static function &add(
        &$store, $sectionName, $requesterPath, $options = array()
    ) {
        return parent::_add(
            self::$_template, $store, $sectionName, $requesterPath, $options
        );
    }

    /**
     * Remove a requester from the ACL store.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Requester section name.
     * @param string|array Requester name or path.
     * @param array Options.
     */
    static function delete(&$store, $sectionName, $path, $options = array()) {
        parent::_delete(self::$_template, $store, $sectionName, $path, $options);
    }

    static function &factory($section = null, $parentID = 0, $name = '') {
        $requester = new AP5L_Acl_Requester();
        $requester -> setName($name);
        $requester -> setParentID($parentID);
        if ($section) {
            $requester -> setSection($section);
        }
        return $requester;
    }

    /**
     * Retrieve a requester by path/name.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param AP5L_Acl_RequesterSection|string The requester section or name of
     * the requester section.
     * @param array|string One or more path elements.
     * @param array Options: [create]=boolean: if set, the entire requester path is
     * created. [exists] =boolean: if set, the requester must exist. If false, the
     * requester must not exist. [parent] A base requester that the pathis relative to.
     * [_path] =array (internal) current path elements for error reporting.
     * @return AP5L_Acl_Requester|false Requester if found, false if not.
     */
    static function &fetchByPath(&$store, $section, $path, $options = array()) {
        if (is_string($section)) {
            $section = AP5L_Acl_RequesterSection::fetchByName(
                $store, $section, array('exists' => true)
            );
        }
        $requester = parent::_fetchByPath(
            self::$_template, $store, $section, $path, $options
        );
        return $requester;
    }

    function getID() {
        return $this -> _requesterID;
    }

    function getName() {
        return $this -> _requesterName;
    }

    /**
     * Get a list of requesters.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section in which to list.
     * @param array The base path to list.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Requesters.
     */
    static function &listing(&$store, $sectionName, $path = null, $options = array()) {
        if (isset($options['order'])) {
            $options['order'] = self::_mapUserSort($options['order'], self::$_fieldMap);
        } else {
            $options['order'] = self::_mapUserSort('name', self::$_fieldMap);
        }
        $result = parent::_listing(
            self::$_template, $store, $sectionName, $path, $options
        );
        return $result;
    }

    /**
     * Merge one requester into another.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section of the source requester.
     * @param array|string The requester path to merge from.
     * @param string The section of the target requester.
     * @param array|string The requester path to merge to.
     * @param array Options.
     */
    static function merge(
        &$store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
     ) {
        parent::_merge(
            self::$_template, $store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options
        );
    }

    /**
     * Move requester to a different location.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section of the source requester.
     * @param array|string The requester path to move from.
     * @param string The section of the renamed requester.
     * @param array|string The requester path to move to.
     * @param array Options.
     */
    static function move(
        &$store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
     ) {
        parent::_move(
            self::$_template, $store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options
        );
    }

    /**
     * Get a list of requesters by name
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section in which to list.
     * @param string The requester name.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Matching AP5L_Acl_Requester objects
     */
    static function &search(&$store, $sectionName, $requesterName, $options = array()) {
        if (isset($options['order'])) {
            $options['order'] = self::_mapUserSort($options['order'], self::$_fieldMap);
        } else {
            $options['order'] = self::_mapUserSort('id', self::$_fieldMap);
        }
        $result = &parent::_search(
            self::$_template, $store, $sectionName, $requesterName, $options
        );
        return $result;
    }

    function setID($id) {
        $this -> _requesterID = $id;
        parent::setID($id);
    }

    function setName($name) {
        $this -> _requesterName = $name;
    }

    function setSection($section) {
        $this -> _requesterSectionID = $section -> getID();
    }

    function setSectionID($sectionID) {
        $this -> _requesterSectionID = $sectionID;
    }

}
