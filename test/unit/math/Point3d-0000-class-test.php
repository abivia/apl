<?php
/**
 * Unit tests for point 3d object.
 *
 * @package AP5L
 * @subpackage Math
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Point3d-0000-class-test.php 94 2009-08-21 03:07:30Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call Point3DTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Point3DTest::main');
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
 * Test class for ImageFill.
 */
class Point3DTest extends PHPUnit_Framework_TestCase {

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite = new PHPUnit_Framework_TestSuite('Point3DTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Test the add constuctor
     */
    public function testConstruct() {
        //return;
        $p3 = new AP5L_Math_Point3d();
        $pass = $p3 -> x ==  0 && $p3 -> y == 0 && $p3 -> z == 0;
        $this -> assertTrue($pass, 'Empty construct.');
        $p3 = new AP5L_Math_Point3d(1, 2.2, 3.4);
        $pass = $p3 -> x ==  1 && $p3 -> y == 2.2 && $p3 -> z == 3.5;
        $this -> assertTrue($pass, 'Construct with args.');
    }

    /**
     * Test the add operator
     */
    public function testAdd() {
        //return;
        $p3 = new AP5L_Math_Point3d();
        $add = new AP5L_Math_Point3d();
        $sum = $p3.add($add);
        $this -> assertEquals($sum, $p3, 'Two zeroes.');
        $p3 = new AP5L_Math_Point3d(1, 2, 3);
        $add = new AP5L_Math_Point3d(4, 5, 6);
        $sum = $p3.add($add);
        $pass = $sum -> x ==  5 && $sum -> y == 7 && $sum -> z == 9;
        $this -> assertTrue($pass, 'Nonzero add');
    }

    /**
     * Test the dot function
     */
    public function testDot() {
        //return;
        $p3 = new AP5L_Math_Point3d();
        $add = new AP5L_Math_Point3d();
        $dot = $p3.dot($add);
        $this -> assertEquals($dot, 0, 'Point at origin.');
        $p3 = new AP5L_Math_Point3d(1, 2, 3);
        $add = new AP5L_Math_Point3d(4, 5, 6);
        $dot = $p3.dot($add);
        $this -> assertEquals($dot, 34, '1,2,3 . 4,5,6');
    }

    /**
     * Test the length function
     */
    public function testLength() {
        //return;
        $p3 = new AP5L_Math_Point3d();
        $len = $p3.length();
        $this -> assertEquals($len, 0, 'Point at origin.');
        $p3 = new AP5L_Math_Point3d(1, 2, 3);
        $len = $p3.length();
        $this -> assertEquals($len, 3.7416573867739413855837487323165, '1,2,3.');
    }

    /**
     * Test the scale operator
     */
    public function testScale() {
        //return;
        $p3 = new AP5L_Math_Point3d();
        $np3 = $p3.scale(2);
        $this -> assertEquals($np3, $p3, 'Twice zero.');
        $p3 = new AP5L_Math_Point3d(1, 2, 3);
        $np3 = $p3.scale(4);
        $pass = $np3 -> x ==  4 && $np3 -> y == 8 && $np3 -> z == 12;
        $this -> assertTrue($pass, 'Nonzero scale');
    }

}

// Call Point3DTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'Point3DTest::main') {
    Point3DTest::main();
}

