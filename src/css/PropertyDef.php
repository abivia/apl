<?php

class AP5L_Css_PropertyDef {
    const ANGLE_REGEX = '/^\s*([+\-]?[0-9]+(?:\.[0-9]*)?)(deg|rad|grad)?(\s+|[^a-z0-9\-]|$)/i';
    const BACKGROUND_ATTACHMENT_REGEX = '/^\s*(fixed|scroll)(\s+|$)/i';
    const BACKGROUND_POSITION_REGEX = '/^\s*(?:(bottom|center|top)\s+(left|center|right))(\s+|$)/i';
    const COLOR_HEX_REGEX = '/^\s*#([0-9a-f]+)(\s+|[^a-z0-9\-]|$)/i';

    const COLOR_RGB_REGEX =
        '/^\s*rgb\(\s*([0-9]+%?)\s*,\s*([0-9]+%?)\s*,\s*([0-9]+%?)\s*\)(\s+|[^a-z0-9\-]|$)/i'
    ;
    const COUNTER_REGEX = '/^\s*(none|[a-z\-]+\s+[0-9]*(\s+[a-z\-]+\s+[0-9]*)+)(\s+|$)/i';
    const COUNTER_INHERIT_REGEX =
        '/^\s*(none|inherit|[a-z\-]+\s+[0-9]*(\s+[a-z\-]+\s+[0-9]*)+)(\s+|$)/i'
    ;
    const FREQUENCY_REGEX = '/^\s*([+\-]?[0-9]+(?:\.[0-9]*)?)(k?hz)(\s+|[^a-z0-9\-]|$)/i';
    const INHERIT_REGEX = '/^\s*inherit(\s+|$)/i';
    const INTEGER_REGEX = '/^\s*([+\-]?[0-9]+)(\s+|$)/i';
    const LENGTH_REGEX = '/^\s*([+\-]?[0-9]+(?:\.[0-9]*)?)(cm|em|ex|in|mm|pc|pt|px)?(\s+|[^a-z0-9\-]|$)/i';
    const NUMBER_REGEX = '/^\s*([+\-]?[0-9]+(?:\.[0-9]*)?)(\s+|$)/i';
    const PERCENTAGE_REGEX = '/^\s*([+\-]?[0-9]+(?:\.[0-9]*)?)(%)(\s+|$)/i';
    const TIME_REGEX = '/^\s*([+\-]?[0-9]+(?:\.[0-9]*)?)(m?s)(\s+|[^a-z0-9\-]|$)/i';

    const TYPE_ALL = -1;
    const TYPE_ANGLE = 0x00000020;
    const TYPE_COLOR = 0x00000001;
    const TYPE_FREQUENCY = 0x00000400;
    const TYPE_LENGTH = 0x00000002;
    const TYPE_NUMBER = 0x00000200;
    const TYPE_PERCENT = 0x00000004;
    const TYPE_QUOTED = 0x00000080;
    const TYPE_QUOTED_LIST = 0x00000100;
    const TYPE_STRING = 0x00001000;
    const TYPE_STRING_LIST = 0x00002000;
    const TYPE_TIME = 0x00000800;
    const TYPE_WORDS = 0x00000008;
    const TYPE_URI = 0x00000010;
    const TYPE_URI_LIST = 0x00000040;

    /*
     * Hacks to make up for inadequate constant expressions.
     */
    const TYPE_HACK_AW = 0x00000028;    // Angle plus words
    const TYPE_HACK_CW = 0x00000009;    // Colour plus words
    const TYPE_HACK_FW = 0x00000408;    // Frequency, words
    const TYPE_HACK_LW = 0x0000000A;    // Length, words
    const TYPE_HACK_NPLW = 0x0000020E;  // Number, percent, length, words
    const TYPE_HACK_PLW = 0x0000000E;   // Percent, length, words
    const TYPE_HACK_PTW = 0x0000080C;   // percentage, time, words
    const TYPE_HACK_SW = 0x00001008;    // string, words
    const TYPE_HACK_SW2 = 0x00002008;   // string list, words
    const TYPE_HACK_UW = 0x00000018;    // URI, words
    const TYPE_HACK_UW2 = 0x00000048;   // URI list, words

    /**
     * Keywords for parsing an angle. Leading keywords are true if they can be followed
     * by a secondary keyword.
     *
     * @var array
     */
    static protected $_angleKeywords = array(
        'leading' => array(
            'center' => true,
            'center-left' => true,
            'center-right' => true,
            'far-left' => true,
            'far-right' => true,
            'left' => true,
            'left-side' => true,
            'leftwards' => false,
            'right' => true,
            'right-side' => true,
            'rightwards' => false,
        ),
        'secondary' => array(
             'behind' => false,
        ),
    );

    /**
     * Map of hex color values to corresponding named colors. Initialized at runtime.
     *
     * @var array
     */
    static protected $_colorNames;

    /**
     * Standard box edge names.
     *
     * @var array
     */
    static protected $_edgeNames = array('top', 'right', 'bottom', 'left');

    /**
     * The groups that this property is a member of. Null means all groups.
     *
     * @var array
     */
    protected $_groups = null;

    /**
     * Definitions for various list types.
     *
     * @var array
     */
    static protected $_listDefs = array(
        'border-color' => array(
            'types' => self::TYPE_HACK_CW,
            'minCount' => 1,
            'words' => array('transparent', 'inherit'),
        ),
        'border-style' => array(
            'types' => self::TYPE_WORDS,
            'minCount' => 1,
            'words' => array(
                'dashed', 'dotted', 'double', 'groove', 'hidden', 'inset',
                'none', 'outset', 'ridge', 'solid',
            ),
        ),
        'border-width' => array(
            'types' => self::TYPE_HACK_PLW,
            'minCount' => 1,
            'words' => array('medium', 'thick', 'thin'),
        ),
        'cue' => array(
            'types' => self::TYPE_HACK_UW,
            'minCount' => 1,
            'words' => array('none', 'inherit'),
        ),
        'cursor' => array(
            'types' => self::TYPE_HACK_UW2,
            'minCount' => 1,
            'words' => array(
                'auto', 'crosshair', 'default', 'e-resize', 'help', 'move',
                'n-resize', 'ne-resize', 'nw-resize', 'pointer', 's-resize',
                'se-resize', 'sw-resize', 'text', 'w-resize', 'wait', 'inherit'
            ),
        ),
        'elevation' => array(
            'types' => self::TYPE_HACK_AW,
            'minCount' => 1,
            'words' => array('above', 'below', 'higher', 'inherit', 'level', 'lower', ),
        ),
        'font-family' => array(
            'types' => self::TYPE_QUOTED_LIST,
            'minCount' => 1,
        ),
        'font-size' => array(
            'types' => self::TYPE_HACK_PLW,
            'maxCount' => 1,
            'minCount' => 1,
            'words' => array(
                'large', 'larger', 'medium', 'small', 'smaller',
                'x-large', 'xx-large', 'x-small', 'xx-small',
            ),
        ),
        'font-style' => array(
            'types' => self::TYPE_WORDS,
            'maxCount' => 1,
            'minCount' => 1,
            'words' => array(
                'italic', 'normal', 'oblique',
            ),
        ),
        'font-variant' => array(
            'types' => self::TYPE_WORDS,
            'maxCount' => 1,
            'minCount' => 1,
            'words' => array(
                'normal', 'small-caps',
            ),
        ),
        'font-weight' => array(
            'types' => self::TYPE_WORDS,
            'maxCount' => 1,
            'minCount' => 1,
            'words' => array(
                'bold', 'bolder', 'lighter', 'normal',
                '100', '200', '300', '400', '500', '600', '700', '800', '900',
            ),
        ),
        'line-height' => array(
            'types' => self::TYPE_HACK_NPLW,
            'maxCount' => 1,
            'minCount' => 1,
            'words' => array(
                'normal',
            ),
        ),
        'outline-color' => array(
            'types' => self::TYPE_HACK_CW,
            'minCount' => 1,
            'words' => array('invert'),
        ),
        'outline-style' => array(
            'types' => self::TYPE_WORDS,
            'minCount' => 1,
            'words' => array(
                'dashed', 'dotted', 'double', 'groove', 'inset',
                'none', 'outset', 'ridge', 'solid',
            ),
        ),
        'outline-style' => array(
            'types' => self::TYPE_HACK_PTW,
            'minCount' => 1,
            'words' => array(),
        ),
        'pitch' => array(
            'types' => self::TYPE_HACK_FW,
            'minCount' => 1,
            'words' => array('high', 'low', 'medium', 'x-high', 'x-low'),
        ),
        'play-during' => array(
            'types' => self::TYPE_HACK_UW,
            'minCount' => 1,
            'words' => array('auto', 'none'),
        ),
        'quotes' => array(
            'types' => self::TYPE_HACK_SW2,
            'minCount' => 1,
            'words' => array('none'),
        ),
        'size' => array(
            'types' => self::TYPE_HACK_LW,
            'maxCount' => 2,
            'minCount' => 1,
            'words' => array('auto', 'landscape', 'portrait'),
        ),
        'text-align' => array(
            'types' => self::TYPE_HACK_SW,
            'minCount' => 1,
            'words' => array('center', 'left', 'right', 'justify'),
        ),
    );

