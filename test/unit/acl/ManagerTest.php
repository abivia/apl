<?php
/**
 * Unit tests for the ACL manager
 *
 * @package AP5L
 * @subpackage Acl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: ManagerTest.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call ManagerTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ManagerTest::main');
}

/*
 * Find the library root and install AP5L.
 */
$path = dirname(__FILE__);
while ($path) {
    $file = $path . DIRECTORY_SEPARATOR . 'AP5L.php';
    //echo 'path: ' . $file . chr(10);
    if (file_exists($file)) {
        include $file;
        AP5L::install();
        break;
    }
    if ($path == dirname($path)) break;
    $path = dirname($path);
}
if (! class_exists('AP5L', false)) {
    throw new Exception('Unable to find AP5L.php in ' . dirname(__FILE__));
}

require_once 'PHPUnit/Framework.php';
require_once 'MDB2.php';

/**
 * Test class for ACL Manager.
 */
class ManagerTest extends PHPUnit_Framework_TestCase {
    const DSN_MYSQL = 'mysql://ap5l_acl:ap5l_acl@localhost/ap5l_acl';
    /**
     * Main object for testing.
     *
     * @var AP5L_Acl_Manager
     */
    public $fixture;

    /**
     * Debug: label of the curret test/operation
     *
     * @var string
     */
    public $phase;

    function __construct() {
        parent::__construct();
        $this -> backupGlobals = false;
    }

    /**
     * Connect to the data store and wipe it clean.
     */
    protected function _connect($dsn = '') {
        if (! $dsn) {
            $dsn = self::DSN_MYSQL;
        }
        $this -> fixture -> connect($dsn, array('prefix' => 'foo_'));
        $this -> fixture -> install('', array('purge' => true));
    }

    protected function _populate($scenario) {
        $this -> setPhase('ACL populate (' . $scenario . ').');
        $acl = &$this -> fixture;
        $assets     = $acl -> assetSectionGet('Building Access System');
        $building   = $acl -> assetGet($assets, 'Head Office');
        $reception  = $acl -> assetGet($assets, array('Head Office', 'Reception'));
        $execRow    = $acl -> assetGet($assets, array('Head Office', 'Executive Row'));
        $ceoOffice  = $acl -> assetGet($assets, array('Head Office', 'Executive Row', 'Chairman\'s Office'));
        $sales      = $acl -> assetGet($assets, array('Head Office', 'Sales'));
        $devel      = $acl -> assetGet($assets, array('Head Office', 'Development'));
        $games      = $acl -> assetGet($assets, array('Head Office', 'Development', 'Games'));
        $operations = $acl -> assetGet($assets, array('Head Office', 'Operations'));
        $caf        = $acl -> assetGet($assets, array('Head Office', 'Cafeteria'));
        $meeting    = $acl -> assetGet($assets, array('Head Office', 'Meeting Rooms'));
        $people = $acl -> requesterSectionGet('People');
        $pdef = $acl -> permissionDefinitionGet('Building Access System', 'Access Times');
        switch ($scenario) {
            case 'default': {
                /*
                 * Visitors get limited access, VIPs get to see the boss
                 */
                $acl -> permissionSet(
                    $reception, $people,
                    array('Non-Staff', 'Visitors'), $pdef, 'Office Hours'
                );
                $acl -> permissionSet(
                    $meeting, $people,
                    array('Non-Staff', 'Visitors'), $pdef, 'Office Hours'
                );
                $acl -> permissionSet(
                    $ceoOffice, $people,
                    array('Non-Staff', 'Visitors', 'VIPs'), $pdef, 'Office Hours'
                );
                /*
                 * Security has evening access to everything
                 */
                $acl -> permissionSet(
                    $building, $people,
                    array('Contractors', 'Security'), $pdef, '7/24'
                );
                /*
                 * Executives get access to everything, except the VP sales
                 * can't play games.
                 */
                $acl -> permissionSet(
                    $building, $people,
                    array('Staff', 'Executives'), $pdef, 'Office Hours'
                );
                $acl -> permissionSet(
                    $games, $people,
                    array('Staff', 'Executives', 'VP Sales'), $pdef, 'Deny'
                );

            }
        }
    }

