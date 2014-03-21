<?php
/**
 * Unit tests for image fills. Note that the generated images may have minor
 * variations by platform thanks to rounding errors and precision variations.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ImageFillTest.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call ImageFillTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ImageFillTest::main');
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

if (! class_exists('AP5L', false)) {
    $base = dirname(__FILE__);
    $base = str_replace('\\', '/', $base);
    require_once $base . '/../../../src/AP5L.php';
    AP5L::install();
}

/**
 * Test class for ImageFill.
 */
class ImageFillTest extends PHPUnit_Framework_TestCase {
    /**
     * Write new "expected" results.
     *
     * When set, this causes the tests to write new "expected" images, thus
     * causing most tests to pass. Use to replace the baseline images.
     *
     * @var boolean
     */
    protected $_newExpect = false;

    /**
     * Fill area
     *
     * @var AP5L_Math_Vector2d
     */
    public $area;

    /**
     * Main object for testing.
     *
     * @var AP5L_Gfx_ImageFill
     */
    public $fixture;

    public $from;
    public $to;

    /**
     * Compare two image files.
     *
     * Since libraries have some rounding issues, we try for an exact match of files
     * by comparing MD5 checksums, but if that fails, we perform a pixel by pixel
     * comparison, where each color component (including alpha) can differ from
     * the expected result by one.
     *
     * @param string Path to the expected file.
     * @param string Path to the actual file.
     * @param string Name of the test.
     * @return void
     */
    protected function _compareImageFiles($expectFid, $actualFid, $testName) {
        if (md5_file($expectFid) == md5_file($actualFid)) {
            return;
        }
        $expectIm = @imagecreatefrompng($expectFid);
        $actualIm = @imagecreatefrompng($actualFid);
        $sizeX = imagesx($expectIm);
        $this -> assertEquals($sizeX, imagesx($actualIm), 'X size ' . $testName);
        $sizeY = imagesy($expectIm);
        $this -> assertEquals($sizeY, imagesy($actualIm), 'Y size ' . $testName);
        $fails = 0;
        $msg = '';
        for ($y = 0; $y < $sizeY; ++$y) {
            for ($x = 0; $x < $sizeX; ++$x) {
                $expectPx = imagecolorat($expectIm, $x, $y);
                $actualPx = imagecolorat($actualIm, $x, $y);
                if ($expectPx != $actualPx) {
                    $fail = false;
                    $diff00 = ($expectPx & 0x0FF) - ($actualPx & 0x0FF);
                    $diff08 = (($expectPx >> 8) & 0x0FF) - (($actualPx >> 8) & 0x0FF);
                    $diff16 = (($expectPx >> 16) & 0x0FF) - (($actualPx >> 16) & 0x0FF);
                    $diff24 = (($expectPx >> 24) & 0x0FF) - (($actualPx >> 24) & 0x0FF);
                    if (
                        (abs($diff00) > 1) || (abs($diff08) > 1)
                        || (abs($diff16) > 1) || (abs($diff24) > 1)
                    ) {
                        $msg .= 'At ' . $x . ' ' . $y
                            . ' ' . dechex($expectPx)
                            . ' ' . dechex($actualPx)
                            . AP5L::LF;
                        // Adjustable error threshold...
                        if (++$fails > 0) {
                            $this -> fail($testName . AP5L::LF . $msg);
                        }
                    }
                }
            }
        }
    }

    protected function _linearBandedTest($angle) {
        //return;
        $im = &$this -> fixture;
        $pad = sprintf('%03d', $angle);
        AP5L_Gfx_ImageFill::linear(
            $im, $this -> area, $angle, $this -> from, $this -> to,
            array('bands' => 3)
        );
        $base = dirname(__FILE__);
        $base .= AP5L::DS . 'data' . AP5L::DS . 'linearbanded' . $pad;
        $expectFid = $base . '.expect.png';
        if ($this -> _newExpect) {
            $im -> write($expectFid);
        }
        $actualFid = $base . '.actual.png';
        $im -> write($actualFid);
        $this -> _compareImageFiles($expectFid, $actualFid, $angle . ' degrees mid-band');
        unlink($actualFid);
    }

