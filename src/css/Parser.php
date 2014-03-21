<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2009, Alan Langford
 * @version $Id: Parser.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * @package AP5L
 * @author Florian Schmitz (floele at gmail dot com) 2005-2006
 */

/**
 * Parses a CSS style sheet into a set of raw rules.
 *
 * Based in part on the CSSTidy parser by Florian Schmitz (floele at gmail dot com)
 *
 * @package AP5L
 * @subpackage Css
 */
class AP5L_Css_Parser {

    /**
     * Parser states.
     */
    const STATE_ATBLOCK = 0;
    const STATE_COMMENT = 1;
    const STATE_PROPERTY = 2;
    const STATE_SELECTOR = 3;
    const STATE_STRING = 4;
    const STATE_VALUE = 5;

    const TOK_AT_START = 1;
    const TOK_AT_END = 2;
    const TOK_SEL_START = 3;
    const TOK_SEL_END = 4;
    const TOK_PROPERTY = 5;
    const TOK_VALUE = 6;
    const TOK_COMMENT = 7;
    const TOK_DEFAULT_AT = 8;

    /**
     * Set true if something has been added to the current selector.
     *
     * @var bool
     */
    private $_added = false;

    /**
     * Saves the current at rule (@media).
     *
     * @var string
     */
    private $_atRule = '';

    /**
     * Available at-rules
     *
     * @var array
     */
    static protected $_atRules = array(
        'charset' => self::STATE_VALUE,
        'font-face' => self::STATE_SELECTOR,
        'import' => self::STATE_VALUE,
        'media' => self::STATE_ATBLOCK,
        'namespace' => self::STATE_VALUE,
        'page' => self::STATE_SELECTOR,
    );

    /**
     * Set when an invalid at-rule is found.
     *
     * @var boolean
     */
    protected $_badAt = false;

    /**
     * All CSS delimiters used by csstidy
     *
     * @var string
     */
    static protected $_delims = '/@}{;:=\'"(,\\!$%&)*+.<>?[]^`|~';

    /**
     * Parser state before entering comment or string scan.
     *
     * @var string
     */
    protected $_prevState = '';

    /**
     * Option settings.
     *
     * @var array
     */
    protected $_settings = array(
        'case_properties' => 1,
        'compress_colors' => true,
        'compress_font-weight' => true,
        'css_level' => 'CSS2.1',
        'discard_invalid_properties' => false,
        'lowercase_s' => false,
        'merge_selectors' => 2,
        'optimise_shorthands' => 1,
        'preserve_css' => false,
        'remove_bslash' => true,
        'remove_last_,' => false,
        'sort_properties' => false,
        'sort_selectors' => false,
        'timestamp' => false,
    );

    /**
     * All whitespace allowed in CSS
     *
     * @var array
     */
    static protected $_whitespace = array(' ', "\n", "\t", "\r", "\x0B");

    /**
     * A regular expression to match CSS whitespace.
     *
     * @var string
     */
    protected $_whitespaceRegex;

    /**
     * The parsed Styles.
     *
     * @var AP5L_Css_Sheet
     */
    public $sheet;

    /**
     * Saves the current selector
     * @var string
     * @access private
     */
    public $selector = '';

    /**
     * Saves the current value
     * @var string
     * @access private
     */
    var $value = '';

    /**
     * Saves the char which opened the last string
     * @var string
     */
    var $str_char = '';

    /**
     *
     */
    var $currString = '';

    /**
     * Saves the line number
     * @var integer
     * @access private
     */
    var $line = 1;

    /**
     * Initialize the parser.
     */
    function __construct() {
        $this -> _whitespaceRegex = '|[' . implode('', self::$_whitespace) . ']|uis';
    }

    /**
     * Add a property:value pair to the list of rules.
     *
     * @param string The media type for this property.
     * @param string The selection expression.
     * @param string The property name.
     * @param string The property value.
     */
    function _addProperty($media, $selector, $property, $new_val) {
        if ($this -> getConfig('preserve_css') || trim($new_val) == '') {
            return;
        }

        $this -> _added = true;
        if (isset($this -> sheet -> rules[$media][$selector][$property])) {
            // Apply precedence via !important
            if (
                (
                    self::isImportant($this -> sheet -> rules[$media][$selector][$property])
                    && self::isImportant($new_val)
                )
                || !self::isImportant($this -> sheet -> rules[$media][$selector][$property])
            ) {
                $this -> sheet -> rules[$media][$selector][$property] = trim($new_val);
            }
        } else {
            $this -> sheet -> rules[$media][$selector][$property] = trim($new_val);
        }
    }