    /**
     * Initialize the ACL data store with some basic test objects.
     *
     * @param array An optional list of object groups to set up. If missing, all
     * groups are created.
     */
    protected function _setupBasic($options = null) {
        $acl = &$this -> fixture;
        /*
         * Create a working domain
         */
        $acl -> domainAdd('A domain', 'password');
        /*
         * Add assets, requesters, permission section
         */
        $this -> setPhase('ACL set-up.');
        if (is_null($options) || isset($options['asset'])) {
            $acl -> assetSectionAdd('asec1');
            $acl -> assetAdd('asec1', 'asset 1');
        }
        if (is_null($options) || isset($options['requester'])) {
            $acl -> requesterSectionAdd('rsec1');
            $acl -> requesterAdd('rsec1', 'requester 1');
        }
        if (is_null($options) || isset($options['permission'])) {
            $acl -> permissionSectionAdd('psec1');
            /*
             * Add permission definitions
             */
            $pdef = &AP5L_Acl_PermissionDefinition::factory('pdef1');
            $pdef -> definition('text', array('length-max' => 20));
            $acl -> permissionDefinitionAdd('psec1', $pdef);
            $pdef = &AP5L_Acl_PermissionDefinition::factory('pdef2');
            $pdef -> definition(
                'choice',
                array(
                    'choices' => array('deny', 'allow'),
                    'case-insensitive' => true
                )
            );
            $acl -> permissionDefinitionAdd('psec1', $pdef);
        }
    }

    /**
     * Set up the data store for conflict tests.
     */
    protected function _setupConflicts() {
        $acl = &$this -> fixture;
        /*
         * Create a working domain
         */
        $acl -> domainAdd('A domain', 'password');
        /*
         * Add assets, requester, permission section
         */
        $this -> setPhase('ACL set-up.');
        $acl -> assetSectionAdd('asec1');
        $acl -> assetAdd('asec1', array('path1'));
        $acl -> assetAdd('asec1', array('path1', 'goal'));
        $acl -> assetAdd('asec1', array('path2'));
        $acl -> assetAdd('asec1', array('path2', 'goal'));
        $acl -> requesterSectionAdd('rsec1');
        $acl -> requesterAdd('rsec1', 'requester 1');
        $acl -> permissionSectionAdd('psec1');
        $pdef = &AP5L_Acl_PermissionDefinition::factory('access');
        $pdef -> definition(
            'choice',
            array(
                'choices' => array('deny', 'allow'),
                'case-insensitive' => true
            )
        );
        $acl -> permissionDefinitionAdd('psec1', $pdef);
    }

