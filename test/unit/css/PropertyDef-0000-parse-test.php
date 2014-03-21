<?php
/**
 * Unit tests for AP5L_Css_PropertyDef parse functions
 *
 * @package AP5L
 * @subpackage QC
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2009, Alan Langford
 * @version $Id: $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call PropertyDefParseTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'PropertyDefParseTest::main');
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

/**
 * Test class for AP5L_Css_PropertyDef parse functions.
 */
class PropertyDefParseTest extends PHPUnit_Framework_TestCase {

    /**
     * Convert integer indexed result sets to string indexes.
     *
     * @param array A set of test cases.
     * @return array Same test cases with indexes updated.
     */
    protected function _reindex($tests) {
        foreach ($tests as &$test) {
            if (is_array($test[1])) {
                if (count($test[1]) == 3) {
                    $test[1] = array_combine(array('val', 'unit', 'buffer'), $test[1]);
                } else {
                    $test[1] = array_combine(array('val', 'unit', 'buffer', 'more'), $test[1]);
                }
            }
        }
        return $tests;
    }

    /**
     * Create a data set for parse angle.
     *
     * @return array
     */
    function dataSetAngle() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('0', array('0', '', '')),
            array('0 ', array('0', '', '')),
            array('0 foo', array('0', '', 'foo')),
            array('0deg', array('0', 'deg', '')),
            array('0deg.', array('0', 'deg', '.')),
            array('0de', false),
            array('0degr', false),
            array('0deg stuff', array('0', 'deg', 'stuff')),
            array('0deg   more-stuff', array('0', 'deg', 'more-stuff')),
            array('1deg', array('1', 'deg', '')),
            array('1.1deg', array('1.1', 'deg', '')),
            array('5rad', array('5', 'rad', '')),
            array('5.6grad', array('5.6', 'grad', '')),
            array('-2.5deg', array('-2.5', 'deg', '')),
            array('+3.7rad', array('+3.7', 'rad', '')),
            array('center 1px', array('center', '', '1px')),
            array('center-left beehive', array('center-left', '', 'beehive')),
            array('center behind tree', array('center behind', '', 'tree')),
        );

        return self::_reindex($tests);
    }

    /**
     * Create a data set for color parsing.
     *
     * @return array
     */
    function dataSetColor() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('#000000', array('#000000', '', '')),
            array('#000 ', array('#000000', '', '')),
            array('#000000 foo', array('#000000', '', 'foo')),
            array('black', array('#000000', '', '')),
            array('black death', array('#000000', '', 'death')),
            array('#0000', false),
            array('blah', false),
            array('#ABC DEF', array('#AABBCC', '', 'DEF')),
            array('rgb(0, 0, 0) foo', array('#000000', '', 'foo')),
            array('rgb(300, 128 , 3) foo', array('#FF8003', '', 'foo')),
            array('rgb( 200% , 10%, 50%) foo', array('#FF1A80', '', 'foo')),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for integer parsing.
     *
     * @return array
     */
    function dataSetInteger() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('0', array('0', '', '')),
            array('0 ', array('0', '', '')),
            array('0 foo', array('0', '', 'foo')),
            array('0.0', false),
            array('00fred', false),
            array('blah', false),
            array('1 stuff', array('1', '', 'stuff')),
            array('+1 foo', array('+1', '', 'foo')),
            array('-1 foo', array('-1', '', 'foo')),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for parse length.
     *
     * @return array
     */
    function dataSetLength() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('0', array('0', '', '')),
            array('0 ', array('0', '', '')),
            array('0 foo', array('0', '', 'foo')),
            array('1cm', array('1', 'cm', '')),
            array('1cm.', array('1', 'cm', '.')),
            //cm|em|ex|in|mm|pc|pt|px
            array('0de', false),
            array('0degr', false),
            array('1em stuff', array('1', 'em', 'stuff')),
            array('1ex   more-stuff', array('1', 'ex', 'more-stuff')),
            array('1in', array('1', 'in', '')),
            array('1.1mm', array('1.1', 'mm', '')),
            array('5pc', array('5', 'pc', '')),
            array('5.6pt', array('5.6', 'pt', '')),
            array('-2.5px', array('-2.5', 'px', '')),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for number parsing.
     *
     * @return array
     */
    function dataSetNumber() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('0', array('0', '', '')),
            array('0 ', array('0', '', '')),
            array('0 foo', array('0', '', 'foo')),
            array('0.0', array('0', '', '')),
            array('00fred', false),
            array('blah', false),
            array('1. stuff', array('1.', '', 'stuff')),
            array('+1.1 foo', array('+1.1', '', 'foo')),
            array('-1.2 foo', array('-1.2', '', 'foo')),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for percentage parsing.
     *
     * @return array
     */
    function dataSetPercentage() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('0%', array('0', '%', '')),
            array('0% ', array('0', '%', '')),
            array('0% foo', array('0', '%', 'foo')),
            array('0.0%', array('0', '%', '')),
            array('0', false),
            array('00%fred', false),
            array('blah', false),
            array('1.% stuff', array('1.', '%', 'stuff')),
            array('+1.1% foo', array('+1.1', '%', 'foo')),
            array('-1.2% foo', array('-1.2', '%', 'foo')),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for quoted string parsing.
     *
     * @return array
     */
    function dataSetQuoted() {
        /*
         * Each element is (input string, allow unquoted), expected result
         */
        $tests = array(
            array(
                array('Arial', true),
                array('Arial', '', '')
            ),
            array(
                array('Arial', false),
                false
            ),
            array(
                array('"Debacle Bold" ', true),
                array('"Debacle Bold"', '', '')
            ),
            array(
                array('"Debacle Bold" ', false),
                array('"Debacle Bold"', '', '')
            ),
            array(
                array('\'Debacle Bold\' ', true),
                array('\'Debacle Bold\'', '', '')
            ),
            array(
                array('\'Debacle Bold\' ', false),
                array('\'Debacle Bold\'', '', '')
            ),
            array(
                array('Arial foo', true),
                array('Arial', '', 'foo')
            ),
            array(
                array('Arial foo', false),
                false
            ),
            array(
                array('Arial "foo', true),
                array('Arial', '', '"foo')
            ),
            /*
             * This one is a little quirky, but we're only looking at
             * whitespace delimiters, so it's right...
             */
            array(
                array('Arial, foo', true),
                array('Arial,', '', 'foo')
            ),
            array(
                array('\'bob stuff', true),
                false
            ),
            array(
                array('"bob stuff', true),
                false
            ),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for quoted list parsing.
     *
     * @return array
     */
    function dataSetQuotedList() {
        /*
         * Each element is (input string, allow unquoted), expected result
         */
        $tests = array(
            array(
                array('Arial', true),
                array('Arial', '', '', false)
            ),
            array(
                array('Arial', false),
                false
            ),
            array(
                array('"Debacle Bold" ', true),
                array('"Debacle Bold"', '', '', false)
            ),
            array(
                array('"Debacle Bold" ', false),
                array('"Debacle Bold"', '', '', false)
            ),
            array(
                array('"Debacle Bold" , ', true),
                array('"Debacle Bold"', '', '', true)
            ),
            array(
                array('"Debacle Bold" , ', false),
                array('"Debacle Bold"', '', '', true)
            ),
            array(
                array('\'Debacle Bold\', ', true),
                array('\'Debacle Bold\'', '', '', true)
            ),
            array(
                array('\'Debacle Bold\', ', false),
                array('\'Debacle Bold\'', '', '', true)
            ),
            array(
                array('Arial foo', true),
                array('Arial', '', 'foo', false)
            ),
            array(
                array('Arial foo', false),
                false
            ),
            array(
                array('Arial "foo', true),
                array('Arial', '', '"foo', false)
            ),
            array(
                array('Arial "foo', false),
                false
            ),
            array(
                array('Arial, foo', true),
                array('Arial', '', 'foo', true)
            ),
            array(
                array('\'bob stuff', true),
                false
            ),
            array(
                array('"bob stuff', true),
                false
            ),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for URL parsing.
     *
     * @return array
     */
    function dataSetUri() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('url(bob)', array('url(bob)', '', '')),
            array('url(bob) ', array('url(bob)', '', '')),
            array('url(bob) foo', array('url(bob)', '', 'foo')),
            array('url(bob)x', false),
            array('url(', false),
            array('urlbob', false),
            array('url(\'bob) stuff', false),
            array('url("bob) stuff', false),
            array('url(\'bob\') stuff', array('url(\'bob\')', '', 'stuff')),
            array('url(\'b\\\'ob\') stuff', array('url(\'b\\\'ob\')', '', 'stuff')),
            array('url("bob") stuff', array('url("bob")', '', 'stuff')),
            array('url("b\'\\"ob") stuff', array('url("b\'\\"ob")', '', 'stuff')),
        );
        return self::_reindex($tests);
    }

    /**
     * Create a data set for URL parsing.
     *
     * @return array
     */
    function dataSetUriList() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('url(bob),', array('url(bob)', '', '')),
            array('url(bob) ,', array('url(bob)', '', '')),
            array('url(bob), foo', array('url(bob)', '', 'foo')),
            array('url(bob),x', false),
            array('url(', false),
            array('urlbob', false),
            array('url(\'bob) stuff', false),
            array('url("bob) stuff', false),
            array('url(\'bob\'), stuff', array('url(\'bob\')', '', 'stuff')),
            array('url(\'b\\\'ob\'), stuff', array('url(\'b\\\'ob\')', '', 'stuff')),
            array('url("bob"), stuff', array('url("bob")', '', 'stuff')),
            array('url("b\'\\"ob"), stuff', array('url("b\'\\"ob")', '', 'stuff')),
        );
        return self::_reindex($tests);
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
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown() {
    }

    /**
     * Test parse angle, using a more extensive set of cases since we're also
     * testing _parseUnit by proxy.
     *
     * @dataProvider dataSetAngle
     */
    function testParseAngle($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parseAngle($prop);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse color.
     *
     * @dataProvider dataSetColor
     */
    function testParseColor($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parseColor($prop);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse integer.
     *
     * @dataProvider dataSetInteger
     */
    function testParseInteger($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parseInteger($prop);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse length.
     *
     * @dataProvider dataSetLength
     */
    function testParseLength($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parseLength($prop);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse number.
     *
     * @dataProvider dataSetNumber
     */
    function testParseNumber($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parseNumber($prop);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse percentage.
     *
     * @dataProvider dataSetPercentage
     */
    function testParsePercentage($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parsePercentage($prop);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse quoted.
     *
     * @dataProvider dataSetQuoted
     */
    function testParseQuoted($prop, $expect) {
        list($value, $allowU) = $prop;
        $actual = AP5L_Css_PropertyDef::parseQuoted($value, $allowU);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse quoted in list mode.
     *
     * @dataProvider dataSetQuotedList
     */
    function testParseQuotedList($prop, $expect) {
        list($value, $allowU) = $prop;
        $actual = AP5L_Css_PropertyDef::parseQuoted($value, $allowU, true);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse URL.
     *
     * @dataProvider dataSetUri
     */
    function testParseUri($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parseUri($prop);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test parse URL in list mode.
     *
     * @dataProvider dataSetUriList
     */
    function testParseUriList($prop, $expect) {
        $actual = AP5L_Css_PropertyDef::parseUri($prop, true);
        $this -> assertEquals($expect, $actual);
    }

}

// Call PropertyDefParseTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'PropertyDefParseTest::main') {
    PropertyDefParseTest::main();
}