    /**
     * Get a configuration value.
     *
     * @param string Name of the item.
     * @return mixed Value of the setting or false if not set.
     */
    function getConfig($setting) {
        if (isset($this -> _settings[$setting])) {
            return $this -> _settings[$setting];
        }
        return false;
    }

    /**
     * Set a configuration value.
     *
     * @param string Name of the item to set.
     * @param mixed Value to set.
     * @return boolean
     */
    function setConfig($setting, $value) {
        if (isset($this -> _settings[$setting]) && $value !== '') {
            $this -> _settings[$setting] = $value;
            return true;
        }
        return false;
    }

    /**
     * Adds a token to the sheet.
     *
     * @param mixed $type
     * @param string $data
     * @param boolean Add a token even if preserve_css is off.
     */
    protected function _addToken($type, $data, $do = false) {
        $this -> sheet -> tokens[] = array(
            'tokType' => $type,
            'value' => ($type == self::TOK_COMMENT) ? $data : trim($data)
        );
    }

    /**
     * Parse unicode notations and find a replacement character.
     *
     * @param string The string containing the unicode character.
     * @param integer Position of the character in the string.
     * @param integer The size of the buffer.
     * @return string
     */
    protected function _unicode(&$buffer, &$posn, &$size)     {
        ++$posn;
        $add = '';
        $replaced = false;
        while (
            $posn < strlen($buffer)
            && (
                ctype_xdigit($buffer[$posn])
                || ctype_space($buffer[$posn])
            )
            && strlen($add) < 6
        ) {
            $add .= $buffer[$posn];
            if (ctype_space($buffer[$posn])) {
                break;
            }
            ++$posn;
        }

        if (
            hexdec($add) > 47 && hexdec($add) < 58
            || hexdec($add) > 64 && hexdec($add) < 91
            || hexdec($add) > 96 && hexdec($add) < 123
        ) {
            $this -> sheet -> log(
                'Replaced unicode notation: Changed \\' . $add . ' to '
                . chr(hexdec($add)), AP5L_Css_Sheet::MSG_INFO,
                $this -> line
            );
            $add = chr(hexdec($add));
            $replaced = true;
        } else {
            $add = trim('\\'.$add);
        }

        if (
            @ctype_xdigit($buffer[$posn + 1])
            && ctype_space($buffer[$posn])
            && !$replaced
            || !ctype_space($buffer[$posn])
        ) {
            --$posn;
        }

        if (
            $add != '\\'
            || !$this -> getConfig('remove_bslash')
            || strpos(self::$_delims, $buffer[$posn + 1]) !== false
        ) {
            $size = strlen($buffer);
            return $add;
        }

        if ($add == '\\') {
            $this -> sheet -> log(
                'Removed unnecessary backslash',
                AP5L_Css_Sheet::MSG_INFO,
                $this -> line
            );
        }
        $size = strlen($buffer);
        return '';
    }

    /**
     * Parse from a URL.
     *
     * @param string Address of a style sheet.
     */
    function parseUrl($url) {
        return $this -> parse(@file_get_contents($url));
    }

    /**
     * Checks if there is a token at the current position.
     *
     * @param string String containing the CSS.
     * @param integer The current position.
     */
    static function isToken(&$buffer, $posn) {
        return (
            strpos(self::$_delims, $buffer[$posn]) !== false
            && !self::isCharEscaped($buffer, $posn)
        );
    }


