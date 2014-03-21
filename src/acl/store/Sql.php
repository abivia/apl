<?php
/**
 * Abstract support for SQL based data stores.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Sql.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Generic SQL ACL Storage manager.
 *
 * This class provides SQL schema level information for use by derived classes.
 * Some of these classes may change or replace these tables to meet their
 * implementation requirements.
 *
 * TODO: Make this a little(or even a lot) more generic
 * 
 * @package AP5L
 * @subpackage Acl
 */
abstract class AP5L_Acl_Store_Sql extends AP5L_Acl_Store {
    /**
     * Mapping of class names to database tables. Indexed by class name.
     *
     * @var array
     */
    static protected $_classToBaseTable = array(
        'AP5L_Acl_Asset' => 'asset',
        'AP5L_Acl_AssetSection' => 'assetsection',
        'AP5L_Acl_Domain' => 'domain',
        'AP5L_Acl_Permission' => 'permission',
        'AP5L_Acl_PermissionDefinition' => 'permissiondef',
        'AP5L_Acl_PermissionSection' => 'permissionsection',
        'AP5L_Acl_Requester' => 'requester',
        'AP5L_Acl_RequesterSection' => 'requestersection',
    );

    /**
     * Mapping of class names to prefixed database tables. Indexed by class
     * name.
     *
     * @var array
     */
    protected $_classToTable = null;

    /**
     * Mapping of database columns to class members. Indexed by class name, each
     * element is an array of member names, indexed by column name.
     *
     * @var array
     */
    static protected $_colToMember = array(
        'AP5L_Acl_Asset' => array(
            'assetID' => '_assetID',
            'assetName' => '_assetName',
            'assetSectionID' => '_assetSectionID',
            'domainID' => '_domainID',
            'info' => '_info',
            'isHidden' => '_isHidden',
            'parentID' => '_parentID',
        ),
        'AP5L_Acl_AssetSection' => array(
            'assetSectionID' => '_assetSectionID',
            'assetSectionName' => '_assetSectionName',
            'domainID' => '_domainID',
            'info' => '_info',
            'isHidden' => '_isHidden',
        ),
        'AP5L_Acl_Domain' => array(
            'domainID' => '_domainID',
            'domainName' => '_domainName',
            'info' => '_info',
            'isHidden' => '_isHidden',
            'passHash' => '_passHash',
            'passMD5' => '_passMD5',
        ),
        'AP5L_Acl_Permission' => array(
            'assetID' => '_assetID',
            'domainID' => '_domainID',
            'editTime' => '_editTime',
            'requesterID' => '_requesterID',
            'info' => '_info',
            'isEnabled' => '_isEnabled',
            'isHidden' => '_isHidden',
            'permissionDefID' => '_permissionDefinitionID',
            'permissionValue' => '_permissionValue',
        ),
        'AP5L_Acl_PermissionDefinition' => array(
            'domainID' => '_domainID',
            'info' => '_info',
            'isHidden' => '_isHidden',
            'permissionDefault' => '_default',
            'permissionDefID' => '_permissionDefinitionID',
            'permissionName' => '_permissionName',
            'permissionRules' => '_rules',
            'permissionSectionID' => '_permissionSectionID',
            'permissionType' => '_type',
        ),
        'AP5L_Acl_PermissionSection' => array(
            'domainID' => '_domainID',
            'info' => '_info',
            'isHidden' => '_isHidden',
            'permissionSectionID' => '_permissionSectionID',
            'permissionSectionName' => '_permissionSectionName',
        ),
        'AP5L_Acl_Requester' => array(
            'domainID' => '_domainID',
            'info' => '_info',
            'isHidden' => '_isHidden',
            'parentID' => '_parentID',
            'requesterID' => '_requesterID',
            'requesterName' => '_requesterName',
            'requesterSectionID' => '_requesterSectionID',
        ),
        'AP5L_Acl_RequesterSection' => array(
            'domainID' => '_domainID',
            'info' => '_info',
            'isHidden' => '_isHidden',
            'requesterSectionID' => '_requesterSectionID',
            'requesterSectionName' => '_requesterSectionName',
        ),
    );

