<?php
/**
 * Fill tool operation.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Operation.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Fill tool operation.
 */
class IFT_Operation extends AP5L_Xml_InflexibleParsedObject {
    protected $_colors;
    protected $_file;
    protected $_height;
    protected $_name;
    protected $_map;
    protected $_width;
    protected $_x;
    protected $_y;
    
    protected function _executeFill(&$imageDef) {
        // Determine the fill area
        $area = AP5L_Math_Vector2d::factory(
            AP5L_Math_Point2d::factory(0, 0),
            $imageDef -> image -> getSize()
        );
        if (! is_null($this -> _x)) {
            $area -> org -> x = $this -> _x;
        }
        if (! is_null($this -> _y)) {
            $area -> org -> y = $this -> _y;
        }
        if (! is_null($this -> _width)) {
            $area -> direction -> x = $this -> _width;
        }
        if (! is_null($this -> _height)) {
            $area -> direction -> y = $this -> _height;
        }
    }

    function execute(&$imageDef) {
        switch ($this -> _name) {
            case 'fill': {
                $this -> _executeFill($imageDef);
            }
            break;
        }
    }
    
    /**
     * Return as XML
     */
    function toXml($eol = '') {
        $xml = '<op>' . $eol;
        $xml .= '</op>' . $eol;
        return $xml;
    }

    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            default: {
                $result = false;
            } break;

        }
        return $result;
    }

    function xmlElementOpen($parser) {
        switch ($parser -> getElement()) {
            case 'op': {
                //<op name="fill" map="flat" colors="#FFFFFF" />
                $this -> _colors = explode(' ', $parser -> attrValue('colors'));
                $this -> _file = $parser -> attrValue('file');
                $this -> _name = $parser -> attrValueRequired('name', '?');
                $this -> _map = $parser -> attrValue('map');
                $this -> _x = $parser -> attrValue('x', null);
                $this -> _y = $parser -> attrValue('y', null);
                $this -> _width = $parser -> attrValue('width', null);
                $this -> _height = $parser -> attrValue('height', null);
            }
            break;
        }
    }

    /**
     * Manufacture an operation object and return it
     */
    static public function &xmlFactory() {
        $t = new IFT_Operation();
        return $t;
    }

    static public function xmlRegister(&$parser) {
        //
        // Register elements
        //
        $parser -> registerElement('op', 'IFT_Operation');
    }

}

