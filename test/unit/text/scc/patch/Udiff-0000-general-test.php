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

// Call TextCssPatchUdiffTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'TextCssPatchUdiffTest::main');
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

class HostMock {

    protected function _lineMatch($left, $right) {
        return $left == $right;
    }

    function contextMatch($buffer, $chunk, $shift = 0) {
        $start = $chunk['new']['start'] + $shift - 1;
        for ($ind = 0; $ind < $chunk['old']['size']; ++$ind) {
            $checkLine = $start + $ind;
            if (!isset($buffer[$checkLine]) || !isset($chunk['old']['lines'][$ind])) {
                return -2;
            }
            if (!$this -> _lineMatch($buffer[$checkLine], $chunk['old']['lines'][$ind])) {
                return -1;
            }
        }
        return $start;
    }

    function getFuzziness() {
        return 3;
    }

    function getStateFrom() {
        return 'old';
    }

    function getStateTo() {
        return 'new';
    }

    function onPatchDone(&$buffer, &$patchChunks) {
        return true;
    }

    function onPatchStart(&$buffer, &$patchChunks) {
        return true;
    }


}

/**
 * Test class for AP5L_Text_Scc_Patch.
 */
class TextCssPatchUdiffTest extends PHPUnit_Framework_TestCase {

    /**
     * Create a data set for applyBuffer.
     *
     * @return array
     */
    function dataSetApplyBuffer() {
        /*
         * Each element is input string, expected result
         */
        $infile =
'Line 1
Line 2
Line 3
Line 4
Line 5
Line 6
Line 7
Line 8
Line 9
Line 10
';
        $tests = array(
            array(
                'No complications.',
                $infile,
'@@ -2,7 +2,7 @@
 Line 2
 Line 3
 Line 4
-Line 5
+New Line 5
 Line 6
 Line 7
 Line 8
',
'Line 1
Line 2
Line 3
Line 4
New Line 5
Line 6
Line 7
Line 8
Line 9
Line 10
',
            ),
            //array(
            //),
        );
        return $tests;
    }

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
        $this -> host = new HostMock;
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown() {
    }

    /**
     * Test applyBuffer, which exercises the core functionality.
     *
     * @dataProvider dataSetApplyBuffer
     */
    function testApplyBuffer($caseName, $input, $chunks, $expect) {
        $patch = new AP5L_Text_Scc_Patch_Udiff();
        $patch -> setHost($this -> host);
        $actual = $patch -> applyBuffer($input, $chunks);
        $this -> assertEquals($expect, $actual, $caseName);
    }

    /**
     * Test case were input file has no eol on last line and it's retained.
     *
     * @return void
     */
    function testEol00() {
        $input =
'Line 1
line2';
        $chunks =
'@@ -2 +2 @@
-line2
\ No newline at end of file
+newline2
\ No newline at end of file
';
        $expect =
'Line 1
newline2';
        $patch = new AP5L_Text_Scc_Patch_Udiff();
        $patch -> setHost($this -> host);
        $actual = $patch -> applyBuffer($input, $chunks);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test case were input file has no eol on last line and one is added.
     *
     * @return void
     */
    function testEol01() {
        $input =
'Line 1
line2';
        $chunks =
'@@ -2 +2,2 @@
-line2
\ No newline at end of file
+newline2
+
';
        $expect =
'Line 1
newline2
';
        $patch = new AP5L_Text_Scc_Patch_Udiff();
        $patch -> setHost($this -> host);
        $actual = $patch -> applyBuffer($input, $chunks);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test case were input file has eol on last line it is removed.
     *
     * @return void
     */
    function testEol10() {
        $input =
'Line 1
line2
';
        $chunks =
'@@ -2,2 +2 @@
-line2
-
+newline2
\ No newline at end of file
';
        $expect =
'Line 1
newline2';
        $patch = new AP5L_Text_Scc_Patch_Udiff();
        $patch -> setHost($this -> host);
        $actual = $patch -> applyBuffer($input, $chunks);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test case were input file has eol on last line and it's retained.
     *
     * @return void
     */
    function testEol11() {
        $input =
'Line 1
line2
';
        $chunks =
'@@ -2 +2 @@
-line2
+newline2
';
        $expect =
'Line 1
newline2
';
        $patch = new AP5L_Text_Scc_Patch_Udiff();
        $patch -> setHost($this -> host);
        $actual = $patch -> applyBuffer($input, $chunks);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test patchParser
     */
    function testPatchParser() {
        $expect = array(
            array(
                'headers' => array(),
                'patch' => array(
'@@ -27,6 +27,8 @@',
'             array(\'/=>\s+/\', \'=> \'),                    // Whitespace after assignment',
'             array(\'/@(param|return)\s+([a-z0-9_\-$]+)\s+/i\', \'@\1 \2 \'),',
'             array(\'/([a-z0-9_\)\]])\s*->/i\', \'\1 ->\'),',
'+            array(\'/->\s*/\', \'-> \'),',
'+            array(\'_^(\s*\*\s*@\w+)\s+_\', \'\1 \'),       // Whitespace after phpdoc directives',
'             array(\'_case\s*\(\s*([^\)]+)\s*\)\s*:_i\', \'case \1:\'), // redundant () in case',
'             );',
'         // Convert tha maps into something easily iterated',
'@@ -143,6 +145,10 @@',
'         if ($lastKey != -1 && trim($buffer[$lastKey]) == \'?>\') {',
'             unset($buffer[$lastKey]);',
'         }',
'+        // Kill trailing blank lines again',
'+        while (count($buffer) && trim(end($buffer)) == \'\') {',
'+            unset($buffer[key($buffer)]);',
'+        }',
'         $buffer = implode(AP5L::LF, $buffer) . AP5L::LF;',
'         file_put_contents($path, $buffer);',
'     }',
'@@ -170,6 +176,7 @@',
'                 //\'directories\' => \'first\'',
'             )',
'         );',
'+        $disp -> unlisten($this);',
'     }',
' ',
'     /**',
                ),

                'old' => array('CodeFix.php', 'Thu Jan 15 04:14:12 1970'),
                'new' => array('CodeFix.php', 'Thu Jan 15 04:14:12 1970'),
            ),
            array(
                'headers' => array(),
                'patch' => array(
'@@ -3,6 +3,12 @@',
' class ZipDirectory extends AP5L_Php_InflexibleObject {',
'     protected $_zip;',
' ',
'+    /**',
'+     * Add file event handler.',
'+     *',
'+     * @param array Information on the source file.',
'+     * @return boolean True if the file should be added.',
'+     */',
'     function onAddFile($info) {',
'         return true;',
'     }',
'@@ -31,6 +37,8 @@',
'                 \'directories\' => \'first\'',
'             )',
'         );',
'+        $disp -> unlisten($this);',
'+        $this -> _zip -> close();',
'     }',
' ',
'     /**',
'',
                ),
                'old' => array('ZipDirectory.php', 'Thu Jan 15 04:14:12 1970'),
                'new' => array('ZipDirectory.php', 'Thu Jan 15 04:14:12 1970'),
            )
        );

        $patch = new AP5L_Text_Scc_Patch_Udiff();
        $patch -> setHost($this -> host);
        $data = file_get_contents(dirname(__FILE__) . '/../_data_patch/0001.diff');
        $data = explode(AP5L::LF, $data);
        $actual = $patch -> patchParser($data);
        $this -> assertEquals($expect, $actual);
    }

}

// Call TextCssPatchUdiffTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'TextCssPatchUdiffTest::main') {
    TextCssPatchUdiffTest::main();
}
