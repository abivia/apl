<?php
/**
 * Unit tests for AP5L_Php_HashMap
 *
 * @package AP5L
 * @subpackage QC
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: HashMap-000-class-test.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call HashMapTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'HashMapTest::main');
}

/*
 * Find the library root and install AP5L.
 */
$path = dirname(__FILE__);
$home = 'test' . DIRECTORY_SEPARATOR . 'unit';
if (($trim = strpos($path, $home)) === false) {
    throw new Exception(
        'Unable to find AP5L.php. No ' . $home . ' in ' . dirname(__FILE__)
    );
}
include substr($path, 0, $trim) . 'src' . DIRECTORY_SEPARATOR . 'AP5L.php';
AP5L::install();

require_once 'PHPUnit/Framework.php';

/**
 * Test class for ACL Debug.
 */
class HashMapTest extends PHPUnit_Framework_TestCase {
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

        $suite  = new PHPUnit_Framework_TestSuite('HashMapTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
        error_reporting(E_ALL);
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown() {
    }

    /**
     * Test for class in library
     */
    function testClassExists() {
        $this -> assertTrue(class_exists('ArrayObject', true));
        $this -> assertTrue(class_exists('AP5L_Php_HashMap', true));
    }

    /**
     * Test initial settings
     */
    function testInitial() {
        $hmap = new AP5L_Php_HashMap();
        $this -> assertEquals(0, $hmap -> count());
        $this -> assertTrue(! $hmap -> exists(0));
    }

    /**
     * Test with one element
     */
    function testOneElement() {
        $hmap = new AP5L_Php_HashMap();
        $hmap -> set('foo', 'value of foo');
        $this -> assertEquals(1, $hmap -> count());
        $this -> assertTrue($hmap -> exists('foo'));
        $this -> assertTrue(! $hmap -> exists('not foo'));
        $this -> assertEquals('value of foo', $hmap -> get('foo'));
        //
        $hmap -> set('foo', 'another value of foo');
        $this -> assertEquals(1, $hmap -> count());
        $this -> assertTrue($hmap -> exists('foo'));
        $this -> assertTrue(! $hmap -> exists('not foo'));
        $this -> assertEquals('another value of foo', $hmap -> get('foo'));
        //
        $hmap -> remove('foo');
        $this -> assertEquals(0, $hmap -> count());
        $this -> assertTrue(! $hmap -> exists('foo'));
        $this -> assertTrue(! $hmap -> exists('not foo'));
    }

    /**
     * Test clear() method
     */
    function testClear() {
        $hmap = new AP5L_Php_HashMap();
        $hmap -> set('foo', 'value of foo');
        $hmap -> set('bar', 'value of bar');
        $this -> assertEquals(2, $hmap -> count());
        $hmap -> clear();
        $this -> assertEquals(0, $hmap -> count());
    }

    /**
     * Test iteration with foreach
     */
    function testForEach() {
        $hmap = new AP5L_Php_HashMap();
        $hmap -> set('foo', 'value of foo');
        $hmap -> set('bar', 'value of bar');
        $this -> assertEquals(2, $hmap -> count());
        $expect = array('value of foo', 'value of bar');
        $count = 0;
        foreach ($hmap as $element) {
            $this -> assertEquals($expect[$count], $element);
            ++$count;
        }
    }

}

// Call HashMapTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'HashMapTest::main') {
    HashMapTest::main();
}
?>
