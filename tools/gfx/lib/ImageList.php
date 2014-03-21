<?php
/**
 * List of fill tool images.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ImageList.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * List of fill tool images.
 */
class IFT_ImageList extends AP5L_Xml_InflexibleParsedObject {
    protected $_images;
    protected $_symbols = array();

    function addImage($image) {
        $this -> _images[] = $image;
    }

    function execute() {
        foreach ($this -> _images as $image) {
            $image -> execute($this -> _symbols);
        }
    }

    function symbolLoad($file) {
        $this -> _symbols = parse_ini_file($file);
    }

    /**
     * Return as XML
     */
    function toXml($eol = '') {
        $xml = '<images>' . $eol;
        foreach ($this -> _images as $image) {
            $xml .= $image -> toXml($eol);
        }
        $xml .= '</images>' . $eol;
        return $xml;
    }

    function xmlElementClose($parser) {
        $result = true; // Assume we match the element
        switch ($parser -> getElement()) {
            case 'image': {
                $this -> _images[] = $parser -> getNewNode();
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
                $this -> _images = array();
            }
            break;
        }
    }

    /**
     * Manufacture an ImageList object and return it
     */
    static public function &xmlFactory() {
        $t = new IFT_ImageList();
        return $t;
    }

    static public function xmlRegister(&$parser) {
        //
        // Register elements
        //
        $parser -> registerElement('imagelist', 'IFT_ImageList');
        IFT_Image::xmlRegister($parser);
    }

}

