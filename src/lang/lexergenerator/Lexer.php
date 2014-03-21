<?php
/**
 * AP5L_Lang_LexerGenerator_Lexer, tokenizer for the lexer generator.
 *
 * This lexer generator translates a file in a format similar to
 * re2c({@link http://re2c.org}) and translates it into a PHP 5-based lexer
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category php
 * @package PHP_LexerGenerator
 * @author Gregory Beaver <cellog@php.net>
 * @copyright 2006 Gregory Beaver
 * @license http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version $Id: Lexer.php 246683 2007-11-22 04:43:52Z instance $
 */

/**
 * Token scanner for plex files.
 *
 * This scanner detects comments beginning with "/*!lex2php" (this can be
 * changed by setting an option) and then returns their components
 * (processing instructions, patterns, strings action code, and regexes)
 *
 * Based on PHP_LexerGenerator by Gregory Beaver <cellog@php.net>,
 * copyright 2006 Gregory Beaver.
 *
 * @package PHP_LexerGenerator
 * @author Gregory Beaver <cellog@php.net>
 * @copyright 2006 Gregory Beaver
 * @license http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version @package_version@
 */
class AP5L_Lang_LexerGenerator_Lexer {
    const REGEX_SYMBOL = '/\G[a-zA-Z_][a-zA-Z0-9_]*/';
    const REGEX_TO_EOL = "/\G[^\n]+/";
    const REGEX_WORD_LC = '/\G%([a-z]+)/';
    const WORD_PI = '/\G%([a-zA-Z_]+)/';

    /**
     * The buffer to tokenize.
     *
     * @var string
     */
    protected $_data;

    /**
     * Current scan position in buffer.
     *
     * @var int
     */
    protected $_scan;

    /**
     * Scanner state name.
     *
     * @var string
     */
    protected $_state;

    /**
     * Token type codes.
     *
     * @var array
     */
    protected $_tokenTypes = array(
        'code' => 1,
        'commentend' => 2,
        'commentstart' => 3,
        'pattern' => 4,
        'phpcode' => 5,
        'pi' => 6,
        'quote' => 7,
        'singlequote' => 8,
        'subpattern' => 9,
    );

    /**
     * The string used to trigger the scanner.
     *
     * @var string
     */
    protected $_trigger = "/*!lex2php\n";

    /**
     * Current line number in input.
     *
     * @var int
     */
    public $line;

    /**
     * Error list.
     *
     * @var array
     */
    public $errors = array();

    /**
     * Type of the current token.
     *
     * @var int
     */
    public $token;

    /**
     * Content of current token.
     *
     * @var string
     */
    public $value;

    /**
     * Prepare scanning
     *
     * @param string The data to be tokenized. Optional.
     * @param array Options. Defined option is 'tokentypes', which allows
     * the application to provide new token codes.
     * @return void
     */
    function __construct($data = '', $options = array()) {
        $this -> setOptions($options);
        $this -> setData($data);
    }

    /**
     * Output an error message.
     *
     * @param string
     */
    protected function _error($msg) {
        $this -> errors[] = 'Line ' . $this -> line . ': ' . $msg;
    }

    /**
     * Initial scanning _state lexer.
     *
     * @return boolean
     */
    protected function _lexStart() {
        if ($this -> _scan >= strlen($this -> _data)) {
            return false;
        }
        $a = strpos($this -> _data, $this -> _trigger, $this -> _scan);
        if ($a === false) {
            $this -> value = substr($this -> _data, $this -> _scan);
            $this -> _scan = strlen($this -> _data);
            $this -> token = $this -> _tokenTypes['phpcode'];
            return true;
        }
        if ($a > $this -> _scan) {
            $this -> value = substr($this -> _data, $this -> _scan, $a - $this -> _scan);
            $this -> _scan = $a;
            $this -> token = $this -> _tokenTypes['phpcode'];
            return true;
        }
        $this -> value = $this -> _trigger;
        $this -> _scan += strlen($this -> _trigger);
        $this -> token = $this -> _tokenTypes['commentstart'];
        $this -> _state = 'Declare';
        return true;
    }

    /**
     * Lexer for top-level scanning state after the initial declaration comment.
     *
     * @return boolean
     */
    protected function _lexStartNonDeclare() {
        if ($this -> _scan >= strlen($this -> _data)) {
            return false;
        }
        $a = strpos($this -> _data, '/*!lex2php' . "\n", $this -> _scan);
        if ($a === false) {
            $this -> value = substr($this -> _data, $this -> _scan);
            $this -> _scan = strlen($this -> _data);
            $this -> token = $this -> _tokenTypes['phpcode'];
            return true;
        }
        if ($a > $this -> _scan) {
            $this -> value = substr($this -> _data, $this -> _scan, $a - $this -> _scan);
            $this -> _scan = $a;
            $this -> token = $this -> _tokenTypes['phpcode'];
            return true;
        }
        $this -> value = '/*!lex2php' . "\n";
        $this -> _scan += 11; // strlen("/*lex2php\n")
        $this -> token = $this -> _tokenTypes['commentstart'];
        $this -> _state = 'Rule';
        return true;
    }

