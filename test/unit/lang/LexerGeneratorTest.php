<?php
/**
 * Unit tests for PHP_LexerGenerator.
 *
 * @author Alan Langford <jal@ambitonline.com>
 * @package PHP_LexerGenerator
 * @version $Id: LexerGeneratorTest.php 246683 2007-11-22 04:43:52Z instance $
 */

// Set up to call main if this file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'LexerGeneratorTest::main');
}

require_once 'PHPUnit/Framework.php';

$base = dirname(__FILE__);
$base = str_replace('\\', '/', $base);
while (! class_exists('AP5L', false)) {
    $lib = $base . '/src/AP5L.php';
    if (file_exists($lib)) {
        require_once $lib;
        AP5L::install();
        break;
    }
    if ($base == dirname($base)) {
        echo 'Unable to find AP5L';
        exit(1);
    }
    $base = dirname($base);
}


/**
 * Tests for the LexerGenerator.
 *
 * This class contains tests that verify either the PHP code generated by a
 * lexer definition, or verify the code and the operation of the generated
 * lexer.
 * @version @package_version@
 */
class LexerGeneratorTest extends PHPUnit_Framework_TestCase {
    public $basePath;
    public $dataPath;
    public $writeTestNames;

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
     * Use the lexer to generate a new PHP file and to compare it with the
     * production version. Any variance is reported in a diff file.
     *
     * @param string Name of the source .plex file.
     * @param string Name of the generated PHP file.
     * @param string Name of the expected PHP file.
     * @param string Name of the file to save differences in.
     * @return boolean True if actual and expected files match.
     */
    public function runCodeTestCore($plexFile, $phpFile, $expectFile, $diffFile) {
        $lex = new AP5L_Lang_LexerGenerator($plexFile, $phpFile);
        $actual = str_replace("\r\n", "\n", file_get_contents($phpFile));
        $expect = str_replace("\r\n", "\n", file_get_contents($expectFile));
        if (md5($expect) != md5($actual)) {
            // Turn strict off for a bit...
            $errLev = error_reporting();
            error_reporting($errLev & ~E_STRICT);
            require_once 'Text/Diff.php';
            require_once 'Text/Diff/Renderer.php';
            require_once 'Text/Diff/Renderer/unified.php';
            $actual = explode("\n", $actual);
            $expect = explode("\n", $expect);
            $diff = new Text_Diff('auto', array($expect, $actual));
            $renderer = new Text_Diff_Renderer_unified();
            file_put_contents($diffFile, $renderer -> render($diff));
            error_reporting($errLev);
            return false;
        }
        return true;
    }

    /**
     * Run the generated lexer against a test string to verify that it operates
     * as expected.
     *
     * @param string Name of the test. This is used to determine class and file
     * names.
     * @param string The input test string.
     * @param string The expected output.
     */
    public function runLexerTestCore($testName, $data, $expect) {
        include $this -> dataPath . $testName . '.php';
        $testClass = 'UnitTest' . $testName . 'Parser';
        ob_start();
        $lex = new $testClass($data);
        while($lex->yylex() != false)
        {
        }
        $actual = ob_get_clean();
        $this -> assertEquals($expect, $actual);
    }

    public function setUp() {
        $this -> basePath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        $this -> dataPath = $this -> basePath . 'data' . DIRECTORY_SEPARATOR;
        $this -> writeTestNames = true;
    }

// This is a template for new tests.
//    public function test() {
//        $this->markTestIncomplete('coming soon.');
//    }

    /**
     * Generate a lexer for the bug reports and make sure the code matches
     * what is expected.
     */
    public function testParseBugFixes() {
        return;
        if ($this -> writeTestNames) {
            echo __METHOD__ . ' ';
        }
        $bugList = array();
        $dh = opendir('.');
        while (($file = readdir($dh)) !== false) {
            if (preg_match('/bug([0-9]+)\.plex/', $file, $match)) {
                $bugList[$match[1]] = $file;
            }
        }
        ksort($bugList);
        print_r($bugList);
        $d = AP5L_Debug_Stdout::getInstance();
        $d -> setState('', true);
        AP5L::setDebug($d);
        $failMsg = '';
        $delim = 'Failed validating fixes to: ';
        foreach ($bugList as $bugNum => $plexFile) {
            $diffFile = $this -> dataPath . 'bug' . $bugNum . '.diff';
            echo 'process ' . $bugNum . chr(10);
            if ($this -> runCodeTestCore(
                $plexFile,
                $this -> dataPath . 'bug' . $bugNum . '.php',
                'bug' . $bugNum . '.php',
                $diffFile
            )) {
                @unlink($diffFile);
                @unlink($this -> dataPath . 'bug' . $bugNum . '.php');
            } else {
                $failMsg .= $delim . $plexFile;
                $delim = ', ';
            }
        }
        if ($failMsg) {
            $this -> fail($failMsg . ' See ' . $this -> dataPath . 'bug*.* files for details.');
        }
    }