    /**
     * Map of named colors to corresponding hex values.
     *
     * @var array
     */
    static protected $_namedColors = array(
        'aliceblue' => '#F0F8FF',
        'antiquewhite' => '#FAEBD7',
        'aquamarine' => '#7FFFD4',
        'azure' => '#F0FFFF',
        'beige' => '#F5F5DC',
        'bisque' => '#FFE4C4',
        'black' => '#000000',
        'blanchedalmond' => '#FFEBCD',
        'blueviolet' => '#8A2BE2',
        'brown' => '#A52A2A',
        'burlywood' => '#DEB887',
        'cadetblue' => '#5F9EA0',
        'chartreuse' => '#7FFF00',
        'chocolate' => '#D2691E',
        'coral' => '#FF7F50',
        'cornflowerblue' => '#6495ED',
        'cornsilk' => '#FFF8DC',
        'crimson' => '#DC143C',
        'cyan' => '#00FFFF',
        'darkblue' => '#00008B',
        'darkcyan' => '#008B8B',
        'darkgoldenrod' => '#B8860B',
        'darkgray' => '#A9A9A9',
        'darkgreen' => '#006400',
        'darkkhaki' => '#BDB76B',
        'darkmagenta' => '#8B008B',
        'darkolivegreen' => '#556B2F',
        'darkorange' => '#FF8C00',
        'darkorchid' => '#9932CC',
        'darkred' => '#8B0000',
        'darksalmon' => '#E9967A',
        'darkseagreen' => '#8FBC8F',
        'darkslateblue' => '#483D8B',
        'darkslategray' => '#2F4F4F',
        'darkturquoise' => '#00CED1',
        'darkviolet' => '#9400D3',
        'deeppink' => '#FF1493',
        'deepskyblue' => '#00BFFF',
        'dimgray' => '#696969',
        'dodgerblue' => '#1E90FF',
        'feldspar' => '#D19275',
        'firebrick' => '#B22222',
        'floralwhite' => '#FFFAF0',
        'forestgreen' => '#228B22',
        'gainsboro' => '#DCDCDC',
        'ghostwhite' => '#F8F8FF',
        'gold' => '#FFD700',
        'goldenrod' => '#DAA520',
        'gray' => '#808080',
        'green' => '#008000',
        'greenyellow' => '#ADFF2F',
        'honeydew' => '#F0FFF0',
        'hotpink' => '#FF69B4',
        'indianred' => '#CD5C5C',
        'indigo' => '#4B0082',
        'ivory' => '#FFFFF0',
        'khaki' => '#F0E68C',
        'lavender' => '#E6E6FA',
        'lavenderblush' => '#FFF0F5',
        'lawngreen' => '#7CFC00',
        'lemonchiffon' => '#FFFACD',
        'lightblue' => '#ADD8E6',
        'lightcoral' => '#F08080',
        'lightcyan' => '#E0FFFF',
        'lightgoldenrodyellow' => '#FAFAD2',
        'lightgrey' => '#D3D3D3',
        'lightgreen' => '#90EE90',
        'lightpink' => '#FFB6C1',
        'lightsalmon' => '#FFA07A',
        'lightseagreen' => '#20B2AA',
        'lightskyblue' => '#87CEFA',
        'lightslateblue' => '#8470FF',
        'lightslategray' => '#778899',
        'lightsteelblue' => '#B0C4DE',
        'lightyellow' => '#FFFFE0',
        'limegreen' => '#32CD32',
        'linen' => '#FAF0E6',
        'magenta' => '#FF00FF',
        'maroon' => '#800000',
        'mediumaquamarine' => '#66CDAA',
        'mediumblue' => '#0000CD',
        'mediumorchid' => '#BA55D3',
        'mediumpurple' => '#9370D8',
        'mediumseagreen' => '#3CB371',
        'mediumslateblue' => '#7B68EE',
        'mediumspringgreen' => '#00FA9A',
        'mediumturquoise' => '#48D1CC',
        'mediumvioletred' => '#C71585',
        'midnightblue' => '#191970',
        'mintcream' => '#F5FFFA',
        'mistyrose' => '#FFE4E1',
        'moccasin' => '#FFE4B5',
        'navajowhite' => '#FFDEAD',
        'navy' => '#000080',
        'oldlace' => '#FDF5E6',
        'olive' => '#808000',
        'olivedrab' => '#6B8E23',
        'orange' => '#ffa500',
        'orangered' => '#FF4500',
        'orchid' => '#DA70D6',
        'palegoldenrod' => '#EEE8AA',
        'palegreen' => '#98FB98',
        'paleturquoise' => '#AFEEEE',
        'palevioletred' => '#D87093',
        'papayawhip' => '#FFEFD5',
        'peachpuff' => '#FFDAB9',
        'peru' => '#CD853F',
        'pink' => '#FFC0CB',
        'plum' => '#DDA0DD',
        'powderblue' => '#B0E0E6',
        'purple' => '#800080',
        'red' => '#FF0000',
        'rosybrown' => '#BC8F8F',
        'royalblue' => '#4169E1',
        'saddlebrown' => '#8B4513',
        'salmon' => '#FA8072',
        'sandybrown' => '#F4A460',
        'seagreen' => '#2E8B57',
        'seashell' => '#FFF5EE',
        'sienna' => '#A0522D',
        'silver' => '#C0C0C0',
        'skyblue' => '#87CEEB',
        'slateblue' => '#6A5ACD',
        'slategray' => '#708090',
        'snow' => '#FFFAFA',
        'springgreen' => '#00FF7F',
        'steelblue' => '#4682B4',
        'tan' => '#D2B48C',
        'teal' => '#008080',
        'thistle' => '#D8BFD8',
        'tomato' => '#FF6347',
        'turquoise' => '#40E0D0',
        'violet' => '#EE82EE',
        'violetred' => '#D02090',
        'wheat' => '#F5DEB3',
        'white' => '#FFFFFF',
        'whitesmoke' => '#F5F5F5',
        'yellow' => '#FFFF00',
        'yellowgreen' => '#9ACD32',
    );