    /**
     * Initialize the ACL data store with a basic test base.
     *
     * @param array An optional list of object groups to set up. If missing, all
     * groups are created.
     */
    protected function _setupSimple() {
        $this -> setPhase('ACL set-up.');
        $acl = &$this -> fixture;
        /*
         * Create a working domain
         */
        $acl -> domainAdd('A domain', 'password');
        /*
         * Assets: Physical areas inside a building.
         *
         * We have a reception area, the workspace. Within the workspace we have
         * executive offices, and within that the CEO office; a lunch room;
         * sales and development groups; operations; and a meeting room. Inside
         * the development area we have a games room.
         */
        $assets     = $acl -> assetSectionAdd('Building Access System');
        $building   = $acl -> assetAdd($assets, 'Head Office');
        $reception  = $acl -> assetAdd($assets, array('Head Office', 'Reception'));
        $execRow    = $acl -> assetAdd($assets, array('Head Office', 'Executive Row'));
        $ceoOffice  = $acl -> assetAdd($assets, array('Head Office', 'Executive Row', 'Chairman\'s Office'));
        $sales      = $acl -> assetAdd($assets, array('Head Office', 'Sales'));
        $devel      = $acl -> assetAdd($assets, array('Head Office', 'Development'));
        $games      = $acl -> assetAdd($assets, array('Head Office', 'Development', 'Games'));
        $operations = $acl -> assetAdd($assets, array('Head Office', 'Operations'));
        $caf        = $acl -> assetAdd($assets, array('Head Office', 'Cafeteria'));
        $meeting    = $acl -> assetAdd($assets, array('Head Office', 'Meeting Rooms'));
        /*
         * Create a people section
         */
        $people = $acl -> requesterSectionAdd('People');
        $acl -> requesterAdd($people, 'Staff');
        $acl -> requesterAdd($people, array('Staff', 'Executives'));
        $acl -> requesterAdd($people, array('Staff', 'Executives', 'CEO'));
        $acl -> requesterAdd($people, array('Staff', 'Executives', 'VP Sales'));
        $acl -> requesterAdd($people, array('Staff', 'Executives', 'VP Development'));
        $acl -> requesterAdd($people, array('Staff', 'Managers'));
        $acl -> requesterAdd($people, array('Staff', 'Managers', 'Channel Sales'));
        $acl -> requesterAdd($people, array('Staff', 'Managers', 'Direct Sales'));
        $acl -> requesterAdd($people, array('Staff', 'Managers', 'Development'));
        $acl -> requesterAdd($people, array('Staff', 'Managers', 'Quality'));
        $acl -> requesterAdd($people, array('Staff', 'Sales'));
        $acl -> requesterAdd($people, array('Staff', 'Sales', 'VP Sales'));
        $acl -> requesterAdd($people, array('Staff', 'Sales', 'Channel Sales'));
        $acl -> requesterAdd($people, array('Staff', 'Sales', 'Direct Sales'));
        $acl -> requesterAdd($people, array('Staff', 'Sales', 'Rep 1'));
        $acl -> requesterAdd($people, array('Staff', 'Sales', 'Rep 2'));
        $acl -> requesterAdd($people, array('Staff', 'Sales', 'Sales Tech'));
        $acl -> requesterAdd($people, array('Staff', 'Developers'));
        $acl -> requesterAdd($people, array('Staff', 'Developers', 'Dev 1'));
        $acl -> requesterAdd($people, array('Staff', 'Developers', 'Dev 2'));
        $acl -> requesterAdd($people, array('Staff', 'Developers', 'Sales Tech'));
        $acl -> requesterAdd($people, 'Contractors');
        $acl -> requesterAdd($people, array('Contractors', 'Security'));
        $acl -> requesterAdd($people, 'Non-Staff');
        $acl -> requesterAdd($people, array('Non-Staff', 'Visitors'));
        $acl -> requesterAdd($people, array('Non-Staff', 'Visitors', 'VIPs'));

        $access = $acl -> permissionSectionAdd('Building Access System');
        $pdef = &AP5L_Acl_PermissionDefinition::factory('Access Times');
        $pdef -> definition(
            'choice',
            array(
                'choices' => array('Deny', 'Office Hours', 'Evenings', '7/24'),
                'case-insensitive' => true
            ),
            'Deny'
        );
        $acl -> permissionDefinitionAdd($access, $pdef);
    }