    /**
     * Mapping of database columns to data types. Indexed by class name, each
     * element is an array of types, indexed by column name.
     *
     * @var array
     */
    static protected $_colType = array(
        'AP5L_Acl_Asset' => array(
            'assetID' => 'auto',
            'assetName' => 'string',
            'assetSectionID' => 'integer',
            'domainID' => 'integer',
            'info' => 'text',
            'isHidden' => 'flag',
            'parentID' => 'integer',
        ),
        'AP5L_Acl_AssetSection' => array(
            'assetSectionID' => 'auto',
            'assetSectionName' => 'string',
            'domainID' => 'integer',
            'info' => 'text',
            'isHidden' => 'flag',
        ),
        'AP5L_Acl_Domain' => array(
            'domainID' => 'auto',
            'domainName' => 'string',
            'info' => 'text',
            'isHidden' => 'flag',
            'passHash' => 'string',
            'passMD5' => 'string',
        ),
        'AP5L_Acl_Permission' => array(
            'assetID' => 'integer',
            'domainID' => 'integer',
            'editTime' => 'utick_auto',
            'requesterID' => 'integer',
            'info' => 'text',
            'isEnabled' => 'flag',
            'isHidden' => 'flag',
            'permissionDefID' => 'integer',
            'permissionValue' => 'text',
        ),
        'AP5L_Acl_PermissionDefinition' => array(
            'domainID' => 'integer',
            'info' => 'text',
            'isHidden' => 'flag',
            'permissionDefault' => 'text',
            'permissionDefID' => 'auto',
            'permissionName' => 'text',
            'permissionRules' => 'json',
            'permissionSectionID' => 'integer',
            'permissionType' => 'text',
        ),
        'AP5L_Acl_PermissionSection' => array(
            'permissionSectionID' => 'auto',
            'permissionSectionName' => 'string',
            'domainID' => 'integer',
            'info' => 'text',
            'isHidden' => 'flag',
        ),
        'AP5L_Acl_Requester' => array(
            'domainID' => 'integer',
            'info' => 'text',
            'isHidden' => 'flag',
            'parentID' => 'integer',
            'requesterID' => 'auto',
            'requesterName' => 'text',
            'requesterSectionID' => 'integer',
        ),
        'AP5L_Acl_RequesterSection' => array(
            'requesterSectionID' => 'auto',
            'requesterSectionName' => 'string',
            'domainID' => 'integer',
            'info' => 'text',
            'isHidden' => 'flag',
        ),
    );

    /**
     * This is the reverse of _colToMember. It is computed automatically when
     * the first instance is constructed.
     */
    static protected $_memberToCol = null;

    /**
     * A prefix for tables/whatever in the data store.
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * Parent key columns for tree classes
     * 
     * @var array
     */
    static protected $_parentCol = array(
        'AP5L_Acl_Asset' => 'parentID',
        'AP5L_Acl_Requester' => 'parentID',
    );

    /**
     * Primary key columns for each object class
     * 
     * @var array
     */
    static protected $_primaryKey = array(
        'AP5L_Acl_Asset' => array('assetID',),
        'AP5L_Acl_AssetSection' => array('assetSectionID',),
        'AP5L_Acl_Domain' => array('domainID',),
        'AP5L_Acl_Permission' => array('assetID', 'requesterID', 'permissionDefID',),
        'AP5L_Acl_PermissionDefinition' => array('permissionDefID',),
        'AP5L_Acl_PermissionSection' => array('permissionSectionID',),
        'AP5L_Acl_Requester' => array('requesterID',),
        'AP5L_Acl_RequesterSection' => array('requesterSectionID',),
    );

