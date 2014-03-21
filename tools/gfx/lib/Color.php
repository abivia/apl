<?php
/**
 * Color transformation object for the fill tool.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Color.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Fill tool color transformation.
 */
class IFT_Color extends AP5L_Xml_InflexibleParsedObject {
    protected $_base;
    protected $_color;
    protected $_id;
    protected $_transform;

    function execute($imageDef) {
        /*
         * Resolve the base color
         */
        $base = clone IFT_CommandParser::symbolResolve($imageDef -> symbols, $this -> _base);
        /*
         * Decode operations and apply them to the color.
         *
         * Operations are aspect:value[operator[value]].
         */
        $opList = explode(';', $this -> _transform);
        $argPat = '/([a-z][a-z0-9]*):([$a-z0-9]*)([^$a-z0-9]*)([$a-z0-9]*)/i';
        foreach ($opList as $op) {
            if (! preg_match($argPat, $op, $hits)) {
                // throw bad syntax
            }
            $attr = strtolower($hits[1]);
            if ($hits[2]) {
                $lhs = IFT_CommandParser::symbolResolve($imageDef -> symbols, $hits[2]);
                if (is_numeric($lhs)) {
                    $lhs = (float) $lhs;
                } elseif ($lhs instanceof AP5L_Gfx_ColorSpace) {
                    $lhs = self::getAttr($lhs, $attr);
                }
            } elseif ($base instanceof AP5L_Gfx_ColorSpace) {
                $lhs = self::getAttr($base, $attr);
            } else {
                $lhs = '';
            }
            if ($hits[4]) {
                $rhs = IFT_CommandParser::symbolResolve($imageDef -> symbols, $hits[4]);
                if (is_numeric($rhs)) {
                    $rhs = (float) $rhs;
                } elseif ($rhs instanceof AP5L_Gfx_ColorSpace) {
                    $rhs = self::getAttr($rhs, $attr);
                }
            } else {
                $rhs = 0.0;
            }
            switch ($hits[3]) {
                case '': {
                    $result = $lhs;
                }
                break;

                case '*': {
                    $result = $lhs * $rhs;
                }
                break;

                default: {
                    throw new AP5L_Gfx_Exception('Unknown operator: "' . $hits[3] . '"');
                }
                break;
            }
            self::setAttr($base, $attr, $result);
        }
        $imageDef -> symbols[$this -> _id] = $base;
    }

    static function getAttr($color, $attr) {
        switch ($attr) {
            case 'alpha': {
                $result = $color -> getAlpha();
            }
            break;

            case 'blue': {
                $result = $color -> getBlue();
            }
            break;
        }
    }

    static function setAttr(&$color, $attr, $value) {
    }

    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            case 'op': {
                $this -> _operations[] = $parser -> getNewNode();
            } break;

            default: {
                $result = false;
            } break;

        }
        return $result;
    }

    function xmlElementOpen($parser) {
        switch ($parser -> getElement()) {
            case 'color': {
                $this -> _id = $parser -> attrValueRequired('id', '?');
                $this -> _base = $parser -> attrValueRequired('base', '?');
                $this -> _transform = $parser -> attrValue('transform');
            }
            break;
        }
    }

    /**
     * Manufacture an Image object and return it
     */
    static public function &xmlFactory() {
        $t = new IFT_Color();
        return $t;

    }

    static public function xmlRegister(&$parser) {
        //
        // Register elements
        //
        $parser -> registerElement('color', 'IFT_Color');
    }

}
