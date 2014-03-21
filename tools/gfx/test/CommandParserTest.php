<?php
/**
 * Command parser unit tests.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: CommandParserTest.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call CommandParserTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'CommandParserTest::main');
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

require_once '../lib/CommandParser.php';

/**
 * Test class for CommandParser.
 */
class CommandParserTest extends PHPUnit_Framework_TestCase {

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('CommandParserTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp() {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }

    /**
     * Test hex string to color parsing
     */
    public function testCommandParserColorHex() {
        //return;
        $col = IFT_CommandParser::colorParse('#000000');
        $this -> assertEquals(0x00000000, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#000001');
        $this -> assertEquals(0x00000001, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#000100');
        $this -> assertEquals(0x00000100, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#010000');
        $this -> assertEquals(0x00010000, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#01000000');
        $this -> assertEquals(0x01000000, $col -> getRgbaInt());
    }

    /**
     * Test RGB string to color parsing
     */
    public function testCommandParserColorRgb() {
        //return;
        //$col = IFT_CommandParser::colorParse('#r(0, 0, 0)');
        //$this -> assertEquals(0x00000000, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#r(0, 0, 1)');
        $this -> assertEquals(0x00000001, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#r(0, 1, 0)');
        $this -> assertEquals(0x00000100, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#r(1, 0, 0)');
        $this -> assertEquals(0x00010000, $col -> getRgbaInt());
        $col = IFT_CommandParser::colorParse('#r(0, 0, 0, 1)');
        $this -> assertEquals(0x01000000, $col -> getRgbaInt());
    }

    /**
     * Test symbol resolution
     */
    public function testCommandParserSumbol() {
        $sym = array (
            'anum' => 5,
            'cref' => '#404040',
            'inum' => '$anum',
            'icref' => '$cref',
        );
        $this -> assertEquals(5, IFT_CommandParser::symbolResolve($sym, '$anum'));
        $this -> assertEquals(5, IFT_CommandParser::symbolResolve($sym, '$inum'));
        $result = IFT_CommandParser::symbolResolve($sym, '$cref');
        $this -> assertTrue($result instanceof AP5L_Gfx_ColorSpace);
        $this -> assertEquals(0x404040, $result -> getRgbaInt());
        $result = IFT_CommandParser::symbolResolve($sym, '$icref');
        $this -> assertTrue($result instanceof AP5L_Gfx_ColorSpace);
        $this -> assertEquals(0x404040, $result -> getRgbaInt());
    }

}

// Call CommandParserTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'CommandParserTest::main') {
    CommandParserTest::main();
}
?>