    /**
     * Parse a CSS source.
     *
     * @param string CSS code.
     * @return boolean
     */
    function parse($buffer) {
        $nested = false;
        $this -> sheet = new AP5L_Css_Sheet();
        $buffer = str_replace(array("\r\n", "\r"), "\n", $buffer) . ' ';
        $parseState = self::STATE_SELECTOR;
        $property = '';
        $word = '';
        $wordList = array();
        $cur_comment = '';

        $size = strlen($buffer);
        for ($posn = 0; $posn < $size; ++$posn) {
            $ch = $buffer[$posn];
            $eos = $posn + 1 >= $size;
            $nch = $eos ? '' : $buffer[$posn + 1];
            $eol = $ch == "\n";
            if ($eol) {
                ++$this -> line;
            }
            //echo $parseState . ' ' . $this -> line . ' ' . $posn . ' [' . $ch . ']' . chr(10);
            $newState = $parseState;
            switch ($parseState) {
                case self::STATE_ATBLOCK: {
                    /* Case in at-block */
                    if (self::isToken($buffer, $posn)) {
                        if ($ch == '/' && $nch == '*') {
                            $newState = self::STATE_COMMENT;
                            ++$posn;
                            $this -> _prevState = self::STATE_ATBLOCK;
                        } elseif ($ch == '{') {
                            $newState = self::STATE_SELECTOR;
                            $this -> _addToken(self::TOK_AT_START, $this -> _atRule);
                        } elseif($ch == ',') {
                            $this -> _atRule = trim($this -> _atRule) . ',';
                        } elseif($ch == '\\') {
                            $this -> _atRule .= $this -> _unicode($buffer, $posn, $size);
                        }
                    } else {
                        $lastpos = strlen($this -> _atRule) - 1;
                        if (!((ctype_space($this -> _atRule[$lastpos])
                            || self::isToken($this -> _atRule, $lastpos)
                            && $this -> _atRule[$lastpos] == ',') && ctype_space($ch))
                        ) {
                            $this -> _atRule .= $ch;
                        }
                    }
                }
                break;

                case self::STATE_SELECTOR: {
                    /* Case in-selector */
                    if (self::isToken($buffer, $posn)) {
                        if ($ch == '/' && $nch == '*' && trim($this -> selector) == '') {
                            $newState = self::STATE_COMMENT;
                            ++$posn;
                            $this -> _prevState = self::STATE_SELECTOR;
                        } elseif ($ch == '@' && trim($this -> selector) == '') {
                            // Check for at-rule
                            $this -> _badAt = true;
                            foreach (self::$_atRules as $name => $type) {
                                if (
                                    !strcasecmp(substr($buffer, $posn + 1, strlen($name)), $name)
                                ) {
                                    if ($type == self::STATE_ATBLOCK) {
                                        $this -> _atRule = '@' . trim($name);
                                    } else {
                                        $this -> selector = '@' . trim($name);
                                    }
                                    $newState = $type;
                                    $posn += strlen($name);
                                    $this -> _badAt = false;
                                }
                            }

                            if ($this -> _badAt) {
                                $this -> selector = '@';
                                $badAtName = '';
                                for ($j = $posn + 1; $j < $size; ++$j) {
                                    if (!ctype_alpha($buffer[$j])) {
                                        break;
                                    }
                                    $badAtName .= $buffer[$j];
                                }
                                $this -> sheet -> log(
                                    'Invalid @-rule: ' . $badAtName . ' (removed)',
                                    AP5L_Css_Sheet::MSG_WARN,
                                    $this -> line
                                );
                            }
                        } elseif (($ch == '"' || $ch == "'")) {
                            $this -> currString = $ch;
                            $newState = self::STATE_STRING;
                            $this -> str_char = $ch;
                            $this -> _prevState = self::STATE_SELECTOR;
                        } elseif ($this -> _badAt && $ch == ';') {
                            $this -> _badAt = false;
                            $newState = self::STATE_SELECTOR;
                        } elseif ($ch == '{') {
                            $newState = self::STATE_PROPERTY;
                            $this -> _addToken(self::TOK_SEL_START, $this -> selector);
                            $this -> _added = false;
                        } elseif ($ch == '}') {
                            $this -> _addToken(self::TOK_AT_END, $this -> _atRule);
                            $this -> _atRule = '';
                            $this -> selector = '';
                        } elseif ($ch == ',') {
                            $this -> selector = trim($this -> selector) . ',';
                        } elseif ($ch == '\\') {
                            $this -> selector .= $this -> _unicode($buffer, $posn, $size);
                        } elseif (
                            !($ch == '*'
                            && in_array($nch, array('.', '#', '[', ':')))
                        ) {
                        // remove unnecessary universal selector,  FS#147
                            $this -> selector .= $ch;
                        }
                    } else {
                        $lastpos = strlen($this -> selector) - 1;
                        if (
                            $lastpos == -1
                            || !(
                                (
                                    ctype_space($this -> selector[$lastpos])
                                    || self::isToken($this -> selector, $lastpos)
                                    && $this -> selector[$lastpos] == ','
                                )
                                && ctype_space($ch)
                            )
                        ) {
                            $this -> selector .= $ch;
                        }
                    }
                }
                break;

                case self::STATE_PROPERTY: {
                    /* Case in-property */
                    if (self::isToken($buffer, $posn)) {
                        if (($ch == ':' || $ch == '=') && $property != '') {
                            $newState = self::STATE_VALUE;
                            if (
                                !$this -> getConfig('discard_invalid_properties')
                                || AP5L_Css_Sheet::isProperty($property)
                            ) {
                                $this -> _addToken(self::TOK_PROPERTY, $property);
                            }
                        } elseif ($ch == '/' && $nch == '*' && $property == '') {
                            $newState = self::STATE_COMMENT;
                            ++$posn;
                            $this -> _prevState = self::STATE_PROPERTY;
                        } elseif ($ch == '}') {
                            $newState = self::STATE_SELECTOR;
                            $this -> _badAt = false;
                            $this -> _addToken(self::TOK_SEL_END, $this -> selector);
                            $this -> selector = '';
                            $property = '';
                        } elseif ($ch == ';') {
                            $property = '';
                        } elseif ($ch == '\\') {
                            $property .= $this -> _unicode($buffer, $posn, $size);
                        }
                    } elseif (!ctype_space($ch)) {
                        $property .= $ch;
                    }
                }
                break;

                case self::STATE_VALUE: {
                    /* Case in-value */
                    $pn = $eol && $this -> nextIsProperty($buffer, $posn + 1) || $eos;
                    if (self::isToken($buffer, $posn) || $pn) {
                        if ($ch == '/' && $nch == '*') {
                            $newState = self::STATE_COMMENT;
                            ++$posn;
                            $this -> _prevState = self::STATE_VALUE;
                        } elseif (($ch == '"' || $ch == "'" || $ch == '(')) {
                            $this -> currString = $ch;
                            $this -> str_char = ($ch == '(') ? ')' : $ch;
                            $newState = self::STATE_STRING;
                            $this -> _prevState = self::STATE_VALUE;
                        } elseif ($ch == ',') {
                            $word = trim($word) . ',';
                        } elseif ($ch == '\\') {
                            $word .= $this -> _unicode($buffer, $posn, $size);
                        } elseif ($ch == ';' || $pn) {
                            if (
                                $this -> selector[0] == '@'
                                && isset(self::$_atRules[substr($this -> selector, 1)])
                                && self::$_atRules[substr($this -> selector, 1)] == self::STATE_VALUE
                            ) {
                                $wordList[] = trim($word);
                                $newState = self::STATE_SELECTOR;

                                switch($this -> selector) {
                                    case '@charset': {
                                        $this -> sheet -> charset = $wordList[0];
                                    }
                                    break;

                                    case '@namespace': {
                                        $this -> sheet -> namespace = implode(' ', $wordList);
                                    }
                                    break;

                                    case '@import': {
                                        $this -> sheet -> imports[] = implode(
                                            ' ', $wordList
                                        );
                                    }
                                    break;
                                }

                                $wordList = array();
                                $word = '';
                                $this -> selector = '';
                            } else {
                                $newState = self::STATE_PROPERTY;
                            }
                        } elseif ($ch != '}') {
                            $word .= $ch;
                        }
                        if (($ch == '}' || $ch == ';' || $pn) && !empty($this -> selector)) {
                            if ($this -> _atRule == '') {
                                //$this -> _atRule = self::TOK_DEFAULT_AT;
                            }

                            // case settings
                            if ($this -> getConfig('lowercase_s')) {
                                $this -> selector = strtolower($this -> selector);
                            }
                            $property = strtolower($property);
                            if ($word != '') {
                                $wordList[] = $word;
                                $word = '';
                            }
                            $this -> value = implode(' ', $wordList);
                            $this -> selector = trim($this -> selector);
                            $valid = AP5L_Css_Sheet::isProperty($property);
                            if ((
                                !$this -> _badAt
                                || $this -> getConfig('preserve_css'))
                                && (
                                    !$this -> getConfig('discard_invalid_properties')
                                    || $valid
                                )
                            ) {
                                $this -> _addProperty(
                                    $this -> _atRule,
                                    $this -> selector,
                                    $property,
                                    $this -> value
                                );
                                $this -> _addToken(self::TOK_VALUE, $this -> value);
                            }
                            if (!$valid) {
                                if ($this -> getConfig('discard_invalid_properties')) {
                                    $this -> sheet -> log(
                                        'Removed invalid property: ' . $property,
                                        AP5L_Css_Sheet::MSG_WARN,
                                        $this -> line
                                    );
                                } else {
                                    $this -> sheet -> log(
                                        'Invalid property in '
                                        . strtoupper($this -> getConfig('css_level'))
                                        . ': '
                                        . $property,
                                        AP5L_Css_Sheet::MSG_WARN,
                                        $this -> line
                                    );
                                }
                            }
                            $property = '';
                            $wordList = array();
                            $this -> value = '';
                        }
                        if ($ch == '}') {
                            $this -> _addToken(self::TOK_SEL_END, $this -> selector);
                            $newState = self::STATE_SELECTOR;
                            $this -> _badAt = false;
                            $this -> selector = '';
                        }
                    } elseif (!$pn) {
                        $word .= $ch;
                        if (ctype_space($ch) && $word != '') {
                            $wordList[] = $word;
                            $word = '';
                        }
                    }
                }
                break;

                case self::STATE_STRING: {
                    /* Case in string */
                    if (
                        $this -> str_char == ')'
                        && ($ch == '"' || $ch == '\'')
                        && !self::isCharEscaped($buffer, $posn)
                    ) {
                        $nested = !$nested;
                    }
                    // ...and no non-escaped backslash at the previous position
                    $temp_add = $ch;
                    if (
                        $eol
                        && !($buffer[$posn - 1] == '\\'
                        && !self::isCharEscaped($buffer, $posn - 1))
                    ) {
                        $temp_add = "\\A ";
                        $this -> sheet -> log(
                            'Fixed incorrect newline in string',
                            AP5L_Css_Sheet::MSG_WARN,
                            $this -> line
                        );
                    }
                    if (
                        !($this -> str_char == ')'
                        && in_array($ch, self::$_whitespace)
                        && !$nested)
                    ) {
                        $this -> currString .= $temp_add;
                    }
                    if (
                        $ch == $this -> str_char
                        && !self::isCharEscaped($buffer, $posn)
                        && !$nested
                    ) {
                        $newState = $this -> _prevState;
                        if (
                            !preg_match($this -> _whitespaceRegex, $this -> currString)
                            && $property != 'content'
                        ) {
                            if ($this -> str_char == '"' || $this -> str_char == '\'') {
                                $this -> currString = substr($this -> currString, 1, -1);
                            } elseif (
                                strlen($this -> currString) > 3
                                && (
                                    $this -> currString[1] == '"'
                                    || $this -> currString[1] == '\''
                                ) /* () */
                            ) {
                                $this -> currString = $this -> currString[0]
                                    . substr($this -> currString, 2, -2)
                                    . substr($this -> currString, -1);
                            }
                        }
                        if ($this -> _prevState == self::STATE_VALUE) {
                            $word .= $this -> currString;
                        } elseif ($this -> _prevState == self::STATE_SELECTOR) {
                            $this -> selector .= $this -> currString;
                        }
                    }
                }
                break;

                case self::STATE_COMMENT: {
                    /* Case in-comment */
                    if ($ch == '*' && $nch == '/') {
                        $newState = $this -> _prevState;
                        ++$posn;
                        $this -> _addToken(self::TOK_COMMENT, $cur_comment);
                        $cur_comment = '';
                    } else {
                        $cur_comment .= $ch;
                    }
                }
                break;
            }
            if ($newState != $parseState) {
                $parseState = $newState;
            }
        }

        return $this -> sheet;
    }

