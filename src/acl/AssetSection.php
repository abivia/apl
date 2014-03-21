<?php
/**
 * ACL Asset sections. 
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: AssetSection.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Asset section support.
 * 
 * Asset sections essentially implement name spaces within an ACL domain. The
 * asset "gold" in "section A" is independent of the asset "gold" in "section
 * B".
 * 
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_AssetSection extends AP5L_Acl_Section {
    protected $_assetSectionID;
    protected $_assetSectionName;

    /**
     * Maps sort orders into matching properties in this class.
     * 
     * @var array
     */
    static protected $_fieldMap = array(
        'id' => '_assetSectionID',
        'name' => '_assetSectionName',
    );

    /**
     * Template parameters for this class. In PHP5, a class can't override
     * static members. These values are passed in to methods of the parent class
     * to get around this.
     * 
     * @var array
     */
    static protected $_template = array(
        'className' => __CLASS__,
        'displayName' => 'Asset Section',
        'sectionName' => '_assetSectionName',
    );

    /**
     * Class Constructor
     * 
     * @var string Optional name of the section.
     */
    function __construct($name = '') {
        $this -> _assetSectionName = $name;
    }

    /**
     * Add an asset section to the ACL store.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Asset section name. The section must not already exist.
     * @param array Options.
     * @return AP5L_Acl_AssetSection The new asset section.
     * @throws AP5L_Acl_Exception If the name is empty or if the section already
     * exists.
     * @throws AP5L_Db_Exception On any data store error.
     */
    static function &add(&$store, $sectionName, $options = array()) {
        return self::_add(self::$_template, $store, $sectionName, $options);
    }

    /**
     * Remove an asset section from the ACL store.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Section name.
     * @param array Options.
     */
    static function delete(&$store, $sectionName, $options = array()) {
        self::_delete(self::$_template, $store, $sectionName, $options);
    }

    /**
     * Create a new asset section object.
     * 
     * @var string Optional name of the section.
     */
    static function &factory($name = '') {
        $section = new AP5L_Acl_AssetSection($name);
        return $section;
    }

    /**
     * Retrieve an asset section by name.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param The section name.
     * @param array Options: [exists]=boolean: if set, the section must exist.
     * If false, the section must not exist.
     * @return object|false Section if found, false if not.
     * @throws AP5L_Acl_Exception On conflict between "exists" option and
     * section existance.
     */
    static function &fetchByName(&$store, $sectionName, $options = array()) {
        return self::genericFetchByName(self::$_template, $store, $sectionName, $options);
    }

    /**
     * Get the asset section identifier.
     * 
     * @return int Asset section identifier.
     */
    function getID() {
        return $this -> _assetSectionID;
    }

    /**
     * Get the asset section name.
     * 
     * @return string Asset section name.
     */
    function getName() {
        return $this -> _assetSectionName;
    }

    /**
     * Get a list of class names that can be members of this section.
     * 
     * Data stores use this information in section merge operations.
     * 
     * @return array
     */
    static function getSectionMemberClasses() {
        return array('AP5L_Acl_Asset');
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
     * @return array Asset section names.
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

    /**
     * Set the asset section identifier.
     * 
     * @param int Section identifier.
     */
    function setID($id) {
        $this -> _assetSectionID = $id;
    }

    /**
     * Set the asset section name.
     * 
     * @param string Section name
     */
    function setName($name) {
        $this -> _assetSectionName = $name;
    }

    /**
     * Get template information for this class.
     * 
     * @return array Information on this class.
     */
    static function template() {
        return self::$_template;
    }

    /**
     * Update an asset section.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Section name.
     * @param array Options.
     */
    static function update(&$store, $oldSectionName, $options = array()) {
        self::_update(self::$_template, $store, $oldSectionName, $options);
    }

}