    /**
     * Table definitions. Uses a shorthand definition that assumes many
     * attributes, for example that integers are unsigned and that nulls are not
     * allowed.
     *
     * @var array
     */
    static protected $_schema = array(
        '1.00' => array(
            'asset' => array(
                'cols' => array(
                    'assetID' => array('type' => 'int', 'size' => 10, 'auto' => true),
                    'domainID' => array('type' => 'int', 'size' => 10),
                    'parentID' => array('type' => 'int', 'size' => 10),
                    'assetSectionID' => array('type' => 'int', 'size' => 10),
                    'assetName' => array('type' => 'varchar', 'size' => 255),
                    'info' => array('type' => 'text'),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                ),
                'keys' => array(
                    'PRIMARY' => array('assetID'),
                    'domain_section_name' => array(
                        'domainID', 'assetSectionID', 'assetName'
                    )
                ),
                'comment' => 'An asset is something to which access can be requested',
            ),
            'assetsection' => array(
                'cols' => array(
                    'assetSectionID' => array(
                        'type' => 'int', 'size' => 10, 'auto' => true
                    ),
                    'domainID' => array('type' => 'int', 'size' => 10),
                    'assetSectionName' => array('type' => 'varchar', 'size' => 255),
                    'info' => array('type' => 'text'),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                ),
                'keys' => array(
                    'PRIMARY' => array('assetSectionID'),
                    '!domain_name' => array(
                        'domainID', 'assetSectionName',
                    )
                ),
                'comment' => 'Asset sections provide namespace-style asset organization.',
            ),
            'domain' => array(
                'cols' => array(
                    'domainID' => array('type' => 'int', 'size' => 10, 'auto' => true),
                    'domainName' => array('type' => 'varchar', 'size' => 255),
                    'passMD5' => array(
                        'type' => 'varchar', 'size' => 32,
                        'comment' => 'Encrypted password'
                    ),
                    'passHash' => array(
                        'type' => 'varchar', 'size' => 32,
                        'comment' => 'Appended to password to thwart brute force MD5 cracks'
                    ),
                    'info' => array('type' => 'text'),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                ),
                'keys' => array(
                    'PRIMARY' => array('domainID'),
                    '!domain_name' => array(
                        'domainName',
                    )
                ),
                'comment' => 'Domains are completely separate ACL spaces.',
            ),
            'permission' => array(
                'cols' => array(
                    'domainID' => array('type' => 'int', 'size' => 10),
                    'assetID' => array('type' => 'int', 'size' => 10),
                    'requesterID' => array('type' => 'int', 'size' => 10),
                    'permissionDefID' => array('type' => 'int', 'size' => 10),
                    'editTime' => array(
                        'type' => 'varchar', 'size' => 20,
                        'comment' => 'Unix base GMT timestamp w/usec, zero padded.'
                    ),
                    'info' => array('type' => 'text'),
                    'isEnabled' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => ''
                    ),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                    'permissionValue' => array('type' => 'varchar', 'size' => 255),
                ),
                'keys' => array(
                    'PRIMARY' => array(
                        'domainID', 'assetID', 'requesterID', 'permissionDefID'
                    ),
                ),
                'comment' => 'A permission is a value given to a request for an asset.',
            ),
            'permissiondef' => array(
                'cols' => array(
                    'permissionDefID' => array(
                        'type' => 'int', 'size' => 10, 'auto' => true
                    ),
                    'domainID' => array('type' => 'int', 'size' => 10),
                    'permissionSectionID' => array('type' => 'int', 'size' => 10),
                    'permissionName' => array('type' => 'varchar', 'size' => 255),
                    'permissionType' => array(
                        'type' => 'varchar', 'size' => 32,
                        'comment' => 'One of: choice, int, float, number, text'
                    ),
                    'permissionDefault' => array('type' => 'varchar', 'size' => 255),
                    'permissionRules' => array(
                        'type' => 'text',
                        'comment' => 'Validation rules, JSON encoded.'
                    ),
                    'info' => array('type' => 'text'),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                ),
                'keys' => array(
                    'PRIMARY' => array('permissionDefID'),
                    '!domain_section_name' => array(
                        'domainID', 'permissionSectionID', 'permissionName',
                    ),
                ),
                'comment' => 'Defines permission attributes.',
            ),
            'permissionsection' => array(
                'cols' => array(
                    'permissionSectionID' => array(
                        'type' => 'int', 'size' => 10, 'auto' => true
                    ),
                    'domainID' => array('type' => 'int', 'size' => 10),
                    'permissionSectionName' => array('type' => 'varchar', 'size' => 255),
                    'info' => array('type' => 'text'),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                ),
                'keys' => array(
                    'PRIMARY' => array('permissionSectionID'),
                    '!domain_section_name' => array(
                        'domainID', 'permissionSectionName',
                    ),
                ),
                'comment' => 'Permission sections provide namespace-style organization.',
            ),
            'requester' => array(
                'cols' => array(
                    'requesterID' => array(
                        'type' => 'int', 'size' => 10, 'auto' => true
                    ),
                    'domainID' => array('type' => 'int', 'size' => 10),
                    'parentID' => array('type' => 'int', 'size' => 10),
                    'requesterSectionID' => array('type' => 'int', 'size' => 10),
                    'requesterName' => array('type' => 'varchar', 'size' => 255),
                    'info' => array('type' => 'text'),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                ),
                'keys' => array(
                    'PRIMARY' => array('requesterID'),
                    '!domain_parent_section_name' => array(
                        'domainID', 'parentID', 'requesterSectionID', 'requesterName',
                    ),
                    'domain_section_name' => array(
                        'domainID', 'requesterSectionID', 'requesterName',
                    ),
                ),
                'comment' => 'A requester is an entity asking for access to an asset.',
            ),
            'requestersection' => array(
                'cols' => array(
                    'requesterSectionID' => array(
                        'type' => 'int', 'size' => 10, 'auto' => true
                    ),
                    'domainID' => array('type' => 'int', 'size' => 10),
                    'requesterSectionName' => array('type' => 'varchar', 'size' => 255),
                    'info' => array('type' => 'text'),
                    'isHidden' => array(
                        'type' => 'varchar', 'size' => 1,
                        'comment' => 'When set (T), object is hidden from UI'
                    ),
                ),
                'keys' => array(
                    'PRIMARY' => array('requesterSectionID'),
                    '!domain_name' => array(
                        'domainID', 'requesterSectionName',
                    ),
                ),
                'comment' => 'Requester sections provide namespace-style asset organization.',
            ),
        ),
    );

    /**
     * Database version transformations. Also rather brute force: index by
     * new version, old version, to get a list of transform instructions.
     *
     * @var array
     */
    static protected $_schemaUpdate = array(
    );

    /**
     * Class constructor.
     * 
     * On the first object creation, this method builds class-static lookup
     * arrays.
     */
    function __construct() {
        $this -> setPrefix('');
        if (! is_array(self::$_memberToCol)) {
            self::$_memberToCol = array();
            foreach (self::$_colToMember as $className => $map) {
                self::$_memberToCol[$className] = array_flip($map);
            }
        }
    }

    /**
     * Create a table and indicies.
     *
     * @param string The base table name (without any prefix).
     * @param array Table definition from the $_schema table.
     * @throws AP5L_Db_Exception
     */
    abstract protected function _createTable($baseTable, $tableDef, $options = array());

    /**
     * Hook for install tear-down
     */
    protected function _onAfterInstall() {
    }

    /**
     * Hook for install set-up
     */
    protected function _onBeforeInstall() {
    }

    /**
     * Apply a list of schema changes to the database.
     *
     * @param array List of change instructions.
     */
    abstract protected function _schemaChange($list);

    /**
     * Record the schema version in the database
     *
     * @param string Version identifier
     * @throws AP5L_Db_Exception
     */
    abstract protected function _setSchemaVersion($version);

    /**
     * Table truncation.
     *
     * @param string The name of the table to be emptied.
     */
    abstract protected function _truncate($table);

    /**
     * Get the schema version from the database.
     *
     * @return string|boolean Version number if any, false if none defined.
     */
    abstract function getSchemaVersion();

    /**
     * Prepare data store.
     *
     * Install performs any operations required to prepare the data store. The
     * install method is also responsible for managing upgrades to the data
     * store.
     *
     * @param string The version of the data store to install. Optional.
     * @param string The version of an existing data store, ignored.
     * @param array Options. Possible options include:
     * <ul><li>charset - string. Default character set. UTF-8 if not specified.
     * </li><li>purge - boolean. If true, any existing data is removed.
     * </li></ul>
     * @throws AP5L_Db_Exception On any failure.
     */
    function install($version = '', $dummy = '', $options = array()) {
        $this -> _onBeforeInstall();
        if ($version === '') {
            $version = max(array_keys(self::$_schema));
        }
        $oldVersion = $this -> getSchemaVersion();
        if ($oldVersion) {
            /*
             * First purge if requested
             */
            if (isset(self::$_schema[$oldVersion])) {
                if (isset($options['purge']) && $options['purge']) {
                    foreach (array_keys(self::$_schema[$oldVersion]) as $table) {
                        $this -> _truncate($table);
                    }
                }
            }
            /*
             * Now find and execute upgrade steps
             *
             * TODO: The _schemaUpdate table will have more abstract
             * instructions rather than specific queries. (add column; update
             * column, expression; etc.) So this code needs to use abstract
             * methods that implement the instructions.
             */
            if (isset(self::$_schemaUpdate[$version][$oldVersion])) {
                foreach (self::$_schemaUpdate[$version][$oldVersion] as $list) {
                    $this -> _schemaChange($list);
                }
            }
        } else {
            /*
             * Translate our table defs into MDB2 create table requests
             */
            foreach (self::$_schema[$version] as $baseTable => $tableDef) {
                $this -> _createTable($baseTable, $tableDef, $options);
            }
        }
        /*
         * Finish by writing the schema version in a place that won't be used by
         * normal operations.
         */
        $this -> _setSchemaVersion($version);
        $this -> _onAfterInstall();
    }

    /**
     * Set the table prefix.
     *
     * This method also builds the class to table mapping array.
     *
     * @param string Prefix to use when referencing database tables.
     */
    function setPrefix($prefix) {
        $this -> _prefix = $prefix;
        $this -> _classToTable = array();
        foreach (self::$_classToBaseTable as $className => $table) {
            $this -> _classToTable[$className] = $prefix . $table;
        }
    }

}