    /**
     * Lexer for declaration comment state.
     *
     * @return boolean
     */
    protected function _lexDeclare() {
        while (true) {
            $this -> _skipWhitespaceEol();
            if (
                $this -> _scan + 1 >= strlen($this -> _data)
                || $this -> _data[$this -> _scan] != '/'
                || $this -> _data[$this -> _scan + 1] != '/'
            ) {
                break;
            }
            // Skip single-line comment
            while (
                $this -> _scan < strlen($this -> _data)
                && $this -> _data[$this -> _scan] != "\n"
            ) {
                ++$this -> _scan;
            }
        }
        if ($this -> _data[$this -> _scan] == '*' && $this -> _data[$this -> _scan + 1] == '/') {
            $this -> _state = 'StartNonDeclare';
            $this -> value = '*/';
            $this -> _scan += 2;
            $this -> token = $this -> _tokenTypes['commentend'];
            return true;
        }
        if (preg_match(self::REGEX_WORD_LC, $this -> _data, $token, null, $this -> _scan)) {
            $this -> value = $token[1];
            $this -> _scan += strlen($token[1]) + 1;
            $this -> _state = 'DeclarePI';
            $this -> token = $this -> _tokenTypes['pi'];
            return true;
        }
        if (preg_match(self::REGEX_SYMBOL, $this -> _data, $token, null, $this -> _scan)) {
            $this -> value = $token[0];
            $this -> token = $this -> _tokenTypes['pattern'];
            $this -> _scan += strlen($token[0]);
            $this -> _state = 'DeclareEquals';
            return true;
        }
        $this -> _error('expecting declaration of sub-patterns');
        return false;
    }

    /**
     * Lexer for processor instructions within declaration comment.
     *
     * @return boolean
     */
    protected function _lexDeclarePI() {
        $this -> _skipWhitespace();
        if ($this -> _data[$this -> _scan] == "\n") {
            ++$this -> _scan;
            $this -> _state = 'Declare';
            ++$this -> line;
            return $this -> _lexDeclare();
        }
        if ($this -> _data[$this -> _scan] == '{') {
            return $this -> _lexCode();
        }
        if (!preg_match(self::REGEX_TO_EOL, $this -> _data, $token, null, $this -> _scan)) {
            $this -> _error('Unexpected end of file');
            return false;
        }
        $this -> value = $token[0];
        $this -> _scan += strlen($this -> value);
        $this -> token = $this -> _tokenTypes['subpattern'];
        return true;
    }

    /**
     * Lexer for processor instructions inside rule comments.
     *
     * @return boolean
     */
    protected function _lexDeclarePIRule() {
        $this -> _skipWhitespace();
        if ($this -> _data[$this -> _scan] == "\n") {
            ++$this -> _scan;
            $this -> _state = 'Rule';
            ++$this -> line;
            return $this -> _lexRule();
        }
        if ($this -> _data[$this -> _scan] == '{') {
            return $this -> _lexCode();
        }
        if (!preg_match(self::REGEX_TO_EOL, $this -> _data, $token, null, $this -> _scan)) {
            $this -> _error('Unexpected end of file.');
            return false;
        }
        $this -> value = $token[0];
        $this -> _scan += strlen($this -> value);
        $this -> token = $this -> _tokenTypes['subpattern'];
        return true;
    }

    /**
     * Lexer for the state representing scanning between a pattern and the "=" sign.
     *
     * @return boolean
     */
    protected function _lexDeclareEquals() {
        $this -> _skipWhitespace();
        if ($this -> _scan >= strlen($this -> _data)) {
            $this -> _error('Unexpected end of input, expecting "=" for sub-pattern declaration.');
        }
        if ($this -> _data[$this -> _scan] != '=') {
            $this -> _error('Expecting "=" for sub-pattern declaration.');
            return false;
        }
        ++$this -> _scan;
        $this -> _state = 'DeclareRightside';
        $this -> _skipWhitespace();
        if ($this -> _scan >= strlen($this -> _data)) {
            $this -> _error('unexpected end of file, expecting right side of sub-pattern declaration');
            return false;
        }
        return $this -> _lexDeclareRightside();
    }

