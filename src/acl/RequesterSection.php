<?php
/**
 * ACL Requester section support.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: RequesterSection.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Requester section support.
 *
 * Requester sections essentially implement name spaces within an ACL domain. The
 * requester "user1" in "section A" is independent of the requester "user1" in
 * "section B".
 * 
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_RequesterSection extends AP5L_Acl_Section {
    protected $_requesterSectionID;
    protected $_requesterSectionName;

    static protected $_fieldMap = array(
        'id' => '_requesterSectionID',
        'name' => '_requesterSectionName',
    );

    static protected $_template = array(
        'className' => __CLASS__,
        'displayName' => 'Requester Section',
        'sectionName' => '_requesterSectionName',
    );

    function __construct($name = '') {
        $this -> _requesterSectionName = $name;
    }

    static function &add(&$store, $sectionName, $options = array()) {
        return self::_add(self::$_template, $store, $sectionName, $options);
    }

    static function delete(&$store, $sectionName, $options = array()) {
        self::_delete(self::$_template, $store, $sectionName, $options);
    }

    static function &factory($name = '') {
        $domain = new AP5L_Acl_RequesterSection($name);
        return $domain;
    }

    static function &fetchByName(&$store, $sectionName, $options = array()) {
        return self::genericFetchByName(self::$_template, $store, $sectionName, $options);
    }

    function getID() {
        return $this -> _requesterSectionID;
    }

    function getName() {
        return $this -> _requesterSectionName;
    }

    static function getSectionMemberClasses() {
        return array('AP5L_Acl_Requester');
    }

    /**
     * Get a list of sections.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Requester section names.
     */
    static function &listing(&$store, $options = array()) {
        if (isset($options['order'])) {
            $options['order'] = self::_mapUserSort($options['order'], self::$_fieldMap);
        } else {
            $options['order'] = self::_mapUserSort('name', self::$_fieldMap);
        }
        $result = self::_listing(self::$_template, $store, $options);
        return $result;
    }

    /**
     * Merge one section into another.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Section name to merge from.
     * @param string Section name merge into.
     * @param array Options.
     */
    static function merge(&$store, $fromSectionName, $toSectionName, $options = array()) {
        self::_merge(self::$_template, &$store, $fromSectionName, $toSectionName, $options);
    }

    static function update(&$store, $sectionName, $options = array()) {
        self::_update(self::$_template, $store, $sectionName, $options);
    }

    function setID($id) {
        $this -> _requesterSectionID = $id;
    }

    function setName($name) {
        $this -> _requesterSectionName = $name;
    }

    static function template() {
        return self::$_template;
    }

}