    /**
     * Validation rules for the property.
     *
     * @var array
     */
    protected $_validation;

    /**
     * The versions this property is defined for.
     *
     *  This has the form 'version', 'start-finish", or 'version+'.
     *
     * @var string
     */
    protected $_versions = '';

    /**
     * The name of the property.
     *
     * @var string
     */
    public $name = '';

    /**
     * Build internal hex-to-color map.
     */
    static protected function _buildColorMap() {
        if (empty(self::$_colorNames)) {
            self::$_colorNames = array();
            foreach(self::$_namedColors as $name => $hex) {
                self::$_colorNames[$hex] = $name;
            }
        }
        return;
    }

    /**
     * Parse a list of values.
     *
     * @param string A property value string.
     * @param array Options. Settinngs include:
     * <ul><li>"types" A bitmask of the TYPE_ constants. Determines what property
     * data types are allowable.
     * </li><li>"minCount" The minimum number of values. Defaults to 1.
     * </li><li>"maxCount" The maximum number of values. Defaults to unlimited.
     * </li><li>"words" A list of words to match. Required when TYPE_WORD is set.
     * </ul>
     * @return array Index "val" is an array of matched values.
     * Index "unit" is an array of matched units. Index "type" is the type of match.
     * These arrays are always the same size. Index "mask" is a bitwise or of all
     * matched types. Index "buffer" is the unparsed portion of the property value,
     * which may be empty.
     */
    static protected function _parseList($value, $options = array()) {
        $types = isset($options['types']) ? $options['types'] : self::TYPE_ALL;
        $minCount = isset($options['minCount']) ? $options['minCount'] : 1;
        $maxCount = isset($options['maxCount']) ? $options['maxCount'] : -1;
        $result = array(
            'mask' => 0,
            'val' => array(),
            'unit' => array(),
            'type' => array()
        );
        $ind = 0;
        while (true) {
            if ($maxCount == $ind) {
                break;
            }
            /*
             * Note: we have to parse percentage before we parse length.
             */
            if (
                $types & self::TYPE_WORDS
                && $match = self::_parseWords($options['words'], $value)
            ) {
                $len = $match[0][1] + strlen($match[0][0]) - (trim($match[2][0]) ? 1 : 0);
                $value = substr($value, $len);
                $result['val'][] = strtolower($match[1][0]);
                $result['unit'][] = '';
                $result['type'][] = self::TYPE_WORDS;
                $result['mask'] |= self::TYPE_WORDS;
            } elseif (
                $types & (self::TYPE_URI | self::TYPE_URI_LIST)
                && $subResult = self::parseUri($value, $types & self::TYPE_URI_LIST)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_URI;
                $result['mask'] |= self::TYPE_URI;
                $value = $subResult['buffer'];
            } elseif (
                $types & (
                    self::TYPE_QUOTED | self::TYPE_QUOTED_LIST
                    | self::TYPE_STRING | self::TYPE_STRING_LIST
                )
                && $subResult = self::parseQuoted(
                    $value,
                    $types & (self::TYPE_QUOTED | self::TYPE_QUOTED_LIST),
                    $types & (self::TYPE_QUOTED_LIST | self::TYPE_STRING_LIST)
                )
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_QUOTED;
                $result['mask'] |= self::TYPE_QUOTED;
                $value = $subResult['buffer'];
                if ($types & self::TYPE_QUOTED_LIST && !$subResult['more']) {
                    $types &= ~self::TYPE_QUOTED_LIST;
                }
            } elseif (
                $types & self::TYPE_COLOR
                && $subResult = self::parseColor($value)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_COLOR;
                $result['mask'] |= self::TYPE_COLOR;
                $value = $subResult['buffer'];
            } elseif (
                $types & self::TYPE_PERCENT
                && $subResult = self::_parseUnit(self::PERCENTAGE_REGEX, $value)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_PERCENT;
                $result['mask'] |= self::TYPE_PERCENT;
                $value = $subResult['buffer'];
            } elseif (
                $types & self::TYPE_FREQUENCY
                && $subResult = self::_parseUnit(self::FREQUENCY_REGEX, $value)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_FREQUENCY;
                $result['mask'] |= self::TYPE_FREQUENCY;
                $value = $subResult['buffer'];
            } elseif (
                $types & self::TYPE_TIME
                && $subResult = self::_parseUnit(self::TIME_REGEX, $value)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_TIME;
                $result['mask'] |= self::TYPE_TIME;
                $value = $subResult['buffer'];
            } elseif (
                $types & self::TYPE_LENGTH
                && $subResult = self::_parseUnit(self::LENGTH_REGEX, $value)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_LENGTH;
                $result['mask'] |= self::TYPE_LENGTH;
                $value = $subResult['buffer'];
            } elseif (
                $types & self::TYPE_ANGLE
                && $subResult = self::_parseUnit(self::ANGLE_REGEX, $value)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_ANGLE;
                $result['mask'] |= self::TYPE_ANGLE;
                $value = $subResult['buffer'];
            } elseif (
                $types & self::TYPE_NUMBER
                && $subResult = self::_parseUnit(self::NUMBER_REGEX, $value)
            ) {
                $result['val'][] = $subResult['val'];
                $result['unit'][] = $subResult['unit'];
                $result['type'][] = self::TYPE_NUMBER;
                $result['mask'] |= self::TYPE_NUMBER;
                $value = $subResult['buffer'];
            } elseif ($ind >= $minCount) {
                break;
            } else {
                return false;
            }
            ++$ind;
        }
        $result['buffer'] = $value;
        return $result;
    }

    /**
     * Parse a string for a number with units via regular expression.
     *
     * @param string The regular expression.
     * @param String The string to parse.
     * @return array Index 0 is the number, Index 1 is the units, Index 2 is
     * the remainder of the string.
     */
    static protected function _parseUnit($regex, $value) {
        $result = false;
        if (preg_match($regex, $value, $match, PREG_OFFSET_CAPTURE)) {
            if (!isset($match[2])) {
                // Missing units are ok if the number is zero.
                if (((float) $match[1][0]) == 0) {
                    $result = array('val' => $match[1][0], 'unit' => '');
                }
            } else {
                // Return the number and the units.
                $result = array('val' => $match[1][0], 'unit' => $match[2][0]);
            }
            if ($result) {
                // Return the value after we've removed the expression
                $len = $match[0][1] + strlen($match[0][0]) - (trim($match[3][0]) ? 1 : 0);
                $result['buffer'] = substr($value, $len);
            }
        }
        return $result;
    }

    /**
     * Parse a string for one of a set of words.
     *
     * @param string|array A regular expression or array of expressions that
     * defines valid words.
     * @param string The string to parse.
     * @return array|false False if no words were matched, the results of
     * preg_match, with offsets, if a match was found.
     */
    static protected function _parseWords($words, $value) {
        if (is_array($words)) {
            $words = implode('|', $words);
        }
        if ($words == '') {
            return false;
        }
        $result = preg_match(
            '/^\s*(' . $words . ')(\s+|$)/i', $value, $match, PREG_OFFSET_CAPTURE
        ) ? $match : false;
        return $result;
    }

    /**
     * Compare a version definition structure to a version and return match, if any.
     *
     * @param mixed Version definitions.
     * @param string Version to match
     * @return string|false Definition of matching version range or false.
     */
    static protected function _versionCheck($version, $check) {
        if (is_array($version)) {
            foreach ($version as $subVer) {
                if ($good = self::_versionCheck($subVer, $check)) {
                    return $good;
                }
            }
            return false;
        }
        $good = false;
        if (strstr($version, '-')) {
            // Range
            $minMax = explode('-', $version);
            if ($check >= $minMax[0] && $check <= $minMax[1]) {
                $good = $version;
            }
        } elseif ($posn = strstr($version, '+')) {
            // Open-ended
            if ($check >= substr($version, 0, $posn)) {
                $good = $version;
            }
        } elseif ($check == $version) {
            $good = $version;
        }
        return $good;
    }

