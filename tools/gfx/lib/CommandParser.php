<?php
/**
 * Parser for ImageFill tool commands.
 *
 * @package AP5L
 * @subpackage Gfx
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: CommandParser.php 92 2009-08-21 03:03:12Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Parser for ImageFill tool commands.
 */
class IFT_CommandParser extends AP5L_Xml_ParserCore {

    function __construct() {
        parent::__construct();
        //
        // Initialize element types
        //
        IFT_ImageList::xmlRegister($this);
    }

    /**
     * Convert any of a variety of string representations of a color into a
     * ColorSpace object.
     *
     * Recognizes these forms: #[00]000000 (standard CSS hex w/alpha); #r(r,g,
     * b[,a]) red, green, blue, alpha; #h(h,s,b[,a]) hue, saturation,
     * brightness, alpha), #(named)
     */
    static function &colorParse($cstr) {
        $cs = new AP5L_Gfx_ColorSpace();
        if ($cstr == '' || $cstr[0] != '#') {
            throw new AP5L_Gfx_Exception(
                'Invalid color "' . $cstr . '"'
            );
        }
        $work = strtolower(substr($cstr, 1));
        if ($work == '') {
            throw new AP5L_Gfx_Exception(
                'Invalid color "' . $cstr . '"'
            );
        }
        if (preg_match('/([hr]?)\(([0-9., ]*)\)/', $work, $hits)) {
            $mode = $hits[1];
            if ($mode == '') {
                $cs -> setNamed($hits[2]);
            } else {
                $vals = explode(',', $hits[2]);
                foreach ($vals as &$val) {
                    $val = trim($val);
                    if (strpos($val, '.') !== false) {
                        $val = (float) $val;
                    } else {
                        $val = (int) $val;
                    }
                }
                while (count($vals) < 4) {
                    $vals[] = 0;
                }
                if ($mode == 'h') {
                    $cs -> setHsba($vals);
                } else {
                    $cs -> setRgba($vals);
                }
            }
        } else {
            $cs -> setHex($work);
        }
        return $cs;
    }

    static function symbolResolve($table, $symbol) {
        $depth = 0;
        while (strlen($symbol) && ($symbol[0] == '$' || $symbol[0] == '#')) {
            if ($symbol[0] == '$') {
                $word = substr($symbol, 1);
                if (! isset($table[$word])) {
                    throw new AP5L_Gfx_Exception(
                        'Undefined symbol "' . $word . '"'
                    );
                }
                $symbol = $table[$word];
            } elseif ($symbol[0] == '#') {
                return self::colorParse($symbol);
            }
            if (++$depth > 10) break;
        }
        return $symbol;
    }

}