    /**
     * Lexer for the right side of a pattern, detects quotes or regexes.
     * @return boolean
     */
    protected function _lexDeclareRightside() {
        if ($this -> _data[$this -> _scan] == "\n") {
            $this -> _state = 'lexDeclare';
            ++$this -> _scan;
            ++$this -> line;
            return $this -> _lexDeclare();
        }
        if ($this -> _data[$this -> _scan] == '"') {
            return $this -> _lexQuote();
        }
        if ($this -> _data[$this -> _scan] == '\'') {
            return $this -> _lexQuote('\'');
        }
        $this -> _skipWhitespace();
        // match a pattern
        $test = $this -> _data[$this -> _scan];
        $token = $this -> _scan + 1;
        $a = 0;
        do {
            if ($a++) {
                ++$token;
            }
            $token = strpos($this -> _data, $test, $token);
        } while ($token !== false &&($this -> _data[$token - 1] == '\\'
                 && $this -> _data[$token - 2] != '\\'));
        if ($token === false) {
            $this -> _error('Unterminated regex pattern(started with "' . $test . '".');
            return false;
        }
        if (substr_count($this -> _data, "\n", $this -> _scan, $token - $this -> _scan)) {
            $this -> _error('Regex pattern extends over multiple lines.');
            return false;
        }
        $this -> value = substr($this -> _data, $this -> _scan + 1, $token - $this -> _scan - 1);
        /*
         * Unescape the regex marker. We will re-escape when creating the
         * final regex.
         */
        $this -> value = str_replace('\\' . $test, $test, $this -> value);
        $this -> _scan = $token + 1;
        $this -> token = $this -> _tokenTypes['subpattern'];
        return true;
    }

    /**
     * Lexer for quoted literals.
     *
     * @return boolean
     */
    protected function _lexQuote($quote = '"') {
        $token = $this -> _scan + 1;
        $a = 0;
        do {
            if ($a++) {
                ++$token;
            }
            $token = strpos($this -> _data, $quote, $token);
        } while (
            $token !== false
            && $token < strlen($this -> _data)
            && (
                $this -> _data[$token - 1] == '\\'
                && $this -> _data[$token - 2] != '\\'
            )
        );
        if ($token === false) {
            $this -> _error('Unterminated quote.');
            return false;
        }
        if (substr_count($this -> _data, "\n", $this -> _scan, $token - $this -> _scan)) {
            $this -> _error('Quote extends over multiple lines.');
            return false;
        }
        $this -> value = substr($this -> _data, $this -> _scan + 1, $token - $this -> _scan - 1);
        $this -> value = str_replace('\\'.$quote, $quote, $this -> value);
        $this -> value = str_replace('\\\\', '\\', $this -> value);
        $this -> _scan = $token + 1;
        if ($quote == '\'') {
            $this -> token = $this -> _tokenTypes['singlequote'];
        } else {
            $this -> token = $this -> _tokenTypes['quote'];
        }
        return true;
    }

    /**
     * Lexer for rules.
     *
     * @return boolean
     */
    protected function _lexRule() {
        while (
            $this -> _scan < strlen($this -> _data)
            && (
                $this -> _data[$this -> _scan] == ' '
                || $this -> _data[$this -> _scan] == "\t"
                || $this -> _data[$this -> _scan] == "\n"
            ) || (
                $this -> _scan < strlen($this -> _data) - 1
                && $this -> _data[$this -> _scan] == '/'
                && $this -> _data[$this -> _scan + 1] == '/'
            )
        ) {
            if (
                $this -> _data[$this -> _scan] == '/' && $this -> _data[$this -> _scan + 1] == '/'
            ) {
                // Skip single line comments
                $nextNewline = strpos($this -> _data, "\n", $this -> _scan) + 1;
                if ($nextNewline) {
                    $this -> _scan = $nextNewline;
                } else {
                    $this -> _scan = sizeof($this -> _data);
                }
                ++$this -> line;
            } else {
                if ($this -> _data[$this -> _scan] == "\n") {
                    ++$this -> line;
                }
                ++$this -> _scan; // skip all whitespace
            }
        }
        if ($this -> _scan >= strlen($this -> _data)) {
            $this -> _error('Unexpected end of input, expecting rule declaration.');
        }
        if (
            $this -> _data[$this -> _scan] == '*' && $this -> _data[$this -> _scan + 1] == '/'
        ) {
            $this -> _state = 'StartNonDeclare';
            $this -> value = '*/';
            $this -> _scan += 2;
            $this -> token = $this -> _tokenTypes['commentend'];
            return true;
        }
        if ($this -> _data[$this -> _scan] == '\'') {
            return $this -> _lexQuote('\'');
        }
        if (preg_match(self::WORD_PI, $this -> _data, $token, null, $this -> _scan)) {
            $this -> value = $token[1];
            $this -> _scan += strlen($token[1]) + 1;
            $this -> _state = 'DeclarePIRule';
            $this -> token = $this -> _tokenTypes['pi'];
            return true;
        }
        if ($this -> _data[$this -> _scan] == "{") {
            return $this -> _lexCode();
        }
        if ($this -> _data[$this -> _scan] == '"') {
            return $this -> _lexQuote();
        }
        if (preg_match(self::REGEX_SYMBOL, $this -> _data, $token, null, $this -> _scan)) {
            $this -> value = $token[0];
            $this -> _scan += strlen($token[0]);
            $this -> token = $this -> _tokenTypes['subpattern'];
            return true;
        } else {
            $this -> _error('Expecting token rule (quotes or sub-patterns).');
            return false;
        }
    }

