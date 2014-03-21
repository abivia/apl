<?php
/**
 * Access control manager / API.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Manager.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Main class for access control. This class provides a simplified high level
 * interface to ACL capabilities.
 *
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_Manager {
    /**
     * Argument number being popped
     *
     * @var int
     */
    protected $_argCount;

    /**
     * Arguments passed to a command a part of a variable list
     *
     * @var array
     */
    protected $_args;

    /**
     * Data store for this connection.
     *
     * @var AP5L_Acl_Store
     */
    protected $_store;

    /**
     * Pop an argument off the call list.
     *
     * This function is used primarily to generate a meaningful message in the
     * case of an error.
     *
     * @param string The name of the expected argument.
     * @return mixed The argument value.
     * @throws AP5L_Acl_Exception If no more arguments are available.
     */
    protected function _argPop($name) {
        ++$this -> _argCount;
        if (empty($this -> _args)) {
            throw new AP5L_Acl_Exception(
                'Missing argument ' . $this -> _argCount . ' looking for ' . $name
            );
        }
        return array_shift($this -> _args);
    }

    /**
     * Add a new asset.
     *
     * Adds an asset to the ACL store. If no parent path is provided, a top
     * level asset is created.
     *
     * @param string Asset section name. Must be the name of an existing asset
     * section.
     * @param string|array Path to the new asset. A string is added at the
     * section root. Each element in the array names an asset in the provided
     * section.
     * @param array Options. Defined options are "create"(boolean), if set then
     * the entire asset path is created if it does not exist; if false, all but
     * the last element of the path must exist.
     * @return void
     */
    function assetAdd($sectionName, $assetPath, $options = array()) {
        return AP5L_Acl_Asset::add(
            $this -> _store, $sectionName, $assetPath, $options
        );
    }

    /**
     * Delete an asset.
     *
     * @param string Asset section name.
     * @param string|array Asset name or path.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function assetDelete($sectionName, $path, $options = array()) {
        AP5L_Acl_Asset::delete($this -> _store, $sectionName, $path, $options);
    }

    /**
     * Get an existing asset.
     *
     * @param string Asset section name.
     * @param string|array Asset name or path.
     * @param array Options: [exists]=boolean throws an exception if exists is
     * true and the object doesn't exist, or if exists is false and the object
     * does.
     * @return AP5L_Acl_Asset|boolean The asset object or false if not found and
     * no exception thrown.
     * @throws AP5L_Acl_Exception If the object's existance mismatches any
     * exists option setting.
     * @throws AP5L_Db_Exception On data store errors.
     *
     */
    function &assetGet($sectionName, $path, $options = array()) {
        return AP5L_Acl_Asset::fetchByPath($this -> _store, $sectionName, $path, $options);
    }

    /**
     * Get a list of assets within a path.
     *
     * @param string Asset section name.
     * @param string|array Optional asset name or path.
     * @param array Options.
     * @return void
     */
    function &assetListing($sectionName, $path = null, $options = array()) {
        return AP5L_Acl_Asset::listing($this -> _store, $sectionName, $path, $options);
    }

    /**
     * Merge an asset.
     *
     * @param string The section of the source asset.
     * @param array|string The asset path to merge from.
     * @param string The section of the target asset.
     * @param array|string The asset path to merge to.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &assetMerge(
        $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
    ) {
        return AP5L_Acl_Asset::merge(
            $this -> _store, $fromSectionName, $fromPath,
            $toSectionName, $toPath, $options
        );
    }

    /**
     * Move/rename an asset.
     *
     * @param string The section of the source asset.
     * @param array|string The asset path to move from.
     * @param string The section of the renamed asset.
     * @param array|string The asset path to move to.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &assetMove($fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()) {
        return AP5L_Acl_Asset::move(
            $this -> _store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options
        );
    }

    /**
     * Find all assets with matching section and name.
     *
     * @param string Asset section name.
     * @param string Asset name.
     * @param array Options.
     * @return array Assets.
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &assetSearch($sectionName, $assetName, $options = array()) {
        return AP5L_Acl_Asset::search($this -> _store, $sectionName, $assetName, $options);
    }

    /**
     * Add an asset section to the ACL store.
     *
     * @param string Asset section name. The section must not already exist.
     * @param array Options.
     * @return AP5L_Acl_AssetSection New asset section object.
     * @throws AP5L_Db_Exception On data store errors.
     */
    function assetSectionAdd($sectionName, $options = array()) {
        return AP5L_Acl_AssetSection::add($this -> _store, $sectionName, $options);
    }

    /**
     * Remove an asset section from the ACL store.
     *
     * @param string Asset section name.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function assetSectionDelete($sectionName, $options = array()) {
        return AP5L_Acl_AssetSection::delete($this -> _store, $sectionName, $options);
    }

    /**
     * Get an asset section from the ACL store.
     *
     * @param string Asset section name.
     * @param array Options: [exists]=boolean throws an exception if exists is
     * true and the object doesn't exist, or if exists is false and the object
     * does.
     * @return AP5L_Acl_AssetSection|boolean The asset section object or false
     * if not found and no exception thrown.
     * @throws AP5L_Acl_Exception If the object's existance mismatches any
     * exists option setting.
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &assetSectionGet($sectionName, $options = array()) {
        return AP5L_Acl_AssetSection::fetchByName($this -> _store, $sectionName, $options);
    }

    /**
     * Get a list of asset sections.
     *
     * @param array Options.
     * @return array Asset section names.
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &assetSectionListing($options = array()) {
        return AP5L_Acl_AssetSection::listing($this -> _store, $options);
    }

    /**
     * Merge an asset section into another.
     *
     * @param string Asset section name to merge from.
     * @param string Asset section name merge into.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function assetSectionMerge($fromSectionName, $toSectionName, $options = array()) {
        return AP5L_Acl_AssetSection::merge(
            $this -> _store, $fromSectionName, $toSectionName, $options
        );
    }

    /**
     * Rename an asset section.
     *
     * @param string Existing asset section name.
     * @param string New asset section name.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function assetSectionRename($oldSectionName, $newSectionName, $options = array()) {
        $options['name'] = $newSectionName;
        return AP5L_Acl_AssetSection::update($this -> _store, $oldSectionName, $options);
    }

    /**
     * Update an asset section.
     *
     * @param string Existing asset section name.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function assetSectionUpdate($sectionName, $options = array()) {
        return AP5L_Acl_AssetSection::update($this -> _store, $sectionName, $options);
    }

    /**
     * Connect to a data store.
     *
     * @param string Data source name.
     * @param array Options.
     * @return void
     * @throws AP5L_Db_Exception On data store errors.
     */
    function connect($dsn, $options = array()) {
        $this -> _store = &AP5L_Acl_Store::factory($dsn, $options);
        if (isset($options['prefix'])) {
            $this -> _store -> setPrefix($options['prefix']);
        }
    }

    /**
     * Disconnect from a data store.
     *
     * @return void
     */
    function disconnect() {
        if ($this -> _store) {
            $this -> _store -> disconnect();
        }
    }

    /**
     * Add a domain to the ACL store and select it.
     *
     * @param string Domain name. The domain must not already exist.
     * @param string Domain access password.
     * @param array Options.
     * @return void
     */
    function domainAdd($domainName, $password, $options = array()) {
        $result = $this -> _store -> get(
            'AP5L_Acl_Domain',
            array('_domainName' => $domainName),
            array('first' => true)
        );
        if ($result) {
            throw new AP5L_Acl_Exception(
                'Cannot add domain "' . $domainName . '", already exists.'
            );
        }
        $domain = AP5L_Acl_Domain::factory($domainName, $password);
        $this -> _store -> put($domain);
        $this -> domainSelect($domainName, $password);
    }

    /**
     * Remove an asset section from the ACL store.
     *
     * @param string Domain name. The domain must not already exist.
     * @param string Domain access password.
     * @param array Options.
     * @return void
     */
    function domainDelete($domainName, $password, $options = array()) {
        $result = $this -> _store -> get(
            'AP5L_Acl_Domain',
            array('_domainName' => $domainName),
            array('first' => true)
        );
    }

    /**
     * Get a list of domains.
     *
     * @param array Options.
     * @return array Domain names.
     */
    function domainListing($options = array()) {
    }

    /**
     * Rename a domain.
     *
     * @param string Existing domain name.
     * @param string Domain access password.
     * @param string New domain name.
     * @param array Options.
     * @return void
     */
    function domainRename($oldDomainName, $password, $newDomainName, $options = array()) {
    }

    /**
     * Select the working domain.
     *
     * @param string Domain name. The domain must exist.
     * @param string Domain access password.
     * @return void
     */
    function domainSelect($domainName, $password) {
        $domain = $this -> _store -> get(
            'AP5L_Acl_Domain',
            array('_domainName' => $domainName),
            array('first' => true)
        );
        if (! ($domain && $domain -> checkPassword($password))) {
            throw new AP5L_Acl_Exception('Domain "' . $domainName . ' not found.');
        }
        $this -> _store -> setDomain($domain);
    }

    /**
     * Install into a data store.
     *
     * @param string The version to install. Optional.
     * @param array Options.
     * @return void
     */
    function install($version = '', $options = array()) {
        $this -> _store -> install($version, '', $options);
    }

    /**
     * Add a permission definition to the ACL store.
     *
     * @param string Permission section name. The section must exist.
     * @param AP5L_Acl_PermissionDefinition The permission definition object.
     * @param array Options.
     * @return void
     */
    function permissionDefinitionAdd($sectionName, &$pdef, $options = array()) {
        return AP5L_Acl_PermissionDefinition::add(
            $this -> _store, $sectionName, $pdef, $options
        );
    }

    /**
     * Remove a permission definition from the ACL store.
     *
     * @param string Permission section name. The section must exist.
     * @param string The name of the permission definition object to delete.
     * @param array Options.
     * @return void
     */
    function permissionDefinitionDelete($sectionName, $pdefName, $options = array()) {
        return AP5L_Acl_PermissionDefinition::delete(
            $this -> _store, $sectionName, $pdefName, $options
        );
    }

    /**
     * Get a permission definition from the ACL store.
     *
     * @param string Permission section name.
     * @param string Permission definition name.
     * @param array Options: [exists]=boolean throws an exception if exists is
     * true and the object doesn't exist, or if exists is false and the object
     * does.
     * @return AP5L_Acl_PermissionDefinition|boolean The permission section
     * object or false if not found and no exception thrown.
     * @throws AP5L_Acl_Exception If the object's existance mismatches any
     * exists option setting.
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &permissionDefinitionGet($sectionName, $pdefName, $options = array()) {
        return AP5L_Acl_PermissionDefinition::fetchByName(
            $this -> _store, $sectionName, $pdefName, $options
        );
    }

    /**
     * Get a list of permission definitions.
     *
     * @param array Options.
     * @return array Permission definitions.
     */
    function &permissionDefinitionListing($options = array()) {
        return AP5L_Acl_PermissionDefinition::listing($this -> _store, $options);
    }

    /**
     * Remove a permission from the ACL store.
     *
     * @param string Asset section name.
     * @param string|array Asset path.
     * @param string Requester section name.
     * @param string|array Requester path.
     * @param string Permission section name.
     * @param string Permission name.
     * @param array Options.
     * @return void
     */
    function permissionDelete(
        $assetSection, $assetPath,
        $requesterSection, $requesterPath,
        $permissionSection, $permissionName,
        $options = array()
    ) {
        return AP5L_Acl_Permission::delete(
            $this -> _store,
            $assetSection, $assetPath,
            $requesterSection, $requesterPath,
            $permissionSection, $permissionName,
            $options
        );
    }

    /**
     * List permissions
     *
     * @param string Asset section name.
     * @param string|array Asset path.
     * @param string Requester section name.
     * @param string|array Requester path.
     * @param string Permission section name. Optional.
     * @param array Options.
     * @return array Permission values indexed by section, name.
     */
    function permissionListing(
        $assetSection, $assetPath,
        $requesterSection, $requesterPath,
        $permissionSection = null,
        $options = array()
    ) {
        return AP5L_Acl_Permission::listing(
            $this -> _store,
            $assetSection, $assetPath,
            $requesterSection, $requesterPath,
            $permissionSection,
            $options
        );
    }

    /**
     * Add a permission section to the ACL store.
     *
     * @param string Permission section name. The section must not already exist.
     * @param array Options.
     * @return void
     */
    function permissionSectionAdd($sectionName, $options = array()) {
        return AP5L_Acl_PermissionSection::add($this -> _store, $sectionName, $options);
    }

    /**
     * Remove a permission section from the ACL store.
     *
     * @param string Permission section name.
     * @param array Options.
     * @return void
     */
    function permissionSectionDelete($sectionName, $options = array()) {
        return AP5L_Acl_PermissionSection::delete($this -> _store, $sectionName, $options);
    }

    /**
     * Get a permission section from the ACL store.
     *
     * @param string Permission section name.
     * @param array Options: [exists]=boolean throws an exception if exists is
     * true and the object doesn't exist, or if exists is false and the object
     * does.
     * @return AP5L_Acl_PermissionSection|boolean The permission section object
     * or false if not found and no exception thrown.
     * @throws AP5L_Acl_Exception If the object's existance mismatches any
     * exists option setting.
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &permissionSectionGet($sectionName, $options = array()) {
        return AP5L_Acl_PermissionSection::fetchByName($this -> _store, $sectionName, $options);
    }

    /**
     * Get a list of permission sections.
     *
     * @param array Options.
     * @return array Permission sections.
     */
    function &permissionSectionListing($options = array()) {
        return AP5L_Acl_PermissionSection::listing($this -> _store, $options);
    }

    /**
     * Merge a permission section into another.
     *
     * @param string Permission section name to merge from.
     * @param string Permission section name merge into.
     * @param array Options.
     * @return void
     */
    function permissionSectionMerge($fromSectionName, $toSectionName, $options = array()) {
        return AP5L_Acl_PermissionSection::merge(
            $this -> _store, $fromSectionName, $toSectionName, $options
        );
    }

    /**
     * Rename a permission section.
     *
     * @param string Existing permission section name.
     * @param string New permission section name.
     * @param array Options.
     * @return void
     */
    function permissionSectionRename($oldSectionName, $newSectionName, $options = array()) {
        $options['name'] = $newSectionName;
        return AP5L_Acl_PermissionSection::update($this -> _store, $oldSectionName, $options);
    }

    /**
     * Update a permission section.
     *
     * @param string Existing permission section name.
     * @param array Options.
     * @return void
     */
    function permissionSectionUpdate($sectionName, $options = array()) {
        return AP5L_Acl_PermissionSection::update($this -> _store, $sectionName, $options);
    }

    /**
     * Set a permission in the ACL store.
     *
     * @param string|AP5L_Acl_Asset|AP5L_Acl_AssetSection Asset section object,
     * Asset object or asset section name.
     * @param string|array Asset path. Only required if an asset section was
     * provided.
     * @param string|AP5L_Acl_Requester|AP5L_Acl_RequesterSection Requester
     * section object, Requester object, or requester section name.
     * @param string|array Requester path. Only required if a requester section
     * was provided
     * @param string|AP5L_Acl_Permission|AP5L_Acl_PermissionSection Permission
     * object, Permission section object, or permission section name.
     * @param string Permission name. Only required if a permission section was
     * provided.
     * @param string Permission value.
     * @param array Options.
     * @return void
     */
    function permissionSet() {
        $this -> _args = func_get_args();
        $this -> _argCount = 0;
        $pop = $this -> _argPop('Asset/Section');
        if ($pop instanceof AP5L_Acl_Asset) {
            $asset = $pop;
        } else {
            $asset = array($pop, $this -> _argPop('Asset'));
        }
        $pop = $this -> _argPop('Requester/Section');
        if ($pop instanceof AP5L_Acl_Requester) {
            $requester = $pop;
        } else {
            $requester = array($pop, $this -> _argPop('Requester'));
        }
        $pop = $pop = $this -> _argPop('Permission Definition');
        if ($pop instanceof AP5L_Acl_PermissionDefinition) {
            $pdef = $pop;
        } else {
            $pdef = array($pop, $this -> _argPop('Permission'));
        }
        $permissionValue = $this -> _argPop('Permission Value');
        if (count($this -> _args)) {
            $options = $this -> _args[0];
        } else {
            $options = array();
        }
        return AP5L_Acl_Permission::set(
            $this -> _store, $asset, $requester, $pdef, $permissionValue, $options
        );
    }

    /**
     * Get permission setting from the ACL.
     *
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
     * @return AP5L_Acl_Permisson|null The permission object, if found. Null if
     * not.
     */
    function queryPermission(
        $assetSection, $assetName, $requesterSection, $requesterName, $pdef,
        $permissionName = ''
    ) {
        return AP5L_Acl_Permission::queryPermission(
            $this -> _store,
            $assetSection, $assetName,
            $requesterSection, $requesterName,
            $pdef, $permissionName
        );
    }

    /**
     * Check the ACL for permisson.
     *
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
     * @return string The value of the requested permission
     */
    function queryPermissionValue(
        $assetSection, $assetName, $requesterSection, $requesterName, $pdef,
        $permissionName = ''
    ) {
        return AP5L_Acl_Permission::queryPermissionValue(
            $this -> _store,
            $assetSection, $assetName,
            $requesterSection, $requesterName,
            $pdef, $permissionName
        );
    }

    /**
     * Add a new requester.
     *
     * Adds a requester to the ACL store. If no parent path is provided, a top
     * level requester is created.
     *
     * @param string Requester section name. Must be the name of an existing requester
     * section.
     * @param string The new requester name.
     * @param array Options. Defined options are "create"(boolean), if set then
     * the requester path is created if it does not exist.
     * @return void
     */
    function requesterAdd($sectionName, $requesterName, $options = array()) {
        return AP5L_Acl_Requester::add(
            $this -> _store, $sectionName, $requesterName, $options
        );
    }

    /**
     * Get an existing requester.
     *
     * @param string Requester section name.
     * @param string|array Requester name or path.
     * @param array Options: [exists]=boolean throws an exception if exists is
     * true and the object doesn't exist, or if exists is false and the object
     * does.
     * @return AP5L_Acl_Asset|boolean The requester object or false if not found
     * and no exception thrown.
     * @throws AP5L_Acl_Exception If the object's existance mismatches any
     * exists option setting.
     * @throws AP5L_Db_Exception On data store errors.
     *
     */
    function &requesterGet($sectionName, $path, $options = array()) {
        return AP5L_Acl_Requester::fetchByPath($this -> _store, $sectionName, $path, $options);
    }

    /**
     * Delete a requester.
     *
     * @param string Requester section name.
     * @param string|array Requester name or path.
     * @param array Options.
     * @return void
     */
    function &requesterDelete($sectionName, $path, $options = array()) {
        return AP5L_Acl_Requester::delete($this -> _store, $sectionName, $path, $options);
    }

    /**
     * Get a list of requesters.
     *
     * @param string The section of the requester.
     * @param array|string The requester path to list.
     * @param array Options.
     * @return array Requesters.
     */
    function &requesterListing($sectionName, $path = null, $options = array()) {
        return AP5L_Acl_Requester::listing($this -> _store, $sectionName, $path, $options);
    }

    /**
     * Merge a requester.
     *
     * @param string The section of the source requester.
     * @param array|string The requester path to merge from.
     * @param string The section of the target requester.
     * @param array|string The requester path to merge to.
     * @param array Options.
     * @return void
     */
    function &requesterMerge(
        $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
    ) {
        return AP5L_Acl_Requester::merge(
            $this -> _store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options
        );
    }

    /**
     * Move a requester.
     *
     * @param string The section of the source requester.
     * @param array|string The requester path to move from.
     * @param string The section of the renamed requester.
     * @param array|string The requester path to move to.
     * @param array Options.
     * @return void
     */
    function &requesterMove(
        $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
    ) {
        return AP5L_Acl_Requester::move(
            $this -> _store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options
        );
    }

    /**
     * Find all requesters with matching section and name.
     *
     * @param string The requested section.
     * @param array|string The requester name to list.
     * @param array Options.
     * @return array Matching AP5L_Acl_Requester objects.
     */
    function &requesterSearch($sectionName, $requesterName, $options = array()) {
        return AP5L_Acl_Requester::search(
            $this -> _store, $sectionName, $requesterName, $options
        );
    }

    /**
     * Add a requester section to the ACL store.
     *
     * @param string Requester section name. The section must not already exist.
     * @param array Options.
     * @return void
     */
    function requesterSectionAdd($sectionName, $options = array()) {
        return AP5L_Acl_RequesterSection::add($this -> _store, $sectionName, $options);
    }

    /**
     * Remove a requester section from the ACL store.
     *
     * @param string Requester section name.
     * @param array Options.
     * @return void
     */
    function requesterSectionDelete($sectionName, $options = array()) {
        return AP5L_Acl_RequesterSection::delete($this -> _store, $sectionName, $options);
    }

    /**
     * Get a requester section from the ACL store.
     *
     * @param string Requester section name.
     * @param array Options: [exists]=boolean throws an exception if exists is
     * true and the object doesn't exist, or if exists is false and the object
     * does.
     * @return AP5L_Acl_RequesterSection|boolean The requester section object or
     * false if not found and no exception thrown.
     * @throws AP5L_Acl_Exception If the object's existance mismatches any
     * exists option setting.
     * @throws AP5L_Db_Exception On data store errors.
     */
    function &requesterSectionGet($sectionName, $options = array()) {
        return AP5L_Acl_RequesterSection::fetchByName($this -> _store, $sectionName, $options);
    }

    /**
     * Get a list of requester sections.
     *
     * @param array Options.
     * @return array Requester section names.
     */
    function &requesterSectionListing($options = array()) {
        return AP5L_Acl_RequesterSection::listing($this -> _store, $options);
    }

    /**
     * Merge a requester section into another.
     *
     * @param string Requester section name to merge from.
     * @param string Requester section name merge into.
     * @param array Options.
     * @return void
     */
    function requesterSectionMerge($fromSectionName, $toSectionName, $options = array()) {
        return AP5L_Acl_RequesterSection::merge(
            $this -> _store, $fromSectionName, $toSectionName, $options
        );
    }

    /**
     * Rename a requester section.
     *
     * @param string Existing requester section name.
     * @param string New requester section name.
     * @param array Options.
     * @return void
     */
    function requesterSectionRename($oldSectionName, $newSectionName, $options = array()) {
        $options['name'] = $newSectionName;
        return AP5L_Acl_RequesterSection::update($this -> _store, $oldSectionName, $options);
    }

    /**
     * Update a requester section.
     *
     * @param string Existing requester section name.
     * @param array Options.
     * @return void
     */
    function requesterSectionUpdate($sectionName, $options = array()) {
        return AP5L_Acl_RequesterSection::update($this -> _store, $sectionName, $options);
    }

    /**
     * Set the data store.
     *
     * @param AP5L_Acl_Store The data store object.
     * @return void
     */
    function setStore(&$store) {
        $this -> _store = $store;
    }

}