    /**
     * Checks if a character is escaped.
     *
     * @param string The string containing the character to be checked.
     * @param integer Position of the character in the string.
     * @return boolean True if the character is escaped.
     */
    static function isCharEscaped(&$buffer, $pos) {
        if ($pos <= 0) {
            return false;
        }
        return !($buffer[$pos - 1] != '\\' || self::isCharEscaped($buffer, $pos - 1));
    }

    /**
     * Check a property value for the !important qualifier.
     *
     * @param string A CSS value
     * @return boolean
     */
    static function isImportant($value) {
        $clean = str_replace(self::$_whitespace, '', $value);
        return !strcasecmp(substr($clean, -10, 10), '!important');
    }
    
    /**
     * Check to see if a character is whitespace.
     * 
     * @param char The character to be tested.
     * @return boolean True if the character is whitespace.
     */
    static function isWhitespace($ch) {
        return in_array($ch, self::$_whitespace);
    }

    /**
     * Strips any !important qualifier from a value.
     *
     * @param string The property value.
     * @return string Property value without any !important qualifier.
     */
    static function removeImportant($value) {
        if ($important = self::isImportant($value)) {
            $posn = strripos($value, '!important');
            $value = trim(substr($value, 0, $posn));
        } else {
            $value = trim($value);
        }
        return array($value, $important);
    }

    /**
     * Checks if the next word in a string from pos is a CSS property
     *
     * @param string $istring
     * @param integer $pos
     * @return bool
     */
    function nextIsProperty($istring, $pos) {
        $istring = substr($istring, $pos, strlen($istring) - $pos);
        $pos = strpos($istring, ':');
        if ($pos === false) {
            return false;
        }
        $istring = strtolower(trim(substr($istring, 0, $pos)));
        if (AP5L_Css_Sheet::isProperty($istring)) {
            $this -> sheet -> log(
                'Added semicolon to the end of declaration',
                AP5L_Css_Sheet::MSG_WARN,
                $this -> line
            );
            return true;
        }
        return false;
    }

}

