<?php
/**
 * ACL Asset support.
 *
 * @package AP5L
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Asset.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * An asset represents an object that is subject to access control.
 *
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_Asset extends AP5L_Acl_Tree {
    /**
     * The unique ID for this asset.
     *
     * @var int
     */
    protected $_assetID;

    /**
     * The name of this asset.
     *
     * @var string
     */
    protected $_assetName;

    /**
     * The ID of the section that this asset belongs to.
     *
     * @var int
     */
    protected $_assetSectionID;

    /**
     * Maps sort orders into matching properties in this class.
     *
     * @var array
     */
    static protected $_fieldMap = array(
        'id' => '_assetID',
        'name' => '_assetName',
        'sectionid' => '_assetSectionID',
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
        'displayName' => 'Asset',
        'name' => '_assetName',
        'sectionClass' => 'AP5L_Acl_AssetSection',
        'sectionID' => '_assetSectionID',
    );

    /**
     * Add a new asset.
     *
     * Adds an asset to the ACL store. If no parent path is provided, a top
     * level asset is created.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Asset section name. Must be the name of an existing asset
     * section.
     * @param string|array The new asset name, or a path to the new asset
     * relative to the parent. All elements of the path except the last must
     * exist, unless the "create" option is specified.
     * @param array Options. Defined options are: [create]=boolean: if set, the
     * entire asset path is created.
     * @return AP5L_Acl_Asset The new asset object.
     *
     */
    static function &add(
        &$store, $sectionName, $assetPath = array(), $options = array()
    ) {
        return parent::_add(
            self::$_template, $store, $sectionName, $assetPath, $options
        );
    }

    /**
     * Remove an asset from the ACL store.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Asset section name.
     * @param string|array Asset name or path.
     * @param array Options.
     * @return void
     */
    static function delete(&$store, $sectionName, $path, $options = array()) {
        parent::_delete(self::$_template, $store, $sectionName, $path, $options);
    }

    /**
     * Create a new asset object.
     *
     * @param AP5L_Acl_AssetSection Optional section for this asset.
     * @param int Optional ID of a parent asset.
     * @param string Optional name of the asset.
     * @return AP5L_Acl_asset The new asset object.
     */
    static function &factory($section = null, $parentID = 0, $name = '') {
        $asset = new AP5L_Acl_Asset();
        $asset -> setName($name);
        $asset -> setParentID($parentID);
        if ($section) {
            $asset -> setSection($section);
        }
        return $asset;
    }

    /**
     * Retrieve an asset by path.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param AP5L_Acl_AssetSection|string The asset section or asset section
     * name.
     * @param array|string One or more path elements.
     * @param array Options: [create]=boolean: if set, the entire asset path is
     * created. [exists] =boolean: if set, the asset must exist. If false, the
     * asset must not exist. [parent] A base asset that the pathis relative to.
     * [_path] =array (internal) current path elements for error reporting.
     * @return AP5L_Acl_Asset|false Asset if found, false if not.
     */
    static function &fetchByPath(&$store, $section, $path, $options = array()) {
        if (is_string($section)) {
            $section = AP5L_Acl_AssetSection::fetchByName(
                $store, $section, array('exists' => true)
            );
        }
        $asset = parent::_fetchByPath(
            self::$_template, $store, $section, $path, $options
        );
        return $asset;
    }

    /**
     * Get the asset identifier.
     *
     * @return int ID of this asset.
     */
    function getID() {
        return $this -> _assetID;
    }

    /**
     * Get the asset name.
     *
     * @return string Asset name.
     */
    function getName() {
        return $this -> _assetName;
    }

    /**
     * Get a list of assets in contained a path.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section in which to list.
     * @param array The base path to list.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Assets.
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
     * Merge one asset into another.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section of the source asset.
     * @param array|string The asset path to merge from.
     * @param string The section of the target asset.
     * @param array|string The asset path to merge to.
     * @param array Options.
     * @return void
     */
    static function merge(
        &$store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
     ) {
        parent::_merge(
            self::$_template, $store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options
        );
    }

    /**
     * Move asset to a different location.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section of the source asset.
     * @param array|string The asset path to move from.
     * @param string The section of the renamed asset.
     * @param array|string The asset path to move to.
     * @param array Options.
     * @return void
     */
    static function move(
        &$store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
     ) {
        parent::_move(
            self::$_template, $store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options
        );
    }

    /**
     * Get a list of assets by name.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section in which to list.
     * @param string The asset name.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Assets.
     */
    static function &search(&$store, $sectionName, $assetName, $options = array()) {
        if (isset($options['order'])) {
            $options['order'] = self::_mapUserSort($options['order'], self::$_fieldMap);
        } else {
            $options['order'] = self::_mapUserSort('id', self::$_fieldMap);
        }
        $result = &parent::_search(
            self::$_template, $store, $sectionName, $assetName, $options
        );
        return $result;
    }

    /**
     * Set the asset identifier.
     *
     * @param int The asset ID.
     * @return void
     */
    function setID($id) {
        $this -> _assetID = $id;
        parent::setID($id);
    }

    /**
     * Set the asset name.
     *
     * @param string The asset name.
     * @return void
     */
    function setName($name) {
        $this -> _assetName = $name;
    }

    /**
     * Set the asset's section.
     *
     * @param AP5L_Acl_AssetSection The section of this asset.
     * @return void
     */
    function setSection($section) {
        $this -> _assetSectionID = $section -> getID();
    }

    /**
     * Set the asset's section identifier.
     *
     * @param int The section ID of this asset.
     * @return void
     */
    function setSectionID($sectionID) {
        $this -> _assetSectionID = $sectionID;
    }

}