    /**
     * Execute the lexer for bug reports with a phpt file and make sure they
     * generate what is expected.
     */
    public function testRunBugFixes() {
        $bugList = array();
        $dh = opendir('.');
        while (($file = readdir($dh)) !== false) {
            if (preg_match('/bug([0-9]+)\.phpt/', $file, $match)) {
                $bugList[$match[1]] = $file;
            }
        }
        ksort($bugList);
        foreach ($bugList as $bugNum => $testFile) {
            if ($this -> writeTestNames) {
                echo __METHOD__ . '(' . $testFile . ') ';
            }
            $test = str_replace("\r\n", "\n", file_get_contents($testFile));
            // This is a very crude extraction method that should be improved
            if (($codePos = strpos($test, chr(10) . '--FILE--' . chr(10))) === false) {
                @unlink('testcase.temp.php');
                $this -> fail('Unable to find --FILE-- marker in ' . $testFile);
            }
            $codeStart = $codePos + strlen('n--FILE--n');
            if (($expectPos = strpos($test, chr(10) . '--EXPECT--' . chr(10))) === false) {
                @unlink('testcase.temp.php');
                $this -> fail('Unable to find --EXPECT-- marker in ' . $testFile);
            }
            $expect = substr($test, $expectPos + strlen('n--EXPECT--n'));
            file_put_contents(
                'testcase.temp.php',
                substr($test, $codeStart, $expectPos - $codeStart)
            );
            ob_start();
            include 'testcase.temp.php';
            $actual = ob_get_clean();
            $this -> assertEquals($expect, $actual, 'Running bug ' . $bugNum);
        }
        @unlink('testcase.temp.php');
    }

    /**
     * Test use of caseinsensitive processing instruction for case-less tokens.
     */
    public function testCaseInsensitive() {
        if ($this -> writeTestNames) {
            echo __METHOD__ . ' ';
        }
        $diffFile = $this -> dataPath . 'CaseInsensitive.diff';
        if ($this -> runCodeTestCore(
            $this -> dataPath . 'CaseInsensitive.plex',
            $this -> dataPath . 'CaseInsensitive.php',
            $this -> dataPath . 'CaseInsensitive.expect.php',
            $diffFile
        )) {
            @unlink($diffFile);
        } else {
            $this -> fail('Output mismatch. See ' . $diffFile . ' for details.');
        }
        $this -> runLexerTestCore(
            'CaseInsensitive',
            'test word TEST WORD TeSt',
            'test: test<br>word: word<br>test: TEST<br>word: WORD<br>test: TeSt<br>'
        );
    }

    /**
     * Test (a copy of) the RegexLexer.
     */
    public function testRegex() {
        if ($this -> writeTestNames) {
            echo __METHOD__ . ' ';
        }
        $diffFile = $this -> dataPath . 'RegexLexer.diff';
        if ($this -> runCodeTestCore(
            $this -> dataPath . 'RegexLexer.plex',
            $this -> dataPath . 'RegexLexer.php',
            $this -> dataPath . 'RegexLexer.expect.php',
            $diffFile
        )) {
            @unlink($diffFile);
        } else {
            $this -> fail('Output mismatch. See ' . $diffFile . ' for details.');
        }
    }

    /**
     * Test use of single quotes for case-less tokens.
     */
    public function testSingleQuote() {
        $diffFile = $this -> dataPath . 'SingleQuote.diff';
        if ($this -> runCodeTestCore(
            $this -> dataPath . 'SingleQuote.plex',
            $this -> dataPath . 'SingleQuote.php',
            $this -> dataPath . 'SingleQuote.expect.php',
            $diffFile
        )) {
            @unlink($diffFile);
        } else {
            $this -> fail('Output mismatch. See ' . $diffFile . ' for details.');
        }
        $this -> runLexerTestCore(
            'SingleQuote',
            'test word TEST WORD TeSt',
            'test: test<br>word: word<br>test: TEST<br>word: WORD<br>test: TeSt<br>'
        );
    }

    /**
     * Test use of single quotes for case-less tokens.
     */
    public function testUnicode() {
        if ($this -> writeTestNames) {
            echo __METHOD__ . ' ';
        }
        $diffFile = $this -> dataPath . 'Unicode.diff';
        if ($this -> runCodeTestCore(
            $this -> dataPath . 'Unicode.plex',
            $this -> dataPath . 'Unicode.php',
            $this -> dataPath . 'Unicode.expect.php',
            $diffFile
        )) {
            @unlink($diffFile);
        } else {
            $this -> fail('Output mismatch. See ' . $diffFile . ' for details.');
        }
        $this -> runLexerTestCore(
            'Unicode',
            'testω ωord TESTω WORD TeStω',
            'test: testω<br>word: ωord<br>test: TESTω<br>word: WORD<br>test: TeStω<br>'
        );
    }

}

// Call main if this file is executed directly.
if (PHPUnit_MAIN_METHOD == 'LexerGeneratorTest::main') {
    LexerGeneratorTest::main();
}
