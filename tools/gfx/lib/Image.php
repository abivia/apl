<?php
/**
 * Fill tool image.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Image.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Top level fill tool image.
 */
class IFT_Image extends AP5L_Xml_InflexibleParsedObject {
    protected $_fileName;
    protected $_fileType;
    protected $_height;
    protected $_instructions;
    protected $_width;

    public $image;
    public $symbols;

    function execute($symbols) {
        /*
         * Copy the global symbol table.
         */
        $this -> symbols = $symbols;
        /*
         * Create the image
         */
        $this -> image = new AP5L_Gfx_Image($this -> _width, $this -> _height);
        /*
         * Apply the operations.
         */
        foreach ($this -> _instructions as $op) {
            $op -> execute($this);
        }
        /*
         * Write the file
         */
        if ($this -> _fileType) {
            $type = $this -> _fileType;
        } else {
            $info = pathinfo($this -> _fileName);
            if (isset($info['extension'])) {
                $type = $info['extension'];
            } else {
                $type = '';
            }
        }
        $this -> image -> write($this -> _fileName, $type);
    }

    /**
     * Return as XML
     */
    function toXml($eol = '') {
        $xml = '<image event="' . $this -> _eventName . '"'
            . ' engine="' . $this -> _engine . '">' . $eol
            . '<argdata>' . AP5L_Xml_Lib::toXmlString($this -> _argData) . '</argdata>' . $eol
            . '</image>' . $eol;
        return $xml;
    }

    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            case 'color':
            case 'op': {
                $this -> _instructions[] = $parser -> getNewNode();
            } break;

            default: {
                $result = false;
            } break;

        }
        return $result;
    }

    function xmlElementOpen($parser) {
        switch ($parser -> getElement()) {
            case 'image': {
                $this -> _fileName = $parser -> attrValueRequired('file', '?');
                $this -> _fileType = $parser -> attrValue('type');
                $this -> _height = $parser -> attrValueRequired('height', 0);
                $this -> _width = $parser -> attrValueRequired('width', 0);
                $this -> _instructions = array();
            }
            break;
        }
    }

    /**
     * Manufacture an Image object and return it
     */
    static public function &xmlFactory() {
        $t = new IFT_Image();
        return $t;

    }

    static public function xmlRegister(&$parser) {
        //
        // Register elements
        //
        $parser -> registerElement('instructions');
        $parser -> registerElement('image', 'IFT_Image');
        IFT_Color::xmlRegister($parser);
        IFT_Operation::xmlRegister($parser);
    }

}

