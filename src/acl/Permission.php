<?php
/**
 * Permission value.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Permission.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A permission is a right or value that can be granted to a requester for an
 * asset.
 * 
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_Permission extends AP5L_Acl_DomainMember {
    protected $_assetID;
    protected $_editTime;

    /**
     * Conflicted status. Set if this permission is the result of a conflict
     * resolution. This is a non-persistent member that reflects the status of
     * a permission result.
     *
     * @var boolean
     */
    private $_isConflicted;
    protected $_isEnabled = true;
    protected $_permissionDefinitionID;
    protected $_permissionName;
    protected $_permissionValue;
    protected $_requesterID;

    static protected $_fieldMap = array(
        'name' => '_permissionName',
        'value' => '_permissionValue',
    );

    function __construct($asset = null, $requester = null, $pdef = null, $permissionValue = null) {
        if (! is_null($asset)) {
            $this -> _assetID = $asset -> getID();
        }
        if (! is_null($requester)) {
            $this -> _requesterID = $requester -> getID();
        }
        if (! is_null($pdef)) {
            $this -> _permissionDefinitionID = $pdef -> getID();
            if (! is_null($permissionValue)) {
                $this -> _permissionValue = $pdef -> validate($permissionValue);
            }
        }
    }

    /*
     * Select a permission value from a leaf-to-leaf sub-tree in a sparse
     * permission matrix.
     *
     * This method is the core of the conflict resolution logic. It looks for
     * the most applicable permisson in a subset of the results matrix that
     * corresponds to one intersection between asset occurances and requester
     * ocurrances. For each intersection, there is a path from the root asset
     * node to the asset and from the root requester node to the requester. The
     * method looks for:
     * <ul><li>A permission at the intersection of the leaf nodes; if one isn't
     * found,
     * </li><li>it searches for a match between one leaf and any permission
     * along the path of the other "plane" (a leaf asset is matched against the
     * requester plane; a leaf requester is matched against the asset plane). If
     * two permissions ate found at symmetrical positions, they are resolved by
     * edit date.
     * </li><li>If no permission is found, the leaf nodes are removed and the
     * search continues recursively on the remaining paths.
     * </li></ul>
     *
     * @param array A sparse permissions matrix, indexed by the IDs of assets in
     * the asset path, then by requester IDs int he requester path.
     * @param array A list of asset identifiers in the asset path, in reverse
     * order (leaf is at index 0).
     * @param array A list of requester identifiers in the requester path, in
     * reverse order (leaf is at index 0).
     * @return AP5L_Acl_Permission|null A resolved permission, if one exists;
     * null if none found.
     */
    static protected function _resolveLeaf($matrix, $aPath, $rPath) {
        if (! count($aPath) || ! count($rPath)) {
            // We have run out of places to look.
            return null;
        }
        /*
         * Get the IDs of the last leaf in each path
         */
        $aPlane = array_shift($aPath);
        $rPlane = array_shift($rPath);
        /*
         * If there's an intersection, it's the winner
         */
        if (isset($matrix[$aPlane][$rPlane])) {
            return $matrix[$aPlane][$rPlane];
        }
        /*
         * Look equal distances along the asset and request planes; if two
         * results found, resolve them by time.
         */
        $depth = max(count($aPath), count($rPath));
        for ($ind = 0; $ind < $depth; ++$ind) {
            $found = null;
            if ($ind < count($aPath) && isset($matrix[$aPath[$ind]][$rPlane])) {
                $requesterPermission = $matrix[$aPath[$ind]][$rPlane];
                $found = $requesterPermission;
            } else {
                $requesterPermission = null;
            }
            if ($ind < count($rPath) && isset($matrix[$aPlane][$rPath[$ind]])) {
                $assetPermission = $matrix[$aPath[$ind]][$rPlane];
                $found = $assetPermission;
            } else {
                $assetPermission = null;
            }
            /*
             * If we have two results, we have a mini-conflict. Resolve it via
             * timestamp.
             */
            if ($assetPermission && $requesterPermission) {
                $found = ($assetPermission -> getEditTime() > $requesterPermission -> getEditTime())
                    ? $assetPermission : $requesterPermission;
            }
            if ($found) {
                return $found;
            }
        }
        /*
         * No resolution at this depth. Move up a step.
         */
        return $this -> _resolveLeaf($matrix, $aPath, $rPath);
    }

    static function delete(
        &$store,
        $assetSection, $assetPath,
        $requesterSection, $requesterPath,
        $permissionSection, $permissionName,
        $options = array()
    ) {
        $permission = self::fetchByName(
            $store,
            $assetSection, $assetPath,
            $requesterSection, $requesterPath,
            $permissionSection, $permissionName
        );
        if (! $permission) {
            // This is a siltent failure
            return;
        }
        $store -> delete($permission);
    }

    static function &factory($asset = null, $requester = null, $pdef = null, $permissionValue = null) {
        $pdef = new AP5L_Acl_Permission($asset, $requester, $pdef, $permissionValue);
        return $pdef;
    }

    static function &fetch(
        &$store,
        $asset,
        $requester,
        $pdef,
        $options = array()
    ) {
        $permission = &$store -> get(
            __CLASS__,
            array(
                '_assetID' => $asset -> getID(),
                '_requesterID' => $requester -> getID(),
                '_permissionDefinitionID' => $pdef -> getID(),
            ),
            array('first' => true)
        );
        if (isset($options['exists'])) {
            if ($options['exists'] && $pdef === false) {
                throw new AP5L_Acl_Exception(
                    'Permission does not exist.'
                );
            } elseif (! $options['exists'] && $pdef !== false) {
                throw new AP5L_Acl_Exception(
                    'Permission already exists.'
                );
            }
        }
        return $permission;
    }

    static function &fetchByName(
        &$store,
        $assetSection, $assetPath,
        $requesterSection, $requesterPath,
        $permissionSection, $permissionName,
        $options = array()
    ) {
        $fetchOpts = array('exists' => true);
        /*
         * Validate eveything
         */
        $asset = AP5L_Acl_Asset::fetchByPath(
            $store, $assetSection, $assetPath, $fetchOpts
        );
        $requester = AP5L_Acl_Requester::fetchByPath(
            $store, $requesterSection, $requesterPath, $fetchOpts
        );
        $pdef = AP5L_Acl_PermissionDefinition::fetchByName(
            $store, $permissionSection, $permissionName, $fetchOpts
        );
        $permission = self::fetch($store, $asset, $requester, $pdef);
        if (isset($options['exists'])) {
            if ($options['exists'] && $pdef === false) {
                throw new AP5L_Acl_Exception(
                    'Permission does not exist.'
                );
            } elseif (! $options['exists'] && $pdef !== false) {
                throw new AP5L_Acl_Exception(
                    'Permission already exists.'
                );
            }
        }
        return $permission;
    }

    function getAssetID() {
        return $this -> _assetID;
    }

    function getConflicted() {
        return $this -> _isConflicted;
    }

    function getDefinitionID() {
        return $this -> _permissionDefinitionID;
    }

    function getEditTime() {
        return $this -> _editTime;
    }

    function getEnabled() {
        return $this -> _isEnabled;
    }

    function getID() {
        return false;
    }

    function getName() {
        return $this -> _permissionName;
    }

    function getRequesterID() {
        return $this -> _requesterID;
    }

    function getRules() {
        return $this -> _rules;
    }

    function getType() {
        return $this -> _type;
    }

    function getValue() {
        return $this -> _permissionValue;
    }

    /**
     * Get a list of permissions.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Permission values indexed by section, permission names.
     */
    static function &listing(
        &$store,
        $assetSection, $assetPath,
        $requesterSection, $requesterPath,
        $permissionSection = null,
        $options = array()
    ) {
        $criteria = array();
        $fetchOpts = array('exists' => true);
        /*
         * Validate eveything
         */
        $asset = AP5L_Acl_Asset::fetchByPath(
            $store, $assetSection, $assetPath, $fetchOpts
        );
        $criteria['_assetID'] = $asset -> getID();
        $requester = AP5L_Acl_Requester::fetchByPath(
            $store, $requesterSection, $requesterPath, $fetchOpts
        );
        $criteria['_requesterID'] = $requester -> getID();
        if (! is_null($permissionSection)) {
            $psec = AP5L_Acl_PermissionSection::fetchByName(
                $store, $permissionSection, $fetchOpts
            );
            $criteria['_permissionSectionID'] = $psec -> getID();
        }
        if (isset($options['order'])) {
            $options['order'] = self::_mapUserSort($options['order'], self::$_fieldMap);
        } else {
            $options['order'] = self::_mapUserSort('name', self::$_fieldMap);
        }
        $result = $store -> permissionList($criteria, $options);
        return $result;
    }

    /**
     * Get ACL permisson.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string|AP5L_Acl_AssetSection Asset section object or asset section
     * name.
     * @param string Asset name.
     * @param string|AP5L_Acl_RequesterSection Requester section object or
     * requester section name.
     * @param string Requester name.
     * @param string|AP5L_Acl_PermissionDefinition|AP5L_Acl_PermissionSection
     * Permission definition object, Permission section object, or permission
     * section name.
     * @param string Permission name. Only required if a permission section was
     * provided.
     * @param array Options.
     * @return AP5L_Acl_Permission|null The corresponding permission setting, if
     * any. Null if none found.
     * @throws AP5L_Acl_Exception If the permission definition does not exist.
     */
    static function queryPermission(
        $store, $assetSection, $assetName, $requesterSection, $requesterName,
        $pdef, $permissionName = ''
    ) {
        /*
         * Make sure the definition exists.
         */
        if (! $pdef instanceof AP5L_Acl_PermissionDefinition) {
            $pdef = &AP5L_Acl_PermissionDefinition::fetchByName(
                $store, $pdef, $permissionName, array('exists' => true)
            );
        }
        /*
         * Get applicable assets and requesters; deal with the trivial case of
         * no matches.
         */
        $assets = AP5L_Acl_Asset::search($store, $assetSection, $assetName);
        if (! count($assets)) {
            return null;
        }
        $requesters = AP5L_Acl_Requester::search(
            $store, $requesterSection, $requesterName
        );
        if (! count($requesters)) {
            return null;
        }
        /*
         * Get a list of all assets in the path.
         */
        $assetList = array();
        foreach ($assets as $asset) {
            $path = $asset -> getIDPath(false);
            foreach ($path as $assetID) {
                $assetList[$assetID] = $assetID;
            }
        }
        /*
         * Get a list of all requesters in the path.
         */
        $requesterList = array();
        foreach ($requesters as $requester) {
            $path = $requester -> getIDPath(false);
            foreach ($path as $requesterID) {
                $requesterList[$requesterID] = $requesterID;
            }
        }
        $hits = $store -> getPermissions(
            $assetList, $requesterList, $pdef -> getID()
        );
        if (! count($hits)) {
            return null;
        }
        /*
         * Arrange the results into a matrix by permission, asset, requester.
         * (use the permission so we can move this to a multiple-permission
         * process)
         */
        $matrix = array();
        foreach ($hits as $permission) {
            $aid = $permission -> getAssetID();
            $pdid = $permission -> getDefinitionID();
            $rid = $permission -> getRequesterID();
            if (! isset($matrix[$pdid])) {
                $matrix[$pdid] = array('values' => array());
            }
            if (! isset($matrix[$pdid][$aid])) {
                $matrix[$pdid][$aid] = array();
            }
            $val = $permission -> getValue();
            $matrix[$pdid][$aid][$rid] = $permission;
            $matrix[$pdid]['values'][$permission -> getValue()] = $permission;
            //echo 'pd=' . $pdid . ' a=' . $aid . ' r=' . $rid . ' v=' . $val . AP5L::LF;
        }
        /*
         * If there is only one value, then there is no conflict.
         */
        $pdid = $pdef -> getID();
        if (count($matrix[$pdid]['values']) == 1) {
            return reset($matrix[$pdid]['values']);
        }
        /*
         * We have a conflict. Path-reduce the permission matrix.
         */
        $resolved = null;
        $resolveTime = 0;
        foreach ($assets as $asset) {
            $aPath = array_reverse(array_values($asset -> getIDPath(false)));
            foreach ($requesters as $requester) {
                $rPath = array_reverse(array_values($requester -> getIDPath(false)));
                $leaf = self::_resolveLeaf($matrix[$pdid], $aPath, $rPath);
                if ($leaf && $leaf -> getEditTime() > $resolveTime) {
                    $resolved = $leaf;
                    $resolveTime = $resolved -> getEditTime();
                }
            }
        }
        if ($resolved) {
            $resolved -> setConflicted(true);
        }
        return $resolved;
    }

    /**
     * Get ACL permisson value.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string|AP5L_Acl_AssetSection Asset section object or asset section
     * name.
     * @param string Asset name.
     * @param string|AP5L_Acl_RequesterSection Requester section object or
     * requester section name.
     * @param string Requester name.
     * @param string|AP5L_Acl_PermissionDefinition|AP5L_Acl_PermissionSection
     * Permission definition object, Permission section object, or permission
     * section name.
     * @param string Permission name. Only required if a permission section was
     * provided.
     * @param array Options.
     * @return string The value of the requested permission
     */
    static function queryPermissionValue(
        $store, $assetSection, $assetName, $requesterSection, $requesterName,
        $pdef, $permissionName = ''
    ) {
        /*
         * Make sure the definition exists.
         */
        if (! $pdef instanceof AP5L_Acl_PermissionDefinition) {
            $pdef = &AP5L_Acl_PermissionDefinition::fetchByName(
                $store, $pdef, $permissionName, array('exists' => true)
            );
        }
        $permission = self::queryPermission(
            $store, $assetSection, $assetName, $requesterSection, $requesterName, $pdef
        );
        if (! $permission) {
            return $pdef -> getDefault();
        }
        return $permission -> getValue();
    }

    /**
     * Set a permission.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param AP5L_Acl_Asset|array Either the asset object for the permission or
     * an array of (asset section, asset path).
     * @param AP5L_Acl_Requester|array Either the requester object for the
     * permission or an array of (requester section, requester path).
     * @param AP5L_Acl_PermissionDefinition| array Either the object
     * defining permission to be added or an array of (permission section,
     * permission name).
     * @param string The permission value.
     * @param array Options.
     */
    static function set(
        &$store, $asset, $requester, $pdef, $permissionValue, $options = array()
    ) {
        /*
         * Validate eveything
         */
        if (is_array($asset)) {
            $asset = AP5L_Acl_Asset::fetchByPath(
                $store, $asset[0], $asset[1], array('exists' => true)
            );
        }
        if (is_array($requester)) {
            $requester = AP5L_Acl_Requester::fetchByPath(
                $store, $requester[0], $requester[1], array('exists' => true)
            );
        }
        if (is_array($pdef)) {
            $pdef = AP5L_Acl_PermissionDefinition::fetchByName(
                $store, $pdef[0], $pdef[1], array('exists' => true)
            );
        }
        $permission = self::factory($asset, $requester, $pdef, $permissionValue);
        if (isset($options['enabled'])) {
            $permission -> setEnabled($options['enabled']);
        }
        /*
         * Add or update the store, forcing insert.
         */
        $store -> put($permission);
    }

    function setConflicted($conflict) {
        $this -> _isConflicted = $conflict;
    }

    function setEnabled($enabled) {
        $this -> _isEnabled = $enabled;
    }

    function setID($id) {
    }

    function setName($name) {
        $name = trim($name);
        if ($name === '') {
            throw new AP5L_Acl_Exception(
                'Permission definition name must be non-blank.'
            );
        }
        $this -> _permissionName = $name;
    }

    function validate($input) {
        switch ($this -> _type) {
            case 'choice': {
                $found = false;
                if (isset($this -> _rules['case-sensitive']) && $this -> _rules['case-sensitive']) {
                    $found = in_array($input, $this -> _rules['choices']);
                    $valid = $input;
                } else {
                    $found = true;
                    foreach ($this -> _rules['choices'] as $value) {
                        if (! strcasecmp($input, $value)) {
                            $valid = $value;
                            $found = true;
                            break;
                        }
                    }
                }
                if (! $found) {
                    throw new AP5L_Acl_Exception(
                        'Invalid choice in ' . $this -> _permissionName . '.'
                    );
                }
            }
            break;

            case 'float':
            case 'int':
            case 'number': {
                if (! is_numeric($input)) {
                    throw new AP5L_Acl_Exception(
                        'Value is not a valid "' . $this -> _type . '".'
                    );
                }
                if ($this -> _type == 'int') {
                    $valid = (int) $input;
                } elseif ($this -> _type == 'float') {
                    $valid = (float) $input;
                } else {
                    $valid = $input;
                }
            }
            break;

            case 'text': {
                if (isset($this -> _rules['length-min'])
                    && strlen($input) < $this -> _rules['length-min']
                ) {
                    throw new AP5L_Acl_Exception(
                        'Value must be at least ' . $this -> _rules['length-min']
                        . ' characters in ' . $this -> _permissionName . '.'
                    );
                }
                if (isset($this -> _rules['length-max'])
                    && strlen($input) > $this -> _rules['length-max']
                ) {
                    throw new AP5L_Acl_Exception(
                        'Value must not exceed ' . $this -> _rules['length-max']
                        . ' characters in ' . $this -> _permissionName . '.'
                    );
                }
                $valid = $input;
            }
            break;

            default: {
                throw new AP5L_Acl_Exception(
                    'Corrupt permission definition: type "' . $this -> _type
                    . '" not valid in ' . $this -> _permissionName . '.'
                );
            }
            break;

        }
        return $valid;
    }

}