    function getPhase() {
        return $this -> phase;
    }

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('ManagerTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Set the test phase (hook for debugging)
     */
    function setPhase($phase) {
        //echo $phase . AP5L::LF;
        $this -> phase = $phase;
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
        AP5L::setDebug(AP5L_Debug::getInstance());
        //AP5L_Debug::getInstance() -> setState('', true);
        $this -> fixture = new AP5L_Acl_Manager();
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown() {
        $this -> fixture -> disconnect();
    }

    /**
     * Just a very basic test of connecting to a data store.
     */
    function testConnect() {
        $this -> _connect();
    }

    /**
     * Test basic domain operations.
     */
    function testDomain() {
        $this -> _connect();
        $this -> fixture -> domainAdd('A domain', 'password');
    }

    /**
     * Test section operations (using asset sections)
     */
    function testAssetSection() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            /*
             * Query without setting a domain
             */
            $pass = false;
            try {
                $result = $acl -> assetSectionListing();
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception when domain not set.');
            /*
             * Create a working domain
             */
            $acl -> domainAdd('A domain', 'password');
            /*
             * Add an asset section
             */
            $this -> setPhase('Add first asset section.');
            $acl -> assetSectionAdd(
                'Asset section 1',
                array('info' => 'It\'s a description!')
            );
            /*
             * List sections
             */
            $list = $acl -> assetSectionListing();
            $this -> assertEquals(1, count($list), 'asset section count:' . $this -> getPhase());
            $this -> assertEquals('Asset section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Try adding a duplicate
             */
            $this -> setPhase('Add duplicate asset section.');
            $pass = false;
            try {
                $acl -> assetSectionAdd('Asset section 1');
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception on duplicate asset section add.');
            /*
             * List sections
             */
            $list = $acl -> assetSectionListing();
            $this -> assertEquals(1, count($list), 'asset section count:' . $this -> getPhase());
            $this -> assertEquals('Asset section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Add a second asset section
             */
            $this -> setPhase('Add second asset section.');
            $acl -> assetSectionAdd(
                'Asset section 2',
                array('info' => 'The second one.')
            );
            /*
             * List sections again
             */
            $list = $acl -> assetSectionListing(array('order' => 'ID'));
            $this -> assertEquals(2, count($list), 'asset section count:' . $this -> getPhase());
            $this -> assertEquals('Asset section 2', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('The second one.', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Rename second asset section
             */
            $this -> setPhase('Rename second asset section.');
            $acl -> assetSectionRename(
                'Asset section 2',
                'AS2 renamed'
            );
            /*
             * List sections again
             */
            $list = $acl -> assetSectionListing(array('order' => 'ID'));
            $this -> assertEquals(2, count($list), 'asset section count:' . $this -> getPhase());
            $this -> assertEquals('AS2 renamed', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('The second one.', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Merge sections
             */
            $this -> setPhase('Merge asset sections.');
            $acl -> assetSectionMerge(
                'AS2 renamed',
                'Asset section 1'
            );
            /*
             * List sections
             */
            $list = $acl -> assetSectionListing(array('order' => 'ID'));
            $this -> assertEquals(1, count($list), 'asset section count:' . $this -> getPhase());
            $this -> assertEquals('Asset section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Test tree operations (using assets)
     */
    function testTree() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            /*
             * Query without setting a domain
             */
            $pass = false;
            try {
                $result = $acl -> assetSectionListing();
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception when domain not set.');
            /*
             * Create a working domain
             */
            $acl -> domainAdd('A domain', 'password');
            /*
             * Add an asset section
             */
            $this -> setPhase('Add first asset section.');
            $acl -> assetSectionAdd(
                'asec1',
                array('info' => 'It\'s a description!')
            );
            /*
             * Add an asset
             */
            $this -> setPhase('Add first asset.');
            $acl -> assetAdd(
                'asec1',
                'asset 1',
                array('info' => 'My first asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', null, array('order' => 'ID'));
            $this -> assertEquals(1, count($list), $this -> getPhase());
            $this -> assertEquals('asset 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('My first asset!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Try adding same asset
             */
            $this -> setPhase('Add duplicate asset.');
            $pass = false;
            try {
                $acl -> assetAdd(
                    'asec1',
                    'asset 1',
                    array('info' => 'My first asset!')
                );
            } catch (AP5L_Exception $e) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception on ' . $this -> getPhase());
            /*
             * Add an asset
             */
            $this -> setPhase('Add second asset.');
            $acl -> assetAdd(
                'asec1',
                'asset 2',
                array('info' => 'My second asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', null, array('order' => 'ID'));
            $this -> assertEquals(2, count($list), $this -> getPhase());
            $this -> assertEquals('asset 2', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('My second asset!', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Add sub asset
             */
            $this -> setPhase('Add first sub-asset.');
            $acl -> assetAdd(
                'asec1',
                array('asset 1', 'asset 1.1'),
                array('info' => 'My first sub-asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', 'asset 1', array('order' => 'ID'));
            $this -> assertEquals(1, count($list), $this -> getPhase());
            $this -> assertEquals('asset 1.1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('My first sub-asset!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Add another sub asset
             */
            $this -> setPhase('Add second sub-asset.');
            $acl -> assetAdd(
                'asec1',
                array('asset 2', 'asset 2.1'),
                array('info' => 'My second sub-asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', 'asset 2', array('order' => 'ID'));
            $this -> assertEquals(1, count($list), $this -> getPhase());
            $this -> assertEquals('asset 2.1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('My second sub-asset!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Add sub asset with same name but different path
             */
            $this -> setPhase('Add third sub-asset.');
            $acl -> assetAdd(
                'asec1',
                array('asset 2', 'asset 1.1'),
                array('info' => 'Same name sub-asset!')
            );
            /*
             * Get assets by name
             */
            $list = $acl -> assetSearch('asec1', 'asset 1.1');
            $this -> assertEquals(2, count($list), $this -> getPhase());
            $this -> assertEquals('asset 1.1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('My first sub-asset!', $list[0] -> getInfo(), $this -> getPhase());
            $this -> assertEquals('asset 1.1', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('Same name sub-asset!', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Rename top level asset.
             */
            $this -> setPhase('Rename second asset.');
            $acl -> assetMove(
                'asec1',
                array('asset 2'),
                'asec1',
                array('asset B')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', null, array('order' => 'ID'));
            $this -> assertEquals(2, count($list), $this -> getPhase());
            $this -> assertEquals('asset 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('My first asset!', $list[0] -> getInfo(), $this -> getPhase());
            $this -> assertEquals('asset B', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('My second asset!', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * List sub-assets
             */
            $list = $acl -> assetListing('asec1', 'asset B', array('order' => 'ID'));
            $this -> assertEquals(2, count($list), $this -> getPhase());
            $this -> assertEquals('asset 2.1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('My second sub-asset!', $list[0] -> getInfo(), $this -> getPhase());
            $this -> assertEquals('asset 1.1', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('Same name sub-asset!', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Erroneous rename top level asset.
             */
            $this -> setPhase('Duplicate rename second asset.');
            $pass = false;
            try {
                $acl -> assetMove(
                    'asec1',
                    array('asset B'),
                    'asec1',
                    array('asset 1')
                );
            } catch (AP5L_Exception $e) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception on ' . $this -> getPhase());
            /*
             * Move a sub-asset
             */
            $this -> setPhase('Move a sub-asset.');
            $acl -> assetMove(
                'asec1',
                array('asset 1', 'asset 1.1'),
                'asec1',
                array('asset B', 'asset 2.2')
            );
            /*
             * List sub-assets
             */
            $list = $acl -> assetListing('asec1', 'asset B', array('order' => 'name'));
            $this -> assertEquals(3, count($list), $this -> getPhase());
            $ind = 1;
            $this -> assertEquals('asset 2.1', $list[$ind] -> getName(), $this -> getPhase());
            $this -> assertEquals('My second sub-asset!', $list[$ind] -> getInfo(), $this -> getPhase());
            $ind = 0;
            $this -> assertEquals('asset 1.1', $list[$ind] -> getName(), $this -> getPhase());
            $this -> assertEquals('Same name sub-asset!', $list[$ind] -> getInfo(), $this -> getPhase());
            $ind = 2;
            $this -> assertEquals('asset 2.2', $list[$ind] -> getName(), $this -> getPhase());
            $this -> assertEquals('My first sub-asset!', $list[$ind] -> getInfo(), $this -> getPhase());
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Test permission definition functionality.
     */
    function testPermissionDefinition() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            /*
             * Query without setting a domain
             */
            $pass = false;
            try {
                $result = $acl -> permissionSectionListing();
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception when domain not set.');
            /*
             * Create a working domain
             */
            $acl -> domainAdd('A domain', 'password');
            /*
             * Add a permission section
             */
            $this -> setPhase('Add first permission section.');
            $acl -> permissionSectionAdd(
                'psec1',
                array('info' => 'It\'s a description!')
            );
            /*
             * Create and add a permission definition
             */
            $this -> setPhase('Add first permission def.');
            $pdef = &AP5L_Acl_PermissionDefinition::factory('pdef1');
            $pdef -> definition('text', array('length-max' => 20));
            $acl -> permissionDefinitionAdd('psec1', $pdef);
            /*
             * Verify that it's there
             */
            $list = $acl -> permissionDefinitionListing();
            $this -> assertEquals(1, count($list), 'permission def count:' . $this -> getPhase());
            $this -> assertEquals('pdef1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('text', $list[0] -> getType(), $this -> getPhase());
            $this -> assertEquals(array('length-max' => 20), $list[0] -> getRules(), $this -> getPhase());
            /*
             * Try adding the same def
             */
            $this -> setPhase('Add duplicate permission def.');
            $pass = false;
            try {
                $pdef -> setID(0);
                $acl -> permissionDefinitionAdd('psec1', $pdef);
            } catch (AP5L_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception on duplicate permission def add.');
            /*
             * Create and add a second permission definition
             */
            $this -> setPhase('Add second permission def.');
            $pdef = &AP5L_Acl_PermissionDefinition::factory('pdef2');
            $pdef -> definition(
                'choice',
                array(
                    'choices' => array('deny', 'allow'),
                    'case-insensitive' => true
                )
            );
            $acl -> permissionDefinitionAdd('psec1', $pdef);
            /*
             * Verify that the defs were stored
             */
            $list = $acl -> permissionDefinitionListing();
            $this -> assertEquals(2, count($list), 'permission def count:' . $this -> getPhase());
            $this -> assertEquals('pdef1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('text', $list[0] -> getType(), $this -> getPhase());
            $this -> assertEquals('pdef2', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('choice', $list[1] -> getType(), $this -> getPhase());
            /*
             * Delete the first section
             */
            $this -> setPhase('Delete first permission def.');
            $acl -> permissionDefinitionDelete('psec1', 'pdef1');
            /*
             * Verify the delete
             */
            $list = $acl -> permissionDefinitionListing();
            $this -> assertEquals(1, count($list), 'permission def count:' . $this -> getPhase());
            $this -> assertEquals('pdef2', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('choice', $list[0] -> getType(), $this -> getPhase());
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Test section operations (using permission sections)
     */
    function testPermissionSection() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            /*
             * Query without setting a domain
             */
            $pass = false;
            try {
                $result = $acl -> permissionSectionListing();
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception when domain not set.');
            /*
             * Create a working domain
             */
            $acl -> domainAdd('A domain', 'password');
            /*
             * Add an asset section
             */
            $this -> setPhase('Add first permission section.');
            $acl -> permissionSectionAdd(
                'Permission section 1',
                array('info' => 'It\'s a description!')
            );
            /*
             * List sections
             */
            $list = $acl -> permissionSectionListing();
            $this -> assertEquals(1, count($list), 'permission section count:' . $this -> getPhase());
            $this -> assertEquals('Permission section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Try adding a duplicate
             */
            $this -> setPhase('Add duplicate permission section.');
            $pass = false;
            try {
                $acl -> permissionSectionAdd('Permission section 1');
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception on duplicate permission section add.');
            /*
             * List sections
             */
            $list = $acl -> permissionSectionListing();
            $this -> assertEquals(1, count($list), 'permission section count:' . $this -> getPhase());
            $this -> assertEquals('Permission section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Add a second section
             */
            $this -> setPhase('Add second permission section.');
            $acl -> permissionSectionAdd(
                'Permission section 2',
                array('info' => 'The second one.')
            );
            /*
             * List sections again
             */
            $list = $acl -> permissionSectionListing(array('order' => 'ID'));
            $this -> assertEquals(2, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('Permission section 2', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('The second one.', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Rename second section
             */
            $this -> setPhase('Rename second permission section.');
            $acl -> permissionSectionRename(
                'Permission section 2',
                'PS2 renamed'
            );
            /*
             * List sections again
             */
            $list = $acl -> permissionSectionListing(array('order' => 'ID'));
            $this -> assertEquals(2, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('PS2 renamed', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('The second one.', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Merge sections
             */
            $this -> setPhase('Merge permission sections.');
            $acl -> permissionSectionMerge(
                'PS2 renamed',
                'Permission section 1'
            );
            /*
             * List sections
             */
            $list = $acl -> permissionSectionListing(array('order' => 'ID'));
            $this -> assertEquals(1, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('Permission section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Test section operations (using requester sections)
     */
    function testRequesterSection() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            /*
             * Query without setting a domain
             */
            $pass = false;
            try {
                $result = $acl -> requesterSectionListing();
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception when domain not set.');
            /*
             * Create a working domain
             */
            $acl -> domainAdd('A domain', 'password');
            /*
             * Add an asset section
             */
            $this -> setPhase('Add first requester section.');
            $acl -> requesterSectionAdd(
                'requester section 1',
                array('info' => 'It\'s a description!')
            );
            /*
             * List sections
             */
            $list = $acl -> requesterSectionListing();
            $this -> assertEquals(1, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('requester section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Try adding a duplicate
             */
            $this -> setPhase('Add duplicate requester section.');
            $pass = false;
            try {
                $acl -> requesterSectionAdd('requester section 1');
            } catch (AP5L_Acl_Exception $foo) {
                $pass = true;
            }
            $this -> assertTrue($pass, 'No exception on duplicate requester section add.');
            /*
             * List sections
             */
            $list = $acl -> requesterSectionListing();
            $this -> assertEquals(1, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('requester section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
            /*
             * Add a second asset section
             */
            $this -> setPhase('Add second requester section.');
            $acl -> requesterSectionAdd(
                'requester section 2',
                array('info' => 'The second one.')
            );
            /*
             * List sections again
             */
            $list = $acl -> requesterSectionListing(array('order' => 'ID'));
            $this -> assertEquals(2, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('requester section 2', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('The second one.', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Rename second section
             */
            $this -> setPhase('Rename second requester section.');
            $acl -> requesterSectionRename(
                'requester section 2',
                'RS2 renamed'
            );
            /*
             * List sections again
             */
            $list = $acl -> requesterSectionListing(array('order' => 'ID'));
            $this -> assertEquals(2, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('RS2 renamed', $list[1] -> getName(), $this -> getPhase());
            $this -> assertEquals('The second one.', $list[1] -> getInfo(), $this -> getPhase());
            /*
             * Merge sections
             */
            $this -> setPhase('Merge requester sections.');
            $acl -> requesterSectionMerge(
                'RS2 renamed',
                'requester section 1'
            );
            /*
             * List sections
             */
            $list = $acl -> requesterSectionListing(array('order' => 'ID'));
            $this -> assertEquals(1, count($list), 'section count:' . $this -> getPhase());
            $this -> assertEquals('requester section 1', $list[0] -> getName(), $this -> getPhase());
            $this -> assertEquals('It\'s a description!', $list[0] -> getInfo(), $this -> getPhase());
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Exercises the ID path calculation code
     */
    function testIDPath() {
        $acl = &$this -> fixture;
        try {
            $idTrack = array();
            $this -> _connect();
            /*
             * Create a working domain
             */
            $acl -> domainAdd('A domain', 'password');
            /*
             * Add an asset section
             */
            $this -> setPhase('Add first asset section.');
            $acl -> assetSectionAdd(
                'asec1',
                array('info' => 'It\'s a description!')
            );
            /*
             * Add an asset
             */
            $this -> setPhase('Add first asset.');
            $acl -> assetAdd(
                'asec1',
                'asset 1',
                array('info' => 'My first asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', null, array('order' => 'ID'));
            $this -> assertEquals(1, count($list), $this -> getPhase());
            $idTrack[$list[0] -> getName()] = $list[0] -> getID();
            $this -> assertEquals(
                array(0, $idTrack['asset 1']),
                $list[0] -> getIDPath(),
                $this -> getPhase()
            );
            /*
             * Add an asset
             */
            $this -> setPhase('Add second asset.');
            $acl -> assetAdd(
                'asec1',
                'asset 2',
                array('info' => 'My second asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', null, array('order' => 'ID'));
            $this -> assertEquals(2, count($list), $this -> getPhase());
            $idTrack[$list[1] -> getName()] = $list[1] -> getID();
            $this -> assertEquals(
                array(0, $idTrack['asset 2']),
                $list[1] -> getIDPath(),
                $this -> getPhase()
            );
            /*
             * Add sub asset
             */
            $this -> setPhase('Add first sub-asset.');
            $acl -> assetAdd(
                'asec1',
                array('asset 1', 'asset 1.1'),
                array('info' => 'My first sub-asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing('asec1', 'asset 1', array('order' => 'ID'));
            $this -> assertEquals(1, count($list), $this -> getPhase());
            $idTrack[$list[0] -> getName()] = $list[0] -> getID();
            $this -> assertEquals(
                array(0, $idTrack['asset 1'], $idTrack['asset 1.1']),
                $list[0] -> getIDPath(),
                $this -> getPhase()
            );
            /*
             * Add sub sub asset
             */
            $this -> setPhase('Add first sub(2)-asset.');
            $acl -> assetAdd(
                'asec1',
                array('asset 1', 'asset 1.1', 'asset 1.1.1'),
                array('info' => 'My first sub-asset!')
            );
            /*
             * List assets
             */
            $list = $acl -> assetListing(
                'asec1', array('asset 1', 'asset 1.1'), array('order' => 'ID')
            );
            $this -> assertEquals(1, count($list), $this -> getPhase());
            $idTrack[$list[0] -> getName()] = $list[0] -> getID();
            $this -> assertEquals(
                array(0, $idTrack['asset 1'], $idTrack['asset 1.1'], $idTrack['asset 1.1.1']),
                $list[0] -> getIDPath(),
                $this -> getPhase()
            );
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Test permission manipulation
     */
    function testPermission_Basic() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            $this -> _setupBasic();
            /*
             * Add a permission
             */
            $this -> setPhase('Add first permission.');
            $acl -> permissionSet(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1', 'pdef2', 'allow'
            );
            /*
             * Get a listing
             */
            $list = $acl -> permissionListing(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1'
            );
            $this -> assertEquals(
                array('psec1' => array('pdef2' => 'allow')),
                $list,
                $this -> getPhase()
            );
            /*
             * Change the permission
             */
            $this -> setPhase('Modify permission.');
            $acl -> permissionSet(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1', 'pdef2', 'deny'
            );
            /*
             * Get a listing
             */
            $list = $acl -> permissionListing(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1'
            );
            $this -> assertEquals(
                array('psec1' => array('pdef2' => 'deny')),
                $list,
                $this -> getPhase()
            );
            /*
             * Invalid permission change
             */
            $this -> setPhase('Invalid modify permission.');
            $pass = false;
            try {
                $acl -> permissionSet(
                    'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1', 'pdef2', 'bogus'
                );
            } catch (AP5L_Exception $e) {
                $pass = true;
            }
            $this -> assertTrue($pass, $this -> getPhase());
            /*
             * Add second permission
             */
            $this -> setPhase('Add second permission.');
            $acl -> permissionSet(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1', 'pdef1', 'some text'
            );
            /*
             * Get a listing
             */
            $list = $acl -> permissionListing(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1'
            );
            $this -> assertEquals(
                array('psec1' => array('pdef1' => 'some text', 'pdef2' => 'deny')),
                $list,
                $this -> getPhase()
            );
            /*
             * Delete a permission
             */
            $this -> setPhase('Delete a permission.');
            $acl -> permissionDelete(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1', 'pdef2'
            );
            /*
             * Get a listing
             */
            $list = $acl -> permissionListing(
                'asec1', 'asset 1', 'rsec1', 'requester 1', 'psec1'
            );
            $this -> assertEquals(
                array('psec1' => array('pdef1' => 'some text')),
                $list,
                $this -> getPhase()
            );
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Make sure the simple setup is working
     */
    function testPermission_Simple() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            $this -> _setupSimple();
            $this -> _populate('default');
            $rights = $acl -> queryPermissionValue(
                'Building Access System', 'Games',
                'People', 'CEO',
                'Building Access System', 'Access Times'
            );
            $this -> assertEquals('Office Hours', $rights, $this -> getPhase());
            $rights = $acl -> queryPermissionValue(
                'Building Access System', 'Games',
                'People', 'Rep 2',
                'Building Access System', 'Access Times'
            );
            $this -> assertEquals('Deny', $rights, $this -> getPhase());
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Create a simple conflict and see how it gets resolved.
     */
     function testPermission_ConflictSimple() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            $this -> _setupConflicts();
            $acl = &$this -> fixture;
            /*
             * Get shortcuts to some objects
             */
            $this  -> setPhase('simple conflict');
            $goal1 = $acl -> assetGet('asec1', array('path1', 'goal'));
            $goal2 = $acl -> assetGet('asec1', array('path2', 'goal'));
            $req = $acl -> requesterGet('rsec1', 'requester 1');
            $pdef = $acl -> permissionDefinitionGet('psec1', 'access');
            /*
             * Allow access via path 1, deny via path 2
             */
            $acl -> permissionSet($goal1, $req, $pdef, 'allow');
            $acl -> permissionSet($goal2, $req, $pdef, 'deny');
            /*
             * See what we get back
             */
            $rights = $acl -> queryPermission(
                'asec1', 'goal', 'rsec1', 'requester 1', 'psec1', 'access'
            );
            $this -> assertTrue($rights -> getConflicted(), $this -> getPhase());
            $this -> assertEquals('deny', $rights -> getValue(), $this -> getPhase());
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

    /**
     * Create a simple conflict and see how it gets resolved.
     */
     function testPermission_ConflictCrossLevel() {
        $acl = &$this -> fixture;
        try {
            $this -> _connect();
            $this -> _setupConflicts();
            /*
             * Get shortcuts to some objects
             */
            $goal1 = $acl -> assetGet('asec1', array('path1', 'goal'));
            $goal2 = $acl -> assetGet('asec1', array('path2', 'goal'));
            $req = $acl -> requesterGet('rsec1', 'requester 1');
            $pdef = $acl -> permissionDefinitionGet('psec1', 'access');
            /*
             * Allow access via path 1, deny via path 2
             */
            $acl -> permissionSet($goal1, $req, $pdef, 'allow');
            $acl -> permissionSet($goal2, $req, $pdef, 'deny');
            /*
             * See what we get back
             */
            $rights = $acl -> queryPermissionValue(
                'asec1', 'goal', 'rsec1', 'requester 1', 'psec1', 'access'
            );
        } catch (AP5L_Exception $e) {
            $this -> fail('unexpected exception: ' . $e);
        }
    }

}

// Call ManagerTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'ManagerTest::main') {
    ManagerTest::main();
}
