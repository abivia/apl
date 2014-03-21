<?php
/**
 * Collect all unit tests.
 *
 * This file runs all unit tests when invoked directly. Otherwise it provides
 * the LexerGenerator_AllTests class to a test runner.
 *
 * @author Alan Langford <jal@ambitonline.com>
 * @package PHP_LexerGenerator
 * @version $Id: AllTests.php 246683 2007-11-22 04:43:52Z instance $
 */


if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'LexerGenerator_AllTests::main');
}

require_once 'PHPUnit/Framework.php';
//require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once dirname(__FILE__) . '../Lexer.php';

/**
 * Collects all the test cases and suites for PHP_LexerGenerator.
 *
 * @version @package_version@
 */
class LexerGenerator_AllTests
{
    public static function main()
    {
        error_reporting(E_ALL|E_STRICT);
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PEAR - PHP_LexerGenerator');

        $suite->addTestFile('LexerTest.php');
        $suite->addTestFile('LexerGeneratorTest.php');

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'LexerGenerator_AllTests::main') {
    LexerGenerator_AllTests::main();
}