    protected function _linearEndBandTest($angle) {
        //return;
        $im = &$this -> fixture;
        $pad = sprintf('%03d', $angle);
        AP5L_Gfx_ImageFill::linear(
            $im, $this -> area, $angle, $this -> from, $this -> to,
            array('bands' => 3, 'bandmidpoint' => false, 'debug' => false)
        );
        $base = dirname(__FILE__);
        $base .= AP5L::DS . 'data' . AP5L::DS . 'linearendband' . $pad;
        $expectFid = $base . '.expect.png';
        if ($this -> _newExpect) {
            $im -> write($expectFid);
        }
        $actualFid = $base . '.actual.png';
        $im -> write($actualFid);
        $this -> _compareImageFiles($expectFid, $actualFid, $angle . ' degrees end-band');
        unlink($actualFid);
    }

    protected function _linearTest($angle) {
        //return;
        $im = &$this -> fixture;
        $pad = sprintf('%03d', $angle);
        AP5L_Gfx_ImageFill::linear($im, $this -> area, $angle, $this -> from, $this -> to);
        $base = dirname(__FILE__);
        $base .= AP5L::DS . 'data' . AP5L::DS . 'linear' . $pad;
        $expectFid = $base . '.expect.png';
        if ($this -> _newExpect) {
            $im -> write($expectFid);
        }
        $actualFid = $base . '.actual.png';
        $im -> write($actualFid);
        $this -> _compareImageFiles($expectFid, $actualFid, $angle . ' degrees unbanded');
        unlink($actualFid);
    }

    protected function _rectangularTest($subType, $xr, $yr) {
        //return;
        $im = &$this -> fixture;
        $focus = new AP5L_Math_Point2d(
            floor($this -> area -> direction -> x * $xr),
            floor($this -> area -> direction -> y * $yr)
        );
        AP5L_Gfx_ImageFill::rectangular(
            $im, $subType, $this -> area, $focus, $this -> from, $this -> to,
            array()
        );
        $pad = sprintf('%s_%03d_%03d', $subType, $focus -> x, $focus -> y);
        $base = dirname(__FILE__);
        $base .= AP5L::DS . 'data' . AP5L::DS . 'rect' . $pad;
        $expectFid = $base . '.expect.png';
        if ($this -> _newExpect) {
            $im -> write($expectFid);
        }
        $actualFid = $base . '.actual.png';
        $im -> write($actualFid);
        $this -> _compareImageFiles($expectFid, $actualFid, $pad . ' rectangular');
        unlink($actualFid);
    }

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('ImageFillTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
        $this -> fixture = new AP5L_Gfx_Image(640, 640);
        $this -> fixture -> setAlphaBlending(true);
        $draw = new AP5L_Gfx_ColorSpace(1.0, 1.0, 1.0);
        for ($c = 0; $c < 640; $c += 64) {
            $this -> fixture -> line($c, 0, $c, 639, $draw);
            $this -> fixture -> line(0, $c, 639, $c, $draw);
        }
        $draw = new AP5L_Gfx_ColorSpace(0.0, 0.0, 1.0);
        for ($c = 32; $c < 640; $c += 64) {
            $this -> fixture -> line($c, 0, $c, 639, $draw);
            $this -> fixture -> line(0, $c, 639, $c, $draw);
        }
        $this -> area = AP5L_Math_Vector2d::factoryI4Rel(112, 112, 160, 320);
        $this -> from = new AP5L_Gfx_ColorSpace(1.0, 0.5, 0.5, 0.1);
        $this -> to = new AP5L_Gfx_ColorSpace(0.5, 1.0, 0.5, 0.2);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        $this -> fixture -> destroy();
    }

    /**
     *
     */
    public function testImageFillFlat() {
        //return;
        $im = &$this -> fixture;
        AP5L_Gfx_ImageFill::flat($im, $this -> area, $this -> from);
        $base = dirname(__FILE__);
        $base .= AP5L::DS . 'data' . AP5L::DS . 'flat';
        $expectFid = $base . '.expect.png';
        if ($this -> _newExpect) {
            $im -> write($expectFid);
        }
        $actualFid = $base . '.actual.png';
        $im -> write($actualFid);
        $this -> _compareImageFiles($expectFid, $actualFid, 'flat');
        unlink($actualFid);
    }

