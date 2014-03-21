<?php
/**
 * Test the flat map.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: FlatMapTest.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call FlatMapTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'FlatMapTest::main');
}

$path = dirname(__FILE__);
while (! function_exists('__autoload')) {
    $file = $path . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'autoloader.php';
    if (file_exists($file)) {
        include $file;
        break;
    }
    if ($path == dirname($path)) break;
    $path = dirname($path);
}

require_once 'PHPUnit/Framework.php';

/**
 * Test class for FlatMap.
 */
class FlatMapTest extends PHPUnit_Framework_TestCase {
    protected $_epsilon = 1e-12;
    /**
     * Main object for testing.
     *
     * @var AP5L_Gfx_ImageFill
     */
    public $fixture;

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('FlatMapTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
        $this -> fixture = new AP5L_Gfx_FillMap_Flat();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }

    /**
     *
     */
    public function testFlatMap() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32));
        $this -> assertEquals('r', $lm -> iterateMode);
        $this -> assertEquals(0, $lm -> startX);
        $this -> assertEquals(16, $lm -> endX);
        $this -> assertEquals(16, $lm -> incrX);
        $this -> assertEquals(0, $lm -> startY);
        $this -> assertEquals(32, $lm -> endY);
        $this -> assertEquals(32, $lm -> incrY);
        $this -> assertEquals(1.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

}

// Call FlatMapTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'FlatMapTest::main') {
    FlatMapTest::main();
}

