<?php
/**
 * Unit tests for AP5L_Css_PropertyDef validation functions
 *
 * @package AP5L
 * @subpackage QC
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2009, Alan Langford
 * @version $Id: $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// Call PropertyDefValidateTest::main() if this source file is executed directly.
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'PropertyDefValidateTest::main');
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
 * Test class for AP5L_Css_PropertyDef validation functions.
 */
class PropertyDefValidateTest extends PHPUnit_Framework_TestCase {

    /**
     * Create a data set for validate angle.
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
        return $tests;
    }

    /**
     * Create a data set for validate background.
     *
     * @return array
     */
    function dataSetBackground() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array(
                'inherit',
                array(
                    array('background-color', 'inherit', '', ''),
                    array('background-attachment', 'inherit', '', ''),
                    array('background-image', 'inherit', '', ''),
                    array('background-position', 'inherit', '', ''),
                    array('background-repeat', 'inherit', '', ''),
                ),
            ),
            array('inherit genes', false),
            array(
                'scroll',
                array(
                    array('background-attachment', 'scroll', '', ''),
                ),
            ),
            array(
                '#ffeedd',
                array(
                    array('background-color', '#FFEEDD', '', ''),
                ),
            ),
            array(
                '#ffeedd scroll',
                array(
                    array('background-color', '#FFEEDD', '', ''),
                    array('background-attachment', 'scroll', '', ''),
                ),
            ),
            array(
                '#ffeedd scroll none',
                array(
                    array('background-color', '#FFEEDD', '', ''),
                    array('background-attachment', 'scroll', '', ''),
                    array('background-image', 'none', '', ''),
                ),
            ),
            array(
                '#ffeedd url("stuff.png") scroll',
                array(
                    array('background-color', '#FFEEDD', '', ''),
                    array('background-image', 'url("stuff.png")', '', ''),
                    array('background-attachment', 'scroll', '', ''),
                ),
            ),
            array(
                '#ffeedd 5px 5px url("stuff.png") scroll',
                array(
                    array('background-color', '#FFEEDD', '', ''),
                    array('background-position', array('5', '5'), array('px', 'px'), ''),
                    array('background-image', 'url("stuff.png")', '', ''),
                    array('background-attachment', 'scroll', '', ''),
                ),
            ),
            array(
                '#ffeedd repeat-x url("stuff.png") 5px 5px scroll',
                array(
                    array('background-color', '#FFEEDD', '', ''),
                    array('background-repeat', 'repeat-x', '', ''),
                    array('background-image', 'url("stuff.png")', '', ''),
                    array('background-position', array('5', '5'), array('px', 'px'), ''),
                    array('background-attachment', 'scroll', '', ''),
                ),
            ),
        );
        return $tests;
    }

    /**
     * Create a data set for validate background position.
     *
     * @return array
     */
    function dataSetBackgroundPosition() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('bottom  center up', array('bottom center', '', 'up')),
            array('100% 50% foo', array(array('100', '50'), array('%', '%'), 'foo')),
            array('100% 50in foo', array(array('100', '50'), array('%', 'in'), 'foo')),
            array('100px 50% foo', array(array('100', '50'), array('px', '%'), 'foo')),
            array('100pt 50mm foo', array(array('100', '50'), array('pt', 'mm'), 'foo')),
        );
        return $tests;
    }

    /**
     * Create a data set for validate background repeat.
     *
     * @return array
     */
    function dataSetBackgroundRepeat() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('repeat fred', array('repeat', '', 'fred')),
            array('no-repeat fred', array('no-repeat', '', 'fred')),
            array('repeat-x fred', array('repeat-x', '', 'fred')),
            array('repeat-y fred', array('repeat-y', '', 'fred')),
            array('circumnavigation fred', false),
        );
        return $tests;
    }

    /**
     * Create a data set for validate border color.
     *
     * @return array
     */
    function dataSetBorderColor() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('foghorn', false, false),
            array('green transparent genes', false, false),
            array(
                'inherit genes',
                array(
                    array('border-top-color', 'inherit', '', ''),
                    array('border-right-color', 'inherit', '', ''),
                    array('border-bottom-color', 'inherit', '', ''),
                    array('border-left-color', 'inherit', '', ''),
                ),
                'genes'
            ),
            array(
                'transparent genes',
                array(
                    array('border-top-color', 'transparent', '', ''),
                    array('border-right-color', 'transparent', '', ''),
                    array('border-bottom-color', 'transparent', '', ''),
                    array('border-left-color', 'transparent', '', ''),
                ),
                'genes'
            ),
            array(
                'green genes',
                array(
                    array('border-top-color', '#008000', '', ''),
                    array('border-right-color', '#008000', '', ''),
                    array('border-bottom-color', '#008000', '', ''),
                    array('border-left-color', '#008000', '', ''),
                ),
                'genes'
            ),
            array(
                'green #ff0000 genes',
                array(
                    array('border-top-color', '#008000', '', ''),
                    array('border-right-color', '#FF0000', '', ''),
                    array('border-bottom-color', '#008000', '', ''),
                    array('border-left-color', '#FF0000', '', ''),
                ),
                'genes'
            ),
        );
        return $tests;
    }

    /**
     * Create a data set for validate border width.
     *
     * @return array
     */
    function dataSetBorderWidth() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('foghorn', false, false),
            array(
                'thick genes',
                array(
                    array('border-top-width', 'thick', '', ''),
                    array('border-right-width', 'thick', '', ''),
                    array('border-bottom-width', 'thick', '', ''),
                    array('border-left-width', 'thick', '', ''),
                ),
                'genes'
            ),
            array(
                'thick thin genes',
                array(
                    array('border-top-width', 'thick', '', ''),
                    array('border-right-width', 'thin', '', ''),
                    array('border-bottom-width', 'thick', '', ''),
                    array('border-left-width', 'thin', '', ''),
                ),
                'genes'
            ),
            array(
                'thick medium thin genes',
                array(
                    array('border-top-width', 'thick', '', ''),
                    array('border-right-width', 'medium', '', ''),
                    array('border-bottom-width', 'thin', '', ''),
                    array('border-left-width', 'thick', '', ''),
                ),
                'genes'
            ),
            array(
                '3px genes',
                array(
                    array('border-top-width', '3', 'px', ''),
                    array('border-right-width', '3', 'px', ''),
                    array('border-bottom-width', '3', 'px', ''),
                    array('border-left-width', '3', 'px', ''),
                ),
                'genes'
            ),
            array(
                '2px 3px genes',
                array(
                    array('border-top-width', '2', 'px', ''),
                    array('border-right-width', '3', 'px', ''),
                    array('border-bottom-width', '2', 'px', ''),
                    array('border-left-width', '3', 'px', ''),
                ),
                'genes'
            ),
            array(
                '1px thick 3px genes',
                array(
                    array('border-top-width', '1', 'px', ''),
                    array('border-right-width', 'thick', '', ''),
                    array('border-bottom-width', '3', 'px', ''),
                    array('border-left-width', '1', 'px', ''),
                ),
                'genes'
            ),
        );
        return $tests;
    }

    /**
     * Create a data set for validate cursor.
     *
     * @return array
     */
    function dataSetCursor() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('pointer fred', array('pointer', '', 'fred')),
            array('url(bob), pointer fred', array('url(bob),pointer', '', 'fred')),
            array('url(sam), url(bob), text wait fred', array('url(sam),url(bob),text', '', 'fred')),
            array('circumnavigation fred', false),
        );
        return $tests;
    }

    /**
     * Create a data set for validate font.
     *
     * @return array
     */
    function dataSetFont() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('italic', false, false),
            array('normal', false, false),
            array('italic', false, false),
            array(
                'caption genes',
                array(
                    array('_font', 'caption', '', ''),
                ),
                'genes'
            ),
            array(
                '32px Arial, "Vera Sans",sans-serif genes',
                array(
                    array('font-size', '32', 'px', ''),
                    array('font-family', 'Arial,"Vera Sans",sans-serif', '', ''),
                ),
                'genes'
            ),
            array(
                'italic small-caps lighter 32px / 120% "Vera Sans", Arial, sans-serif genes',
                array(
                    array('font-style', 'italic', '', ''),
                    array('font-variant', 'small-caps', '', ''),
                    array('font-weight', 'lighter', '', ''),
                    array('font-size', '32', 'px', ''),
                    array('line-height', '120', '%', ''),
                    array('font-family', '"Vera Sans",Arial,sans-serif', '', ''),
                ),
                'genes'
            ),
        );
        return $tests;
    }

    /**
     * Create a data set for validate font-family.
     *
     * @return array
     */
    function dataSetFontFamily() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('medium fred', array('medium', '', 'fred')),
            array('Arial, "Times New Roman" fred', array('Arial,"Times New Roman"', '', 'fred')),
            array('Helvetica, Courier, "Zapf Tingies" fred', array('Helvetica,Courier,"Zapf Tingies"', '', 'fred')),
            array('Helvetica, Courier, fred, ', array('Helvetica,Courier,fred', '', '')),
            );
        return $tests;
    }

    /**
     * Create a data set for validate font-size.
     *
     * @return array
     */
    function dataSetFontSize() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('medium fred', array('medium', '', 'fred')),
            array('12px fred', array('12', 'px', 'fred')),
            array('120% fred', array('120', '%', 'fred')),
            array('circumnavigation fred', false),
        );
        return $tests;
    }

    /**
     * Create a data set for validate font-style.
     *
     * @return array
     */
    function dataSetFontStyle() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('normal fred', array('normal', '', 'fred')),
            array('italic fred', array('italic', '', 'fred')),
            array('oblique fred', array('oblique', '', 'fred')),
            array('circumnavigation fred', false),
        );
        return $tests;
    }

    /**
     * Create a data set for validate font-variant.
     *
     * @return array
     */
    function dataSetFontVariant() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('normal fred', array('normal', '', 'fred')),
            array('small-caps fred', array('small-caps', '', 'fred')),
            array('circumnavigation fred', false),
        );
        return $tests;
    }

    /**
     * Create a data set for validate font-weight.
     *
     * @return array
     */
    function dataSetFontWeight() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('normal fred', array('normal', '', 'fred')),
            array('Bold fred', array('bold', '', 'fred')),
            array('300 fred', array('300', '', 'fred')),
            array('lighter fred', array('lighter', '', 'fred')),
            array('bolder fred', array('bolder', '', 'fred')),
            array('3000 fred', false),
            array('circumnavigation fred', false),
        );
        return $tests;
    }

    /**
     * Create a data set for validate line-height.
     *
     * @return array
     */
    function dataSetLineHeight() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('1.2 fred', array('1.2', '', 'fred')),
            array('12px fred', array('12', 'px', 'fred')),
            array('120% fred', array('120', '%', 'fred')),
            array('circumnavigation fred', false),
        );
        return $tests;
    }

    /**
     * Create a data set for validate quotes.
     *
     * @return array
     */
    function dataSetQuotes() {
        /*
         * Each element is input string, expected result
         */
        $tests = array(
            array('inherit', array('inherit', '', '')),
            array('inherit genes', array('inherit', '', 'genes')),
            array('"1" "2" fred', array(array('"1"', '"2"'), array('', ''), 'fred')),
            array('"1" fred', false),
            array('circumnavigation fred', false),
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
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown() {
    }

    /**
     * Test validate angle, using a more extensive set of cases since we're also
     * testing _parseUnit by proxy.
     *
     * @dataProvider dataSetAngle
     */
    function testValidateAngle($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'azimuth' => AP5L_Css_Property::factory('azimuth', $result[0], $result[1], ''),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateAngle('2.0+', 'azimuth', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate background.
     *
     * @dataProvider dataSetBackground
     */
    function testValidateBackground($prop, $result) {
        if ($result) {
            $props = array();
            foreach ($result as $key => $propData) {
                $props[$propData[0]] = AP5L_Css_Property::factory(
                    $propData[0], $propData[1], $propData[2], ''
                );
            }
            $expect = array('', $props, );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateBackground('2.0+', 'background', $prop, '');
        //print_r($actual);print_r($expect);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate background position.
     *
     * @dataProvider dataSetBackgroundPosition
     */
    function testValidateBackgroundPosition($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'background' => AP5L_Css_Property::factory(
                        'background', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateBackgroundPosition('2.0+', 'background', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate background repeat.
     *
     * @dataProvider dataSetBackgroundRepeat
     */
    function testValidateBackgroundRepeat($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'background-repeat' => AP5L_Css_Property::factory(
                        'background-repeat', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateBackgroundRepeat('2.0+', 'background-repeat', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate border color.
     *
     * @dataProvider dataSetBorderColor
     */
    function testValidateBorderColor($prop, $result, $remainder) {
        if ($result) {
            $props = array();
            foreach ($result as $key => $propData) {
                $props[$propData[0]] = AP5L_Css_Property::factory(
                    $propData[0], $propData[1], $propData[2], ''
                );
            }
            $expect = array($remainder, $props);
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateBorderColor4('2.0+', 'border-color', $prop, '');
        //echo 'actual ';print_r($actual);echo 'expect ';print_r($expect);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate border width.
     *
     * @dataProvider dataSetBorderWidth
     */
    function testValidateBorderWidth($prop, $result, $remainder) {
        if ($result) {
            $props = array();
            foreach ($result as $key => $propData) {
                $props[$propData[0]] = AP5L_Css_Property::factory(
                    $propData[0], $propData[1], $propData[2], ''
                );
            }
            $expect = array($remainder, $props);
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateBorderWidth4('2.0+', 'border-width', $prop, '');
        //echo 'actual ';print_r($actual);echo 'expect ';print_r($expect);
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate cursor.
     *
     * @dataProvider dataSetCursor
     */
    function testValidateCursor($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'cursor' => AP5L_Css_Property::factory(
                        'cursor', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateCursor('2.0+', 'cursor', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate font.
     *
     * @dataProvider dataSetFont
     */
    function testValidateFont($prop, $result, $remainder) {
        if ($result) {
            $props = array();
            foreach ($result as $key => $propData) {
                $props[$propData[0]] = AP5L_Css_Property::factory(
                    $propData[0], $propData[1], $propData[2], ''
                );
            }
            $expect = array($remainder, $props);
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateFont('2.0+', 'font', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate font-family.
     *
     * @dataProvider dataSetFontFamily
     */
    function testValidateFontFamily($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'font-family' => AP5L_Css_Property::factory(
                        'font-family', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateFontFamily('2.0+', 'font-family', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate font-size.
     *
     * @dataProvider dataSetFontSize
     */
    function testValidateFontSize($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'font-size' => AP5L_Css_Property::factory(
                        'font-size', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateFontSize('2.0+', 'font-size', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate font-style.
     *
     * @dataProvider dataSetFontStyle
     */
    function testValidateFontStyle($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'font-style' => AP5L_Css_Property::factory(
                        'font-style', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateFontStyle('2.0+', 'font-style', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate font-variant.
     *
     * @dataProvider dataSetFontVariant
     */
    function testValidateFontVariant($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'font-variant' => AP5L_Css_Property::factory(
                        'font-variant', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateFontVariant('2.0+', 'font-variant', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate font-weight.
     *
     * @dataProvider dataSetFontWeight
     */
    function testValidateFontWeight($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'font-weight' => AP5L_Css_Property::factory(
                        'font-weight', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateFontWeight('2.0+', 'font-weight', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate line-height.
     *
     * @dataProvider dataSetLineHeight
     */
    function testValidateLineHeight($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'line-height' => AP5L_Css_Property::factory(
                        'line-height', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateLineHeight('2.0+', 'line-height', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

    /**
     * Test validate quotes.
     *
     * @dataProvider dataSetQuotes
     */
    function testValidateQuotes($prop, $result) {
        if ($result) {
            $expect = array(
                $result[2],
                array(
                    'quotes' => AP5L_Css_Property::factory(
                        'quotes', $result[0], $result[1], ''
                    ),
                ),
            );
        } else {
            $expect = false;
        }
        $actual = AP5L_Css_PropertyDef::validateQuotes('2.0+', 'quotes', $prop, '');
        $this -> assertEquals($expect, $actual);
    }

}

// Call PropertyDefValidateTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'PropertyDefValidateTest::main') {
    PropertyDefValidateTest::main();
}