    /**
     * Create a property definition
     *
     * @param string Name of the property.
     * @param mixed The media groups this property belongs to. One of: a string that
     * names the group; an array of group names; or null (meaning all groups).
     * @param string Valid version range spefifier. See {$_versions}.
     * @param mixed Optional validation rules. Can be any of: a simple string, a
     * numerically indexed array of alternative values; or an array of the preceeding,
     * indexed by CSS version range.
     * @return AP5L_Css_PropertyDef Initialized object.
     */
    static function factory($name, $groups, $versions, $validation = null) {
        $prop = new AP5L_Css_PropertyDef();
        $prop -> name = $name;
        if ($groups === null) {
            $prop -> _groups = null;
        } elseif (is_array($groups)) {
            $prop -> _groups = $groups;
        } else {
            $prop -> _groups = array($groups);
        }
        $prop -> _versions = $versions;
        if (is_array($validation)) {
            reset($validation);
            if (is_int(key($validation))) {
                // Use validation range equal to the definition
                $prop -> _validation = array($versions => $validation);
            } else {
                // Assume we have a version ranged set of definitions.
                $prop -> _validation = $validation;
            }
        } else {
            $prop -> _validation = array($versions, array($validation));
        }

        return $factory;
    }

    /**
     * Parse and validate a CSS property.
     *
     * @param string The property name.
     * @param string Value for the property.
     * @param string CSS version to validate against.
     * @return array|false Decomposed properties as AP5L_Css_Property objects.
     */
    static function parse($propName, $value, $version) {
        $propName = strtolower(trim($propName));
        if (! isset(self::$_versions[$propName])) {
            // Unknown property. Fail.
            return false;
        }
        if (! self::_versionCheck(self::$_versions[$propName], $version)) {
            // Property is defined but not for this version.
            return false;
        }
        list($value, $important) = AP5L_Css_Parser::removeImportant($value);
        $lcValue = strtolower($value);
        $defs = self::$_validation[$propName];
        if ($validKey = self::_versionCheck(array_keys($defs), $version)) {
            $result = false;
            foreach ($defs[$validKey] as $rule) {
                if ($rule[0] == '.') {
                    // Invoke property specific validation
                    if (
                        $result = call_user_func(
                            __CLASS__ . '::validate' . substr($rule, 1),
                            $validKey,
                            $propName,
                            $value,
                            $important
                        )
                    ) {
                        break;
                    }
                } elseif ($rule[0] == '<') {
                    // Try to match a common format, e.g. length
                    if (
                        $result = call_user_func(
                            __CLASS__ . '::parse' . substr($rule, 1),
                            $propName,
                            $value,
                            $important
                        )
                    ) {
                        /*
                         * The parse functions return arrays of (value, unit).
                         * In this simple case we load them into a property and
                         * proceed.
                         */
                        $result = array(
                            $propName => AP5L_Css_Property::factory(
                                $propName, $result['val'], $result['unit'], $important
                            )
                        );
                        break;
                    }
                } elseif ($lcValue == $rule) {
                    // Simple keyword match
                    $result = array(
                        $propName => AP5L_Css_Property::factory(
                            $propName, $value, '', $important
                        )
                    );
                    break;
                }
            }
        } else {
            /*
             * We appear to have no validation rules for this version. Just
             * jam the value and property name in and hope for the best.
             */
            $result = array(
                $propName => AP5L_Css_Property::factory($propName, $value, '', $important)
            );
        }
    }

    /**
     * Parse a string for an angle.
     *
     * @param string The value to parse.
     * @return array|false False if a valid angle was not found. If found, an array.
     * Index 0 contains the matched value or keywords, Index 1 contains the
     * units (if any), Index 2 contains the remainder of the string.
     */
    static function parseAngle($value) {
        $lcValue = strtolower($value);
        $result = false;
        // Try to match a numeric angle
        if (! $result = self::_parseUnit(self::ANGLE_REGEX, $value)) {
            // Normalize the value for whitespace
            $lcValue = trim(preg_replace('/\s+/', ' ', $lcValue));
            $words = explode(' ', $lcValue);
            if (isset(self::$_angleKeywords['leading'][$words[0]])) {
                // See if there's a second word
                if (isset($words[1]) && self::$_angleKeywords['leading'][$words[0]]) {
                    if (isset(self::$_angleKeywords['secondary'][$words[1]])) {
                        $result = array(
                            'val' => $words[0] . ' ' . $words[1],
                            'unit' => '',
                        );
                    } else {
                        $result = array('val' => $words[0], 'unit' => '');
                    }
                } else {
                    $result = array('val' => $words[0], 'unit' => '');
                }
            }
            if ($result) {
                // Return the value after we've removed the keywords
                $matchEx = '/^\s*' . str_replace(' ', '\s+', $result['val']) . '\s*/i';
                $result['buffer'] = preg_replace($matchEx, '', $value);
            }
        }
        return $result;
    }

    /**
     * Parse a clipping area.
     *
     * @param string The value to parse.
     * @return array|false False if a valid area was not found. If found, an array.
     * Index 0 contains the matched value, Index 1 is an empty string, and Index 2
     * contains the remainder of the string.
     */
    static function parseClip($value) {
        $result = false;
        if (preg_match('/^\s*(rect)\s*\(((?U).*)\)(\s*|$)/i', $value, $match)) {
            $size = strlen($value);
            // Found "rect(stuff)"
            $lengths = explode(',', $match[2][0]);
            if (count($lengths) == 4) {
                /*
                 * We have the right number of elements, put them together like
                 * other CSS and then use existing parse functions.
                 */
                $lengths = implode(' ', $lengths);
                $list = array(
                    'types' => self::TYPE_LENGTH | self::TYPE_WORDS,
                    'minCount' => 4,
                    'maxCount' => 4,
                    'words' => array('auto'),
                );
                if ($subMatch = self::_parseList($value, $list)) {
                    // We validate. Reassemble the string.
                    $lengths = array();
                    foreach ($subMatch['val'] as $ind => $num) {
                        $lengths[] = $num . $subMatch['unit'][$ind];
                    }

                    $len = $match[0][1] + strlen($match[0][0]) - (trim($match[3][0]) ? 1 : 0);
                    $result = array(
                        $match[1][0] . '(' . implode(',', $lengths) . ')',
                        '',
                        substr($value, $len)
                    );
                }
            }
        }
        return $result;
    }