    /**
     *
     */
    public function testImageFillImageBorder1() {
        return; // for some reason the underlying class does not exist??
        $border = AP5L_Gfx_Image::factory(2,2);
        $border -> setPixel(0, 0, 0x00FF0000);
        $border -> setPixel(1, 0, 0x0000FF00);
        $border -> setPixel(0, 1, 0x0000FF00);
        $border -> setPixel(1, 1, 0x000000FF);
        $im = &$this -> fixture;
        AP5L_Gfx_ImageFill::imageBorder($im, $this -> area, 1, $border);
        $pad = '1';
        $base = dirname(__FILE__);
        $base .= AP5L::DS . 'data' . AP5L::DS . 'imageborder' . $pad;
        $expectFid = $base . '.expect.png';
        if ($this -> _newExpect) {
            $im -> write($expectFid);
        }
        $actualFid = $base . '.actual.png';
        $im -> write($actualFid);
        $this -> _compareImageFiles($expectFid, $actualFid, 'imageborder' . $pad);
        unlink($actualFid);
    }

    /**
     *
     */
    public function testImageFillLinear_000() {
        $this -> _linearTest(0);
    }

    /**
     *
     */
    public function testImageFillLinear_030() {
        $this -> _linearTest(30);
    }

    /**
     *
     */
    public function testImageFillLinear_060() {
        $this -> _linearTest(60);
    }

    /**
     *
     */
    public function testImageFillLinear_090() {
        $this -> _linearTest(90);
    }

    /**
     *
     */
    public function testImageFillLinear_120() {
        $this -> _linearTest(120);
    }

    /**
     *
     */
    public function testImageFillLinear_150() {
        $this -> _linearTest(150);
    }

    /**
     *
     */
    public function testImageFillLinear_180() {
        $this -> _linearTest(180);
    }

    /**
     *
     */
    public function testImageFillLinear_210() {
        $this -> _linearTest(210);
    }

    /**
     *
     */
    public function testImageFillLinear_240() {
        $this -> _linearTest(240);
    }

    /**
     *
     */
    public function testImageFillLinear_270() {
        $this -> _linearTest(270);
    }

    /**
     *
     */
    public function testImageFillLinear_300() {
        $this -> _linearTest(300);
    }

    /**
     *
     */
    public function testImageFillLinear_330() {
        $this -> _linearTest(330);
    }

    /**
     *
     */
    public function testImageFillLinear_360() {
        $this -> _linearTest(360);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_000() {
        $this -> _linearBandedTest(0);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_030() {
        $this -> _linearBandedTest(30);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_060() {
        $this -> _linearBandedTest(60);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_090() {
        $this -> _linearBandedTest(90);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_120() {
        $this -> _linearBandedTest(120);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_150() {
        $this -> _linearBandedTest(150);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_180() {
        $this -> _linearBandedTest(180);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_210() {
        $this -> _linearBandedTest(210);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_240() {
        $this -> _linearBandedTest(240);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_270() {
        $this -> _linearBandedTest(270);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_300() {
        $this -> _linearBandedTest(300);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_330() {
        $this -> _linearBandedTest(330);
    }

    /**
     *
     */
    public function testImageFillLinearBanded_360() {
        $this -> _linearBandedTest(360);
    }

    public function testImageFillLinearEndBand_030() {
        $this -> _linearEndBandTest(30);
    }

    /**
     *
     */
    public function testImageFillRectangularCos_050_050() {
        $this -> _rectangularTest('cos', 0.5, 0.5);
    }

    /**
     *
     */
    public function testImageFillRectangularLinear_050_050() {
        $this -> _rectangularTest('linear', 0.5, 0.5);
    }

    /**
     *
     */
    public function testImageFillRectangularPower_050_050_050() {
        $this -> _rectangularTest('pow0.5', 0.5, 0.5);
    }

    /**
     *
     */
    public function testImageFillRectangularPower_300_050_050() {
        $this -> _rectangularTest('pow3', 0.5, 0.5);
    }

    /**
     *
     */
    public function testImageFillRectangularSin_050_050() {
        $this -> _rectangularTest('sin', 0.5, 0.5);
    }

}

// Call ImageFillTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'ImageFillTest::main') {
    ImageFillTest::main();
}
?>
