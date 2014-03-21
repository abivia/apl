<?php
/**
 * Unit tests for ColorSpace.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ColorSpaceTest.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call ColorSpaceTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ColorSpaceTest::main');
}

$path = dirname(__FILE__);
while (! function_exists('__autoload')) {
    $file = $path . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'autoloader.php';
    //echo 'path: ' . $file . chr(10);
    if (file_exists($file)) {
        include $file;
        break;
    }
    if ($path == dirname($path)) break;
    $path = dirname($path);
}

require_once 'PHPUnit/Framework.php';

if (! class_exists('AP5L', false)) {
    $base = dirname(__FILE__);
    $base = str_replace('\\', '/', $base);
    require_once $base . '/../../../src/AP5L.php';
    AP5L::install();
}

/**
 * Test class for ColorSpace.
 */
class ColorSpaceTest extends PHPUnit_Framework_TestCase {
    /**
     * Main object for testing.
     *
     * @var AP5L_Gfx_ColorSpace
     */
    public $fixture;

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('ColorSpaceTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
        $this -> fixture = new AP5L_Gfx_ColorSpace();
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
    public function testColorSpaceBlend() {
        $c = &$this -> fixture;
        $c -> setRgba(1.0, 0.5, 0.5, 0.1);
        $mix = new AP5L_Gfx_ColorSpace(0.5, 1.0, 0.5);
        $b = $c -> blend($mix, 0.5);
        $expect = new AP5L_Gfx_ColorSpace(0.75, 0.75, 0.5, 0.1);
        $this -> assertEquals(
            $expect,
            $b,
            'noblend:' . chr(10)
            . 'base=' . $c . chr(10) . ' mix=' . $mix . chr(10)
            . ' expect=' . $expect . chr(10). ' actual=' . $b . chr(10) . chr(10)
        );
        $b = $c -> blend($mix, 0.5, true);
        $expect = new AP5L_Gfx_ColorSpace(0.75, 0.75, 0.5, 0.05);
        $this -> assertEquals(
            $expect,
            $b,
            'blend:' . chr(10)
            . 'base=' . $c . chr(10) . ' mix=' . $mix . chr(10)
            . ' expect=' . $expect . chr(10). ' actual=' . $b . chr(10) . chr(10)
        );
        $c -> setRgba(1.0, 0.5, 0.5, 0.1);
        $mix = new AP5L_Gfx_ColorSpace(0.5, 1.0, 0.5, 0.4);
        $b = $c -> blend($mix, 0.5);
        $expect = new AP5L_Gfx_ColorSpace(0.85, 0.65, 0.5, 0.1);
        $this -> assertEquals(
            $expect,
            $b,
            'noblend:' . chr(10)
            . 'base=' . $c . chr(10) . ' mix=' . $mix . chr(10)
            . ' expect=' . $expect . chr(10). ' actual=' . $b . chr(10) . chr(10)
        );
        $b = $c -> blend($mix, 0.5, true);
        $expect = new AP5L_Gfx_ColorSpace(0.75, 0.75, 0.5, 0.25);
        $this -> assertEquals(
            $expect,
            $b,
            'blend:' . chr(10)
            . 'base=' . $c . chr(10) . ' mix=' . $mix . chr(10)
            . ' expect=' . $expect . chr(10). ' actual=' . $b . chr(10) . chr(10)
        );
    }

    public function testColorSpaceFactory() {
        $c = AP5L_Gfx_ColorSpace::factory();
        $hex = $c -> getHex();
        $this -> assertEquals('000000', $hex);
        $c = AP5L_Gfx_ColorSpace::factory('DEADBE40');
        $hex = $c -> getHex(true);
        $this -> assertEquals('deadbe40', $hex);
    }

    public function testColorSpaceRgbaIntBlend() {
        /*
         * Test data is structured as source1, source2, array(array(ratio,
         * noblend, blend))
         */
        $testData = array(
            array(
                'Simple (no alpha)',
                0x202020, 0x404040,
                array(
                    array(0.0, '202020', '202020'),
                    array(0.5, '303030', '303030'),
                    array(1.0, '404040', '404040'),
                ),
            ),
            array(
                'With alpha',
                0x20202020, 0x40404040,
                array(
                    array(0.0, '20202020', '20202020'),
                    array(0.5, '20282828', '30303030'),
                    array(1.0, '20303030', '40404040'),
                ),
            ),
            array(
                'Negatives 1',
                0x0ff, 0x0ff,
                array(
                    array(0.0, 'ff', 'ff'),
                    array(0.5, 'ff', 'ff'),
                    array(1.0, 'ff', 'ff'),
                ),
            ),
            array(
                'Negatives 2',
                0xff, 0xff,
                array(
                    array(0.0, 'ff', 'ff'),
                    array(0.5, 'ff', 'ff'),
                    array(1.0, 'ff', 'ff'),
                ),
            ),
            array(
                '',
                0x0ff00, 0x0ff00,
                array(
                    array(0.0, 'ff00', 'ff00'),
                    array(0.5, 'ff00', 'ff00'),
                    array(1.0, 'ff00', 'ff00'),
                ),
            ),
            array(
                '',
                0xff00, 0xff00,
                array(
                    array(0.0, 'ff00', 'ff00'),
                    array(0.5, 'ff00', 'ff00'),
                    array(1.0, 'ff00', 'ff00'),
                ),
            ),
        );
        foreach ($testData as $dataSet) {
            $c1 = $dataSet[1];
            $c2 = $dataSet[2];
            foreach ($dataSet[3] as $test) {
                $this -> assertEquals(
                    $test[1],
                    dechex(AP5L_Gfx_ColorSpace::rgbaIntBlend($c1, $c2, $test[0])),
                    $dataSet[0] . ': c1=' . dechex($c1) . ' c2=' . dechex($c2) . ' r=' . $test[0] . ' noblend'
                );
                $this -> assertEquals(
                    $test[2],
                    dechex(AP5L_Gfx_ColorSpace::rgbaIntBlend($c1, $c2, $test[0], true)),
                    $dataSet[0] . ': c1=' . dechex($c1) . ' c2=' . dechex($c2) . ' r=' . $test[0] . ' blend'
                );
            }
        }
    }

    /**
     *
     */
    public function testColorSpaceSetRgbaToInt() {
        //return;
        $this -> assertEquals(0x00000000, AP5L_Gfx_ColorSpace::rgbaToInt(0, 0, 0, 0));
        $this -> assertEquals(0x00010000, AP5L_Gfx_ColorSpace::rgbaToInt(1.0/255, 0, 0, 0));
        $this -> assertEquals(0x00000100, AP5L_Gfx_ColorSpace::rgbaToInt(0, 1.0/255, 0, 0));
        $this -> assertEquals(0x00000001, AP5L_Gfx_ColorSpace::rgbaToInt(0, 0, 1.0/255, 0));
        $this -> assertEquals(0x01000000, AP5L_Gfx_ColorSpace::rgbaToInt(0, 0, 0, 1.0/127));
    }

    /**
     *
     */
    public function testColorSpaceSetRgb() {
        //return;
        $col = &$this -> fixture;
        $this -> assertEquals(0x00000000, $col -> getRgbaInt());
        $col -> setRgb(1, 0, 0);
        $this -> assertEquals(0x00010000, $col -> getRgbaInt());
        $col -> setRgb(array(1, 0, 0));
        $this -> assertEquals(0x00010000, $col -> getRgbaInt());
        $col -> setRgb(0, 1, 0);
        $this -> assertEquals(0x00000100, $col -> getRgbaInt());
        $col -> setRgb(array(0, 1, 0));
        $this -> assertEquals(0x00000100, $col -> getRgbaInt());
        $col -> setRgb(0, 0, 1);
        $this -> assertEquals(0x00000001, $col -> getRgbaInt());
        $col -> setRgb(array(0, 0, 1));
        $this -> assertEquals(0x00000001, $col -> getRgbaInt());
        $col -> setRgb(1, 0, 1);
        $this -> assertEquals(0x00010001, $col -> getRgbaInt());
        $col -> setRgb(array(1, 0, 1));
        $this -> assertEquals(0x00010001, $col -> getRgbaInt());
        $col -> setRgb(1, 1, 0);
        $this -> assertEquals(0x00010100, $col -> getRgbaInt());
        $col -> setRgb(array(1, 1, 0));
        $this -> assertEquals(0x00010100, $col -> getRgbaInt());
        $col -> setRgb(1, 1, 1);
        $this -> assertEquals(0x00010101, $col -> getRgbaInt());
        $col -> setRgb(array(1, 1, 1));
        $this -> assertEquals(0x00010101, $col -> getRgbaInt());
    }

    /**
     *
     */
    public function testColorSpaceSetRgba() {
        //return;
        $col = &$this -> fixture;
        $this -> assertEquals(0x00000000, $col -> getRgbaInt());
        $col -> setRgba(1, 0, 0, 1);
        $this -> assertEquals(0x01010000, $col -> getRgbaInt());
        $col -> setRgba(array(1, 0, 0, 1));
        $this -> assertEquals(0x01010000, $col -> getRgbaInt());
        $col -> setRgba(0, 1, 0, 1);
        $this -> assertEquals(0x01000100, $col -> getRgbaInt());
        $col -> setRgba(array(0, 1, 0, 1));
        $this -> assertEquals(0x01000100, $col -> getRgbaInt());
        $col -> setRgba(0, 0, 1, 1);
        $this -> assertEquals(0x01000001, $col -> getRgbaInt());
        $col -> setRgba(array(0, 0, 1, 1));
        $this -> assertEquals(0x01000001, $col -> getRgbaInt());
        $col -> setRgba(1, 0, 1, 1);
        $this -> assertEquals(0x01010001, $col -> getRgbaInt());
        $col -> setRgba(array(1, 0, 1, 1));
        $this -> assertEquals(0x01010001, $col -> getRgbaInt());
        $col -> setRgba(1, 1, 0, 1);
        $this -> assertEquals(0x01010100, $col -> getRgbaInt());
        $col -> setRgba(array(1, 1, 0, 1));
        $this -> assertEquals(0x01010100, $col -> getRgbaInt());
        $col -> setRgba(1, 1, 1, 1);
        $this -> assertEquals(0x01010101, $col -> getRgbaInt());
        $col -> setRgba(array(1, 1, 1, 1));
        $this -> assertEquals(0x01010101, $col -> getRgbaInt());
    }

}

// Call ColorSpaceTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'ColorSpaceTest::main') {
    ColorSpaceTest::main();
}
?>
