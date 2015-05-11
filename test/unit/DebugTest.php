<?php
/**
 * Unit tests for the debug writer
 *
 * @package AP5L
 * @subpackage QC
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: DebugTest.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call DebugTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'DebugTest::main');
}

/*
 * Find the library root and install AP5L.
 */
if (!class_exists('AP5L', false)) {
    $path = dirname(__FILE__);
    $home = 'test' . DIRECTORY_SEPARATOR . 'unit';
    if (($trim = strpos($path, $home)) === false) {
        throw new Exception(
            'Unable to find AP5L.php. No ' . $home . ' in ' . dirname(__FILE__)
        );
    }
    include substr($path, 0, $trim) . 'src' . DIRECTORY_SEPARATOR . 'AP5L.php';
    AP5L::install();
}

require_once 'PHPUnit/Framework.php';

/**
 * Test class for ACL Debug.
 */
class DebugTest extends PHPUnit_Framework_TestCase {
    /**
     * Main object for testing.
     *
     * @var AP5L_Debug
     */
    public $fixture;

    function __construct() {
        parent::__construct();
        //$this -> backupGlobals = false;
    }

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('DebugTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown() {
    }

    /**
     * Test initial settings
     */
    function testInitial() {
        $d = AP5L_Debug_Stdout::getInstance();
        $this -> assertTrue(! $d -> getState(''));
        $this -> assertTrue(! $d -> getState('::MyClass'));
        $this -> assertTrue(! $d -> getState('::MyClass::stuff'));
        $this -> assertTrue(! $d -> getState('::MyClass::stuff'));
        $this -> assertTrue(! $d -> getState('::Package_MyClass'));
        $this -> assertTrue(! $d -> getState('::Package_MyClass::stuff'));
        $this -> assertTrue(! $d -> getState('Space::Package_MyClass'));
        $this -> assertTrue(! $d -> getState('Space::Package_MyClass::stuff'));
        $this -> assertTrue(! $d -> getState('Pkg_Space::Package_MyClass'));
        $this -> assertTrue(! $d -> getState('Pkg_Space::Package_MyClass::stuff'));
    }

    /**
     * Test basic state setup
     */
    function testSetState() {
        $d = AP5L_Debug_Stdout::getInstance();
        $d -> setState('', true);
        $this -> assertTrue($d -> getState(''));
        $this -> assertTrue($d -> getState('::MyClass'));
        $this -> assertTrue($d -> getState('::MyClass::stuff'));
        $this -> assertTrue($d -> getState('type@::MyClass::stuff'));
        $this -> assertTrue($d -> getState('::Package_MyClass'));
        $this -> assertTrue($d -> getState('type@::Package_MyClass::stuff'));
        $this -> assertTrue($d -> getState('Space::Package_MyClass'));
        $this -> assertTrue($d -> getState('type@Space::Package_MyClass::stuff'));
        $this -> assertTrue($d -> getState('Pkg_Space::Package_MyClass'));
        $this -> assertTrue($d -> getState('type@Pkg_Space::Package_MyClass::stuff'));
    }

    /**
     * Test setting hierarchy
     */
    function testSetHierarchy() {
        $d = AP5L_Debug_Stdout::getInstance();
        $d -> setState('', 0);
        $d -> setState('::MyClass', 1);
        $d -> setState('::MyClass::stuff', 2);
        $d -> setState('type@', 3);
        $d -> setState('Pkg_Space::Package_MyClass', 4);
        $d -> setState('Pkg_Space::Package', 5);
        $d -> setState('Pkg_Space::Package_', 6);
        //$d -> setState('::MyClass', true);
        //$d -> setState('::MyClass', true);
        $this -> assertEquals(0,    $d -> getState(''));
        $this -> assertEquals(1,    $d -> getState('::MyClass'));
        $this -> assertEquals(2,    $d -> getState('::MyClass::stuff'));
        $this -> assertEquals(3,    $d -> getState('type@::MyClass::stuff'));
        $this -> assertEquals(0,    $d -> getState('::Package_MyClass'));
        $this -> assertEquals(3,    $d -> getState('type@::Package_MyClass::stuff'));
        $this -> assertEquals(0,    $d -> getState('Space::Package_MyClass'));
        $this -> assertEquals(3,    $d -> getState('type@Space::Package_MyClass::stuff'));
        $this -> assertEquals(4,    $d -> getState('Pkg_Space::Package_MyClass'));
        $this -> assertEquals(6,    $d -> getState('Pkg_Space::Package_'));
        $this -> assertEquals(6,    $d -> getState('Pkg_Space::Package_Whatever'));
        $this -> assertEquals(5,    $d -> getState('Pkg_Space::Package'));
        $this -> assertEquals(6,    $d -> getState('Pkg_Space::Package_::stuff'));
        $this -> assertEquals(5,    $d -> getState('Pkg_Space::Package::stuff'));
        $this -> assertEquals(3,    $d -> getState('type@Pkg_Space::Package_MyClass::stuff'));
    }
}

// Call DebugTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'DebugTest::main') {
    DebugTest::main();
}