    static function parseColor($value) {
        self::_buildColorMap();
        $result = false;
        $lcValue = strtolower($value);

        // rgb(r,g,b) -> #rrggbb
        if (preg_match(self::COLOR_RGB_REGEX, $value, $match, PREG_OFFSET_CAPTURE)) {
            $result = '#';
            $fullMatch = array_shift($match);
            $endMatch = array_pop($match);

            foreach ($match as $rangePair) {
                $range = $rangePair[0];
                if (substr($range, -1) == '%') {
                    $range = round((255 * $range) / 100);
                }
                $range = max(min($range, 255), 0);
                $result .= substr('0' . strtoupper(dechex($range)), -2);
            }
            $len = $fullMatch[1] + strlen($fullMatch[0]) - (trim($endMatch[0]) ? 1 : 0);
            $result = array(
                'buffer' => substr($value, $len),
                'val' => $result,
                'unit' => '',
            );
        } elseif (preg_match(self::COLOR_HEX_REGEX, $value, $match, PREG_OFFSET_CAPTURE)) {
            $work = strtoupper($match[1][0]);
            if (strlen($work) == 3) {
                $result = '#' . $work[0] . $work[0]
                    . $work[1] . $work[1]
                    . $work[2] . $work[2]
                ;
            } elseif (strlen($work) >= 6) {
                $result = '#' . substr($work, 0, 6);
            }
            if ($result) {
                $len = $match[0][1] + strlen($match[0][0]) - (trim($match[2][0]) ? 1 : 0);
                $result = array(
                    'buffer' => substr($value, $len),
                    'val' => $result,
                    'unit' => '',
                );
            }
        } else {
            // Normalize the value for whitespace
            $lcValue = trim(preg_replace('/\s+/', ' ', $lcValue));
            $words = explode(' ', $lcValue);
            if (isset(self::$_namedColors[$words[0]])) {
                $result = self::$_namedColors[$words[0]];
            } elseif ($words[0] == 'fuchsia') {
                $result = '#FF00FF';
            }
            if ($result !== false) {
                // Return the value after we've removed the keyword
                $matchEx = '/^\s*' . $words[0] . '\s*/i';
                $result = array(
                    'buffer' => preg_replace($matchEx, '', $value),
                    'val' => $result,
                    'unit' => '',
                );
            }
        }
        return $result;
    }

