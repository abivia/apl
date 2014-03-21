<?php
/**
 * Unit tests for AP5L_Text_Css_Patch
 *
 * @package AP5L
 * @subpackage QC
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2009, Alan Langford
 * @version $Id: $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call TextCssPatchTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'TextCssPatchTest::main');
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

class MyPatch extends AP5L_Text_Scc_Patch {

    protected function _message($message) {
        echo $message . AP5L::LF;
    }

}

/**
 * Test class for AP5L_Text_Scc_Patch.
 */
class TextCssPatchTest extends PHPUnit_Framework_TestCase {

    /**
     * Runs the test methods of this class.
     *
     */
    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite(__CLASS__);
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
     * Test patchBuffer, which exercises the core functionality.
     */
    function testPatchBuffer() {
        // Clean out the working directory
        AP5L_Filesystem_Directory::delete('_data_work');
        AP5L_Filesystem_Directory::copy('_data_orig', '_data_work');
        $patch = MyPatch::factory('Udiff', 'MyPatch');
        $patch -> setBasePath('_data_work');
        $patch -> patchFile('_data_patch/0001.diff');
        $this -> assertEquals(
            md5(file_get_contents('_data_good/CodeFix.php')),
            md5(file_get_contents('_data_work/CodeFix.php')),
            'CodeFix.php'
        );
        $this -> assertEquals(
            md5(file_get_contents('_data_good/ZipDirectory.php')),
            md5(file_get_contents('_data_work/ZipDirectory.php')),
            'ZipDirectory.php'
        );
    }

}

// Call TextCssPatchTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'TextCssPatchTest::main') {
    TextCssPatchTest::main();
}