    /**
     * Lexer for php code blocks.
     *
     * @return boolean
     */
    protected function _lexCode() {
        $cp = $this -> _scan + 1;
        for (
            $level = 1;
            $cp < strlen($this -> _data)
            && ($level > 1 || $this -> _data[$cp] != '}');
            $cp++
        ) {
            if ($this -> _data[$cp] == '{') {
                ++$level;
            } elseif ($this -> _data[$cp] == '}') {
                --$level;
            } elseif ($this -> _data[$cp] == '/' && $this -> _data[$cp + 1] == '/') {
                /* Skip C++ style comments */
                $cp += 2;
                $z = strpos($this -> _data, "\n", $cp);
                if ($z === false) {
                    $cp = strlen($this -> _data);
                    break;
                }
                $cp = $z;
            } elseif ($this -> _data[$cp] == "'" || $this -> _data[$cp] == '"') {
                // String character literals
                $startchar = $this -> _data[$cp];
                $prevc = 0;
                for (
                    $cp++;
                    $cp < strlen($this -> _data)
                    && ($this -> _data[$cp] != $startchar || $prevc === '\\');
                    ++$cp
                ) {
                    if ($prevc === '\\') {
                        $prevc = 0;
                    } else {
                        $prevc = $this -> _data[$cp];
                    }
                }
            }
        }
        if ($cp >= strlen($this -> _data)) {
            $this -> _error('PHP code starting on this line is not terminated before the end of the file.');
            return false;
        } else {
            $this -> value = substr($this -> _data, $this -> _scan + 1, $cp - $this -> _scan - 1);
            $this -> token = $this -> _tokenTypes['code'];
            $this -> _scan = $cp + 1;
            return true;
        }
    }

    /**
     * Skip whitespace characters.
     *
     * @return void
     */
    protected function _skipWhitespace() {
        while (
            $this -> _scan < strlen($this -> _data)
            && (
                $this -> _data[$this -> _scan] == ' '
                || $this -> _data[$this -> _scan] == "\t"
            )
        ) {
            ++$this -> _scan; // skip whitespace
        }
    }

    /**
     * Skip whitespace and EOL characters.
     *
     * @return void
     */
    protected function _skipWhitespaceEol() {
        while (
            $this -> _scan < strlen($this -> _data)
            && (
                $this -> _data[$this -> _scan] == ' '
                || $this -> _data[$this -> _scan] == "\t"
                || $this -> _data[$this -> _scan] == "\n"
            )
        ) {
            if ($this -> _data[$this -> _scan] == "\n") {
                ++$this -> line;
            }
            ++$this -> _scan; // skip whitespace
        }
    }

    /**
     * Primary scanner.
     *
     * In addition to lexing, this properly increments the line number of lexing.
     * This calls the proper sub-lexer based on the parser state.
     *
     * @param unknown_type $parser
     * @return unknown
     */
    public function advance($parser) {
        if ($this -> _scan >= strlen($this -> _data)) {
            return false;
        }
        if ($this -> {'_lex' . $this -> _state}()) {
            $this -> line += substr_count($this -> value, "\n");
            return true;
        }
        return false;
    }

    /**
     * Set the data to be tokenized.
     *
     * This resets the scanner.
     *
     * @param string The data to be tokenized.
     * @return void
     */
    function setData($data) {
        $this -> _data = str_replace("\r\n", "\n", $data);
        $this -> _scan = 0;
        $this -> line = 1;
        $this -> _state = 'Start';
    }

    /**
     * Set options.
     *
     * @param array Options. Defined option is 'tokentypes', which allows
     * the application to provide new token type codes.
     * @return void
     */
    function setOptions($options) {
        if (isset($options['tokentypes'])) {
            $this -> _tokenTypes = array_merge(
                $this -> _tokenTypes, $options['tokentypes']
            );
        }
        if (isset($options['trigger'])) {
            $this -> _trigger = $options['trigger'];
        }
    }

}