    static function parseInteger($value) {
        $result = false;
        if (preg_match(self::INTEGER_REGEX, $value, $match, PREG_OFFSET_CAPTURE)) {
            // Return the number without units.
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[2][0]) ? 1 : 0);
            $result = array(
                'buffer' => substr($value, $len),
                'val' => $match[1][0],
                'unit' => '',
            );
        }
        return $result;
    }

    static function parseLength($value) {
        return $result = self::_parseUnit(self::LENGTH_REGEX, $value);
    }

    static function parseNumber($value) {
        $result = false;
        if (preg_match(self::NUMBER_REGEX, $value, $match, PREG_OFFSET_CAPTURE)) {
            // Return the number without units.
            $result = array(
                'buffer' => substr($value, $match[0][1] + strlen($match[0][0])),
                'val' => $match[1][0],
                'unit' => '',
            );
        }
        return $result;
    }

    static function parsePercentage($value) {
        return $result = self::_parseUnit(self::PERCENTAGE_REGEX, $value);
    }

    static function parseQuoted($value, $allowUnquoted = false, $asList = false) {
        $result = false;
        $size = strlen($value);
        $trailComma = false;
        $state = 'skipws';
        $adjust = 0;
        $posn = 0;
        while ($posn < $size && $state != 'done') {
            if ($value[$posn] == '\\') {
                // Skip escaped characters
                ++$posn;
            } else {
                switch ($state) {
                    case 'end': {
                        /*
                         * We are in an unquoted identifier or past the
                         * closing quote and Looking for anything up to
                         * closing whitespace.
                         */
                        if (strpos(" \t\n\r", $value[$posn]) !== false) {
                            $state = $asList ? 'endcomma' : 'done';
                        } elseif ($asList && $value[$posn] == ',') {
                            $trailComma = true;
                            $adjust = 1;
                            $state = 'done';
                        }
                    }
                    break;

                    case 'endcomma': {
                        // Looking for whitespace leading to a delimiting comma.
                        if ($value[$posn] == ',') {
                            $trailComma = $asList;
                            $adjust = 1;
                            $state = 'done';
                        } elseif (strpos(" \t\n\r", $value[$posn]) === false) {
                            //$good = false;
                            --$posn;
                            $state = 'done';
                        }
                    }
                    break;

                    case 'skipws': {
                        // Skipping leading whitespace
                        if (strpos(" \t\n\r", $value[$posn]) === false) {
                            if ($value[$posn] == '\'' || $value[$posn] == '"') {
                                $endScan = $value[$posn++];
                                $state = 'str';
                            } elseif ($allowUnquoted) {
                                $endScan = '';
                                $state = 'end'; //$asList ? 'endcomma' : 'end';
                            } else {
                                return false;
                            }
                        }
                    }
                    break;

                    case 'str': {
                        // Looking to close a quoted string
                        if ($value[$posn] == $endScan) {
                            $state = $asList ? 'endcomma' : 'done';
                        } elseif ($asList && $value[$posn] == ',') {
                            $trailComma = true;
                            --$posn;
                            $state = 'done';
                        }
                    }
                    break;

                }
            }
            ++$posn;
        }
        if (in_array($state, array('done', 'end', 'endcomma'))) {
            $retval = substr($value, $posn);
            $result = array(
                'buffer' => ltrim($retval),
                'val' => trim(
                    preg_replace('/\s+/', ' ', substr($value, 0, $posn - $adjust))
                ),
                'unit' => '',
            );
            if ($asList) {
                $result['more'] = $trailComma;
            }
        }
        return $result;
    }

    static function parseUri($value, $asList = false) {
        $result = false;
        if (preg_match('/^\s*url\s*\(\s*/i', $value, $match)) {
            $size = strlen($value);
            // Found "url(" now check for quotes
            $posn = strlen($match[0]);
            if ($posn >= $size) {
                return $result;
            }
            if ($value[$posn] == '\'' || $value[$posn] == '"') {
                $endScan = $value[$posn++];
                $state = 'str';
            } else {
                $endScan = '';
                $state = 'end';
            }
            $good = true;
            $adjust = 0;
            while ($posn < $size && $state != 'done') {
                if ($value[$posn] == '\\') {
                    // Skip escaped characters
                    ++$posn;
                } else {
                    switch ($state) {
                        case 'end': {
                            // Looking for anything up to a closing parenthesis.
                            if ($value[$posn] == ')') {
                                $state = $asList ? 'endcomma' : 'done';
                            }
                        }
                        break;

                        case 'endcomma': {
                            // Looking for whitespace leading to a delimiting comma.
                            if ($value[$posn] == ',') {
                                $adjust = 1;
                                $state = 'done';
                            } elseif (strpos(" \t\n\r", $value[$posn]) === false) {
                                $good = false;
                                $state = 'done';
                            }
                        }
                        break;

                        case 'endws': {
                            // Looking for whitespace leading to a closing parenthesis.
                            if ($value[$posn] == ')') {
                                $state = $asList ? 'endcomma' : 'done';
                            } elseif (strpos(" \t\n\r", $value[$posn]) === false) {
                                $good = false;
                                $state = 'done';
                            }
                        }
                        break;

                        case 'str': {
                            // Looking to close a quoted string
                            if ($value[$posn] == $endScan) {
                                $state = 'endws';
                            }
                        }
                        break;

                    }
                }
                ++$posn;
            }
            if ($state == 'done' && $good) {
                $retval = substr($value, $posn);
                if (strlen($retval) == 0 || AP5L_Css_Parser::isWhitespace($retval[0])) {
                    $result = array(
                        'buffer' => ltrim($retval),
                        'val' => trim(preg_replace('/\s+/', ' ', substr($value, 0, $posn - $adjust))),
                        'unit' => '',
                    );
                }
            }
        }
        return $result;
    }

    static function validateAngle($version, $propName, $value, $important) {
        $result = false;
        if ($match = self::parseAngle($value)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'], $match['unit'], $important
                    )
                )
            );
        }
        return $result;
    }

    static function validateBackground(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $avail = array(
            'attachment' => 'validateBackgroundAttachment',
            'color' => 'validateBackgroundColor',
            'image' => 'validateGenericU0i',
            'position' => 'validateBackgroundPosition',
            'repeat' => 'validateBackgroundRepeat',
        );
        // First look for a simple inherit.
        if (
            $inheritable
            && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $value = substr($value, $len);
            $resultList = array();
            foreach ($avail as $key => $dummy) {
                $subPropName = $propName . '-' . $key;
                $resultList[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, 'inherit', '', $important
                );
            }
        } else {
            /*
             * Looking for anything from the list: color, image, repeat, attachment, position
             */
            $resultList = array();
            $hit = true;
            while ($hit && $value != '' && !empty($avail)) {
                $hit = false;
                foreach ($avail as $propSuffix => $subMethod) {
                    $subResult = call_user_func(
                        __CLASS__ . '::' . $subMethod,
                        $version,
                        'background-' . $propSuffix,
                        $value,
                        $important,
                        false
                    );
                    if ($subResult) {
                        $value = $subResult[0];
                        foreach ($subResult[1] as $key => $propObj) {
                            $resultList[$key] = $propObj;
                        }
                        unset($avail[$propSuffix]);
                        $hit = true;
                        break;
                    }
                }
            }
        }
        // There should be nothing else in the property.
        if ($value == '') {
            $result = array('', $resultList);
        }
        return $result;
    }

    static function validateBackgroundAttachment(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        if (
            $inheritable
            && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, 'inherit', '', $important
                    )
                )
            );
        } elseif (preg_match(self::BACKGROUND_ATTACHMENT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)) {
            // Keyword match
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[2][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, strtolower($match[1][0]), '', $important
                    )
                )
            );
        }
        return $result;
    }

    static function validateBackgroundColor(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $wordList = array('transparent');
        if ($inheritable) {
            $wordList[] = 'inherit';
        }
        if ($match = self::_parseWords($wordList, $value)) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[2][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, strtolower($match[1][0]), '', $important
                    )
                )
            );
        } elseif ($match = self::parseColor($value)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'], $match['unit'], $important
                    )
                )
            );
        }
        return $result;
    }

    static function validateBackgroundPosition(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        if (
            $inheritable
            && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, 'inherit', '', $important
                    )
                )
            );
        } elseif (preg_match(self::BACKGROUND_POSITION_REGEX, $value, $match, PREG_OFFSET_CAPTURE)) {
            // Keyword match
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[3][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, strtolower($match[1][0] . ' ' . $match[2][0]), '', $important
                    )
                )
            );
        } elseif (
            $match = self::_parseList(
                $value,
                array(
                    'types' => self::TYPE_PERCENT | self::TYPE_LENGTH,
                    'minCount' => 2,
                    'maxCount' => 2
                )
            )
        ) {
            // Grab a pair of values
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'], $match['unit'], $important
                    )
                )
            );
        }
        return $result;
    }

    static function validateBackgroundRepeat(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $wordList = array('no-repeat', 'repeat(?:-[xy])?');
        if ($inheritable) {
            $wordList[] = 'inherit';
        }
        if ($match = self::_parseWords($wordList, $value)) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[2][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, strtolower($match[1][0]), '', $important
                    )
                )
            );
        }
        return $result;
    }

    /**
     * Processing border, border-top, etc.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed properties.
     */
    static function validateBorder(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        //[ 'border-*-width' || 'border-style' || <color> ] | inherit
        $result = false;
        $subs = array('-color', '-style', '-width');
        /*
         * We match a strange composite of style, width, and color.
         */
        $list = self::$_listDefs['border-style'];
        $list['types'] |= self::$_listDefs['border-width']['types'] | self::TYPE_COLOR;
        $list['words'] = array_merge($list['words'], self::$_listDefs['border-width']['words']);
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            // Create sub-properties
            $props = array();
            for ($ind = 0; $ind < count($match[0]); ++$ind) {
                // Figure out what we have here
                if ($match['val'][$ind] == 'inherit') {
                    foreach ($subs as $sub) {
                        $props[$propName . $sub] = AP5L_Css_Property::factory(
                            $propName . $sub, 'inherit', '', $important
                        );
                    }
                } elseif ($match['val'][$ind][0] == '#') {
                    $subPropName = $propName . '-color';
                    $props[$subPropName] = AP5L_Css_Property::factory(
                        $subPropName, $match['val'][$ind], $match['unit'][$ind], $important
                    );
                } elseif (in_array($match['val'][$ind], self::$_listDefs['border-width']['words'])) {
                    $subPropName = $propName . '-width';
                    $props[$subPropName] = AP5L_Css_Property::factory(
                        $subPropName, $match['val'][$ind], $match['unit'][$ind], $important
                    );
                } else {
                    $subPropName = $propName . '-style';
                    $props[$subPropName] = AP5L_Css_Property::factory(
                        $subPropName, $match['val'][$ind], $match['unit'][$ind], $important
                    );
                }
            }
            $result = array($match['buffer'], $props);
        }
        return $result;
    }

    /**
     * Processing border-top-color, etc.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateBorderColor($version, $propName, $value, $important) {
        $result = false;
        $list = self::$_listDefs['border-color'];
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing border-color.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed properties, decomposed for each side.
     */
    static function validateBorderColor4($version, $propName, $value, $important) {
        $result = false;
        $list = self::$_listDefs['border-color'];
        $list['maxCount'] = 4;
        // Look for a match and make sure we didn't mix colours and keywords.
        $match = self::_parseList($value, $list);
        if ($match && $match['mask'] != (self::TYPE_COLOR | self::TYPE_WORDS)) {
            // Fill in any missing values by duplicating.
            for ($source = 0, $ind = count($match['val']); $ind < 4; ++$source, ++$ind) {
                $match['val'][$ind] = $match['val'][$source];
                $match['unit'][$ind] = $match['unit'][$source];
            }
            // Create sub-properties
            $props = array();
            for ($ind = 0; $ind < 4; ++$ind) {
                $subPropName = 'border-' . self::$_edgeNames[$ind] . '-color';
                $props[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, $match['val'][$ind], $match['unit'][$ind], $important
                );
            }
            $result = array($match['buffer'], $props);
        }
        return $result;
    }

    /**
     * Processing border-top-style, etc.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateBorderStyle(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['border-style'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing border-style.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed properties, decomposed for each side.
     */
    static function validateBorderStyle4(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['border-style'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 4;
        if ($match = self::_parseList($value, $list)) {
            // Fill in any missing values by duplicating.
            for ($source = 0, $ind = count($match['val']); $ind < 4; ++$source, ++$ind) {
                $match['val'][$ind] = $match['val'][$source];
                $match['unit'][$ind] = $match['unit'][$source];
            }
            // Create sub-properties
            $props = array();
            for ($ind = 0; $ind < 4; ++$ind) {
                $subPropName = 'border-' . self::$_edgeNames[$ind] . '-style';
                $props[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, $match['val'][$ind], $match['unit'][$ind], $important
                );
            }
            $result = array($match['buffer'], $props,);
        }
        return $result;
    }

    /**
     * Processing border-top-width, etc.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateBorderWidth(
        $version, $propName, $value, $important
    ) {
        $result = false;
        $list = self::$_listDefs['border-width'];
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing border-width.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed properties, decomposed for each side.
     */
    static function validateBorderWidth4($version, $propName, $value, $important) {
        $result = false;
        $list = self::$_listDefs['border-width'];
        $list['maxCount'] = 4;
        if ($match = self::_parseList($value, $list)) {
            // Fill in any missing values by duplicating.
            for ($source = 0, $ind = count($match['val']); $ind < 4; ++$source, ++$ind) {
                $match['val'][$ind] = $match['val'][$source];
                $match['unit'][$ind] = $match['unit'][$source];
            }
            // Create sub-properties
            $props = array();
            for ($ind = 0; $ind < 4; ++$ind) {
                $subPropName = 'border-' . self::$_edgeNames[$ind] . '-width';
                $props[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, $match['val'][$ind], $match['unit'][$ind], $important
                );
            }
            $result = array($match['buffer'], $props,);
        }
        return $result;
    }

    /**
     * Processing clip.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateClip(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_WORDS,
            'minCount' => 1,
            'maxCount' => 1,
            'words' => array('auto'),
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        } elseif ($match = self::parseClip($value)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'], $match['unit'], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateContent(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        // This is messy, just assume everything works for the moment.
        // FIXME: Write a real validator for this.
        $result = array(
            '',
            array(
                $propName => AP5L_Css_Property::factory(
                    $propName, trim(preg_replace('/\s+/', ' ', $value)), '', $important
                ),
            ),
        );
        return $result;
    }

    /**
     * Processing counter-increment, counter-reset.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateCounter(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $regex = $inheritabble ? self::COUNTER_REGEX : self::COUNTER_INHERIT_REGEX;
        if (
            preg_match($regex, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            $last = count($match) - 1;
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[$last][0]) ? 1 : 0);
            $propVal = substr($value, 0, $len);
            $propVal = trim(preg_replace('/\s+/', ' ', $propVal));
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $propVal, '', $important
                    )
                )
            );
        }
        return $result;
    }

    /**
     * Processing cue-before, cue-after.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateCue(
        $version, $propName, $value, $important, $inheritable = true
    ) {
    }

    /**
     * Processing cue.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateCue2(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['cue'];
        $list['maxCount'] = 2;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing cursor.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateCursor(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['cursor'];
        if ($match = self::_parseList($value, $list)) {
            // Copy any number of URIs and at most one keyword
            $parts = array();
            foreach ($match['val'] as $key => $part) {
                $parts[] = $part;
                if ($match['type'][$key] == self::TYPE_WORDS) {
                    break;
                }
            }
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, implode(',', $parts), '', $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateElevation(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['elevation'];
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateFont(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        //[ [ 'font-style' || 'font-variant' || 'font-weight' ]?
        // 'font-size' [ / 'line-height' ]? 'font-family' ]
        $result = false;
        $props = array();
        if (
            $inheritable && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            // Simple inherit case. Push into sub-properties.
            $subProps = array(
                '_font', 'font-family', 'font-size', 'font-style',
                'font-variant', 'font-weight', 'line-height',
            );
            foreach ($subProps as $subPropName) {
                $props[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, 'inherit', '', $important
                );
            }
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $result = array(substr($value, $len), $props);
        } elseif (
            $match = self::_parseWords(
                'caption|icon|menu|message\-box|small\-caption|status\-bar', $value
            )
        ) {
            // Give me five minutes in a closed room with the idiots who allowed this.
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[2][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    '_font' => AP5L_Css_Property::factory(
                        '_font', $match[1][0], '', $important
                    )
                ),
            );
        } else {
            // Try picking up a combination of style, variant, and weight
            $good = true;
            $anyHit = true;
            while ($anyHit) {
                $anyHit = false;
                $match = self::validateFontStyle($version, 'font-style', $value, $important, false);
                if ($match) {
                    $anyHit = true;
                    $props = array_merge($props, $match[1]);
                    $value = $match[0];
                    continue;
                }
                $match = self::validateFontVariant($version, 'font-variant', $value, $important, false);
                if ($match) {
                    $anyHit = true;
                    $props = array_merge($props, $match[1]);
                    $value = $match[0];
                    continue;
                }
                $match = self::validateFontWeight($version, 'font-weight', $value, $important, false);
                if ($match) {
                    $anyHit = true;
                    $props = array_merge($props, $match[1]);
                    $value = $match[0];
                    continue;
                }
            }
            // Now the mandatory font size
            if ($good && $match = self::validateFontSize($version, 'font-size', $value, $important, false)) {
                $props = array_merge($props, $match[1]);
                $value = $match[0];
            } else {
                $good = false;
            }
            // Line height
            if (
                $good
                && !empty($value)
                && $value[0] == '/'
            ) {
                $value = substr($value, 1);
                if ($match = self::validateLineHeight($version, 'line-height', $value, $important, false)
                ) {
                    $props = array_merge($props, $match[1]);
                    $value = $match[0];
                } else {
                    $good = false;
                }
            }
            // And now the font family
            if (
                $good
                && $match = self::validateFontFamily($version, 'font-family', $value, $important, false)
            ) {
                $props = array_merge($props, $match[1]);
                $value = $match[0];
            } else {
                $good = false;
            }
            if ($good) {
                $result = array($value, $props);
            }
        }
        return $result;
    }

    static function validateFontFamily(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        if (
            $inheritable && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $result = array(
                substr($value, $len),
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, 'inherit', '', $important
                    ),
                ),
            );
        } else {
            $list = self::$_listDefs['font-family'];
            if ($match = self::_parseList($value, $list)) {
                $result = array(
                    $match['buffer'],
                    array(
                        $propName => AP5L_Css_Property::factory(
                            $propName, implode(',', $match['val']), '', $important
                        ),
                    ),
                );
            }
        }
        return $result;
    }

    static function validateFontSize(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['font-size'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateFontStyle(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['font-style'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateFontVariant(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['font-variant'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateFontWeight(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['font-weight'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing anything tha takes <length>|auto[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateGenericLai(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_LENGTH | self::TYPE_WORDS,
            'minCount' => 1,
            'maxCount' => 1,
            'words' => array('auto'),
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing anything tha takes <length>|<percentage>|auto[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateGenericLpai(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_LENGTH | self::TYPE_PERCENT | self::TYPE_WORDS,
            'minCount' => 1,
            'maxCount' => 1,
            'words' => array('auto'),
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing anything tha takes <length>|<percentage>|auto{1,4}[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateLpai4($version, $propName, $value, $important) {
        $result = false;
        if (
            $inheritable && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            // Simple inherit case. Push into sub-properties.
            for ($ind = 0; $ind < 4; ++$ind) {
                $subPropName = $propName . '-' . self::$_edgeNames[$ind];
                $props[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, 'inherit', '', $important
                );
            }
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $result = array(substr($value, $len), $props);
        } else {
            $list = array(
                'types' => self::TYPE_LENGTH | self::TYPE_PERCENT | self::TYPE_WORDS,
                'minCount' => 1,
                'maxCount' => 4,
                'words' => array('auto'),
            );
            if ($match = self::_parseList($value, $list)) {
                // Fill in any missing values by duplicating.
                for ($source = 0, $ind = count($match['val']); $ind < 4; ++$source, ++$ind) {
                    $match['val'][$ind] = $match['val'][$source];
                    $match['unit'][$ind] = $match['unit'][$source];
                }
                // Create sub-properties
                $props = array();
                for ($ind = 0; $ind < 4; ++$ind) {
                    $subPropName = $propName . '-' . self::$_edgeNames[$ind];
                    $props[$subPropName] = AP5L_Css_Property::factory(
                        $subPropName, $match['val'][$ind], $match['unit'][$ind], $important
                    );
                }
                $result = array($match['buffer'], $props,);
            }
        }
        return $result;
    }

    /**
     * Processing anything that takes <length>|normal[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateGenericLni(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_LENGTH | self::TYPE_WORDS,
            'minCount' => 1,
            'maxCount' => 1,
            'words' => array('normal'),
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing anything that takes <length>|<percentage>|none[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateGenericLp0i(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_LENGTH | self::TYPE_PERCENT | self::TYPE_WORDS,
            'minCount' => 1,
            'maxCount' => 1,
            'words' => array('none'),
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing anything that takes <length>|<percentage>[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateGenericLpi(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_LENGTH | self::TYPE_PERCENT,
            'minCount' => 1,
            'maxCount' => 1,
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing anything that takes <number>[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateGenericNi(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_NUMBER,
            'minCount' => 1,
            'maxCount' => 1,
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing anything that takes <uri>|none[|inherit].
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateGenericU0i(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list =  array(
            'types' => self::TYPE_HACK_UW,
            'minCount' => 1,
            'words' => array('none', 'inherit'),
        );
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateLineHeight(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['line-height'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateListStyle(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $avail = array(
            'image' => 'validateListGenericU0i',
            'position' => 'validateListStylePosition',
            'type' => 'validateListStyleType',
        );
        // First look for a simple inherit.
        if (
            $inheritable
            && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $value = substr($value, $len);
            $resultList = array();
            foreach ($avail as $key => $dummy) {
                $subPropName = $propName . '-' . $key;
                $resultList[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, 'inherit', '', $important
                );
            }
        } else {
            /*
             * Looking for anything from the list: image, position, type
             */
            $resultList = array();
            $hit = true;
            while ($hit && $value != '' && !empty($avail)) {
                $hit = false;
                foreach ($avail as $propSuffix => $subMethod) {
                    $subResult = call_user_func(
                        __CLASS__ . '::' . $subMethod,
                        $version,
                        'background-' . $propSuffix,
                        $value,
                        $important,
                        false
                    );
                    if ($subResult) {
                        $value = $subResult[0];
                        foreach ($subResult[1] as $key => $propObj) {
                            $resultList[$key] = $propObj;
                        }
                        unset($avail[$propSuffix]);
                        $hit = true;
                        break;
                    }
                }
            }
        }
        // There should be nothing else in the property.
        if ($value == '') {
            $result = array('', $resultList);
        }
        return $result;
    }

    static function validateListStylePosition(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_WORDS,
            'minCount' => 1,
            'maxCount' => 1,
            'words' => array('inside', 'outside'),
        );
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateListStyleType(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = array(
            'types' => self::TYPE_WORDS,
            'minCount' => 1,
            'maxCount' => 1,
            'words' => array(
                'circle', 'decimal', 'disc', 'square',
                'lower-alpha', 'lower-roman', 'none',
                'upper-alpha', 'upper-roman',
            ),
        );
        if ($version == '2.0+') {
            $list['words'] = array_merge(
                $list['words'],
                array(
                    'armenian',
                    'cjk-ideographic',
                    'decimal-leading-zero',
                    'georgian',
                    'hebrew',
                    'hiragana',
                    'hiragana-iroha',
                    'katakana',
                    'katakana-iroha',
                    'lower-greek',
                    'lower-latin',
                    'upper-latin',
                )
            );
        }
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateMargin(
        $version, $propName, $value, $important, $inheritable = true
    ) {
    }

    static function validateMarks(
        $version, $propName, $value, $important, $inheritable = true
    ) {
    }

    static function validateOutline(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $avail = array(
            'color' => 'validateOutlineColor',
            'style' => 'validateOutlineStyle',
            'width' => 'validateBorderWidth',
        );
        // First look for a simple inherit.
        if (
            $inheritable
            && preg_match(self::INHERIT_REGEX, $value, $match, PREG_OFFSET_CAPTURE)
        ) {
            $len = $match[0][1] + strlen($match[0][0]) - (trim($match[1][0]) ? 1 : 0);
            $value = substr($value, $len);
            $resultList = array();
            foreach ($avail as $key => $dummy) {
                $subPropName = $propName . '-' . $key;
                $resultList[$subPropName] = AP5L_Css_Property::factory(
                    $subPropName, 'inherit', '', $important
                );
            }
        } else {
            /*
             * Looking for anything from the list: color, style, width
             */
            $resultList = array();
            $hit = true;
            while ($hit && $value != '' && !empty($avail)) {
                $hit = false;
                foreach ($avail as $propSuffix => $subMethod) {
                    $subResult = call_user_func(
                        __CLASS__ . '::' . $subMethod,
                        $version,
                        'background-' . $propSuffix,
                        $value,
                        $important,
                        false
                    );
                    if ($subResult) {
                        $value = $subResult[0];
                        foreach ($subResult[1] as $key => $propObj) {
                            $resultList[$key] = $propObj;
                        }
                        unset($avail[$propSuffix]);
                        $hit = true;
                        break;
                    }
                }
            }
        }
        // There should be nothing else in the property.
        if ($value == '') {
            $result = array('', $resultList);
        }
        return $result;
    }

    /**
     * Processing outline-color.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateOutlineColor(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['outline-color'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    /**
     * Processing outline-style.
     *
     * @param string CSS version to validate against.
     * @param string Base property name.
     * @param string Property value.
     * @param boolean State of !important flag.
     * @param boolean Flag set if the property value can be "inherit"
     * @return array|boolean False on parse failure. On success, index 0 is the
     * unparsed portion of the value, or an empty string. Index 1 is an array
     * containing the parsed property.
     */
    static function validateOutlineStyle(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['outline-style'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validatePause(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['pause'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validatePause2(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['pause'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 2;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'], $match['unit'], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validatePitch(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['pitch'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validatePlayDuring(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['play-during'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 1;
        if ($match = self::_parseList($value, $list)) {
            $value = $match['buffer'];
            if ($match['type'][0] == self::TYPE_URI) {
                $subList = array(
                    'types' => self::TYPE_WORDS,
                    'maxCount' => 2,
                    'minCount' => 0,
                    'words' => array('mix', 'repeat'),
                );
                if ($subMatch = self::_parselist($value, $subList)) {
                    $value = $subMatch['buffer'];
                    foreach (array('val', 'unit', 'type') as $axis) {
                        $match[$axis] = array_merge($match[$axis], $subMatch[$axis]);
                    }
                }
            }
            $result = array(
                $value,
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'][0], $match['unit'][0], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateQuotes(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['quotes'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $props = array();
            $size = count($match['val']);
            if ($size == 1 && in_array($match['val'][0], $list['words'])) {
                $props[$propName] = AP5L_Css_Property::factory(
                    $propName, $match['val'][0], $match['unit'][0], $important
                );
            } elseif (!($size & 1)) {
                $props[$propName] = AP5L_Css_Property::factory(
                    $propName, $match['val'], $match['unit'], $important
                );
            } else {
                return false;
            }
            $result = array($match['buffer'], $props);
        }
        return $result;
    }

    static function validateSize(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['size'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'], $match['unit'], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateTextAlign(
        $version, $propName, $value, $important, $inheritable = true
    ) {
        $result = false;
        $list = self::$_listDefs['text-align'];
        if ($inheritable) {
            $list['words'][] = 'inherit';
        }
        $list['maxCount'] = 2;
        if ($match = self::_parseList($value, $list)) {
            $result = array(
                $match['buffer'],
                array(
                    $propName => AP5L_Css_Property::factory(
                        $propName, $match['val'], $match['unit'], $important
                    ),
                ),
            );
        }
        return $result;
    }

    static function validateTextDecoration(
        $version, $propName, $value, $important, $inheritable = true
    ) {
    }

    function versionCheck($check) {
        // Strip off any "CSS"
        if (($posn = stristr($check, 'css')) !== false) {
            $check = substr($check, $posn + 3);
        }
        return $this -> _versionCheck($this -> _versions[$this -> name], $check);
    }

}
