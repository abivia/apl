<?php
/**
 * Test the linear map.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: LinearMapTest.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call LinearMapTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'LinearMapTest::main');
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
 * Test class for LinearMap.
 */
class LinearMapTest extends PHPUnit_Framework_TestCase {
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

        $suite  = new PHPUnit_Framework_TestSuite('LinearMapTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
        $this -> fixture = new AP5L_Gfx_FillMap_Linear();
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
    public function testLinearMapAxisX1() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 0);
        $this -> assertEquals(0, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(0, $lm -> startX);
        $this -> assertEquals(16, $lm -> endX);
        $this -> assertEquals(1, $lm -> incrX);
        $this -> assertEquals(0, $lm -> startY);
        $this -> assertEquals(32, $lm -> endY);
        $this -> assertEquals(1, $lm -> incrY);
        $this -> assertEquals(false, $lm -> getReversed());
        $this -> assertEquals(0.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(0, 8), 'at (0, 8)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(0, 16), 'at (0, 16)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(0, 24), 'at (0, 24)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(0, 32), 'at (0, 32)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 8), 'at (4, 8)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 16), 'at (4, 16)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 24), 'at (4, 24)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 32), 'at (4, 32)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 16), 'at (8, 16)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 24), 'at (8, 24)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 32), 'at (8, 32)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 8), 'at (12, 8)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 24), 'at (12, 24)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 32), 'at (12, 32)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 8), 'at (16, 8)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 24), 'at (16, 24)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

    /**
     *
     */
    public function testLinearMapBandedAxisX1() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 0);
        $lm -> bands = 4;
        $this -> assertEquals(0, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(0, $lm -> startX);
        $this -> assertEquals(16, $lm -> endX);
        $this -> assertEquals(1, $lm -> incrX);
        $this -> assertEquals(0, $lm -> startY);
        $this -> assertEquals(32, $lm -> endY);
        $this -> assertEquals(1, $lm -> incrY);
        $this -> assertEquals(false, $lm -> getReversed());
        $this -> assertEquals(0.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(2, 0), 'at (2, 0)', $this -> _epsilon);
        $this -> assertEquals(1.0/3.0, $lm -> getRatio(3, 0), 'at (3, 0)', $this -> _epsilon);
        $this -> assertEquals(1.0/3.0, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
        $this -> assertEquals(2.0/3.0, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
        $this -> assertEquals(2.0/3.0, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
    }

    /**
     *
     */
    public function testLinearMapEndBandAxisX1() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 0);
        $lm -> bands = 4;
        $lm -> bandMidpoint = false;
        $this -> assertEquals(0, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(0, $lm -> startX);
        $this -> assertEquals(16, $lm -> endX);
        $this -> assertEquals(1, $lm -> incrX);
        $this -> assertEquals(0, $lm -> startY);
        $this -> assertEquals(32, $lm -> endY);
        $this -> assertEquals(1, $lm -> incrY);
        $this -> assertEquals(false, $lm -> getReversed());
        $this -> assertEquals(0.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(2, 0), 'at (2, 0)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(3, 0), 'at (3, 0)', $this -> _epsilon);
        $this -> assertEquals(1.0/3.0, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
        $this -> assertEquals(2.0/3.0, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
    }

    /**
     *
     */
    public function testLinearMapAxisX2() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 180);
        $this -> assertEquals(0, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(true, $lm -> getReversed());
        $this -> assertEquals(0, $lm -> startX);
        $this -> assertEquals(16, $lm -> endX);
        $this -> assertEquals(1, $lm -> incrX);
        $this -> assertEquals(0, $lm -> startY);
        $this -> assertEquals(32, $lm -> endY);
        $this -> assertEquals(1, $lm -> incrY);
        $this -> assertEquals(1.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(0, 8), 'at (0, 8)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(0, 16), 'at (0, 16)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(0, 24), 'at (0, 24)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(0, 32), 'at (0, 32)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(4, 8), 'at (4, 8)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(4, 16), 'at (4, 16)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(4, 24), 'at (4, 24)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(4, 32), 'at (4, 32)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 16), 'at (8, 16)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 24), 'at (8, 24)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 32), 'at (8, 32)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(12, 8), 'at (12, 8)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(12, 24), 'at (12, 24)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(12, 32), 'at (12, 32)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(16, 8), 'at (16, 8)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(16, 24), 'at (16, 24)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

    /**
     * Test quadrant 1 (>0 && <90 degrees)
     */
    public function testLinearMapQuad1a() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 15);
        $this -> assertEquals(15, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(false, $lm -> getReversed());
        $this -> assertEquals(0.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 8), 'at (0, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 16), 'at (0, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 24), 'at (0, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 32), 'at (0, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 8), 'at (4, 8)', $this -> _epsilon);
        $this -> assertEquals(0.3372288150935, $lm -> getRatio(4, 16), 'at (4, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 24), 'at (4, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 32), 'at (4, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 16), 'at (8, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 24), 'at (8, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 32), 'at (8, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 8), 'at (12, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 24), 'at (12, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 32), 'at (12, 32)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 8), 'at (16, 8)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 24), 'at (16, 24)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

    /**
     * Test quadrant 1 (>0 && <90 degrees)
     */
    public function testLinearMapQuad1b() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 75);
        $this -> assertEquals(75, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(false, $lm -> getReversed());
        $this -> assertEquals(0.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 8), 'at (0, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 16), 'at (0, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 24), 'at (0, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 32), 'at (0, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 8), 'at (4, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 16), 'at (4, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 24), 'at (4, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 32), 'at (4, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 16), 'at (8, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 24), 'at (8, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 32), 'at (8, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 8), 'at (12, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 24), 'at (12, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 32), 'at (12, 32)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 8), 'at (16, 8)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 24), 'at (16, 24)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

    /**
     * Test quadrant 2 (>90 && <180 degrees)
     */
    public function testLinearMapQuad2a() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 105);
        $this -> assertEquals(75, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(false, $lm -> getReversed());
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 8), 'at (0, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 16), 'at (0, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 24), 'at (0, 24)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(0, 32), 'at (0, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 8), 'at (4, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 16), 'at (4, 16)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(4, 24), 'at (4, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 32), 'at (4, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 16), 'at (8, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 24), 'at (8, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 32), 'at (8, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(12, 8), 'at (12, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 24), 'at (12, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 32), 'at (12, 32)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 8), 'at (16, 8)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 24), 'at (16, 24)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

    /**
     * Test quadrant 3 (>180 && <270 degrees)
     */
    public function testLinearMapQuad3a() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 195);
        $this -> assertEquals(15, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(true, $lm -> getReversed());
        $this -> assertEquals(1.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 8), 'at (0, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 16), 'at (0, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 24), 'at (0, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 32), 'at (0, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(4, 8), 'at (4, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 16), 'at (4, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 24), 'at (4, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 32), 'at (4, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 16), 'at (8, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 24), 'at (8, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 32), 'at (8, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 8), 'at (12, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(12, 24), 'at (12, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 32), 'at (12, 32)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 8), 'at (16, 8)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 24), 'at (16, 24)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

    /**
     * Test quadrant 4 (>270 && <360 degrees)
     */
    public function testLinearMapQuad4a() {
        $lm = &$this -> fixture;
        $lm -> setup(AP5L_Math_Vector2d::factoryI4Rel(10, 10, 16, 32), 285);
        $this -> assertEquals(75, $lm -> getAngle());
        $this -> assertEquals('xy', $lm -> iterateMode);
        $this -> assertEquals(true, $lm -> getReversed());
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 0), 'at (0, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 8), 'at (0, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 16), 'at (0, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.00, $lm -> getRatio(0, 24), 'at (0, 24)', $this -> _epsilon);
        $this -> assertEquals(0.00, $lm -> getRatio(0, 32), 'at (0, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 0), 'at (4, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 8), 'at (4, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 16), 'at (4, 16)', $this -> _epsilon);
        $this -> assertEquals(0.25, $lm -> getRatio(4, 24), 'at (4, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.25, $lm -> getRatio(4, 32), 'at (4, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 0), 'at (8, 0)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 8), 'at (8, 8)', $this -> _epsilon);
        $this -> assertEquals(0.50, $lm -> getRatio(8, 16), 'at (8, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 24), 'at (8, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.50, $lm -> getRatio(8, 32), 'at (8, 32)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 0), 'at (12, 0)', $this -> _epsilon);
        $this -> assertEquals(0.75, $lm -> getRatio(12, 8), 'at (12, 8)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 16), 'at (12, 16)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 24), 'at (12, 24)', $this -> _epsilon);
//        $this -> assertEquals(0.75, $lm -> getRatio(12, 32), 'at (12, 32)', $this -> _epsilon);
        $this -> assertEquals(1.00, $lm -> getRatio(16, 0), 'at (16, 0)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 8), 'at (16, 8)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 16), 'at (16, 16)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 24), 'at (16, 24)', $this -> _epsilon);
//        $this -> assertEquals(1.00, $lm -> getRatio(16, 32), 'at (16, 32)', $this -> _epsilon);
    }

}

// Call LinearMapTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'LinearMapTest::main') {
    LinearMapTest::main();
}

