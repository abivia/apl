<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Sheet.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Style sheet class
 *
 *  The intent of the code is to simplify the process of building and
 *  maintaining a complex style sheet. Ideally the code will encapsulate
 *  specific "quirks" of CSS implementation, allowing the user to focus more
 *  on functional requirements.
 *
 * @package AP5L
 * @subpackage Css
 */
class AP5L_Css_Sheet {
    /**
     * Rules for the sheet.
     *
     * @var array
     */
    protected $_rules = array();

    /**
     * Retrieve or create a reference to a rule.
     *
     * @param string Root rule selector.
     * @return AP5L_Css_Rule Reference to the rule object.
     */
    function &getRule($rootSelect) {
        if ($pos = strpos('.', $rootSelect) === false) {
            $element = $rootSelect;
            $className = '';
        } else {
            $element = substr($rootSelect, 0, $pos - 1);
            $className = substr($rootSelect, $pos + 1);
        }
        if ($element == '*') {
            $element = '*';
        }
        if (! isset($this -> _rules[$element])) {
            $this -> _rules[$element] = new AP5L_Css_Rule($element);
        }
        $rule = &$this -> _rules[$element];
        if ($className) {
            $rule = &$rule -> getClass($className);
        }
        return $rule;
    }

    /**
     * Get a copy of all rules.
     *
     * @return array The current rule set for the sheet.
     */
    function getRules() {
        return $this -> _rules;
    }

    /**
     * Log message levels.
     */
    const MSG_INFO = 0;
    const MSG_WARN = 1;
    const MSG_ERROR = 2;

    /**
     * Properties that can be decomposed into sub-properties.
     *
     * @var array
     */
    static protected $_compoundProperties = array(
        'background' => array(
            'background-color',
            'background-image',
            'background-repeat',
            'background-attachment',
            'background-position'
        ),
        'border' => array(
            'border-width',
            'border-style',
            'border-color',
        ),
        'border-color' => array(
            'border-top-color',
            'border-right-color',
            'border-bottom-color',
            'border-left-color',
        ),
        'border-bottom' => array(
            'border-width',
            'border-style',
            'border-bottom-color',
        ),
        'border-left' => array(
            'border-width',
            'border-style',
            'border-left-color',
        ),
        'border-right' => array(
            'border-width',
            'border-style',
            'border-right-color',
        ),
        'border-top' => array(
            'border-width',
            'border-style',
            'border-top-color',
        ),
        'border-width' => array(
            'border-top-width',
            'border-right-width',
            'border-bottom-width',
            'border-left-width',
        ),
        'font' => array(
            'font-style',
            'font-variant',
            'font-weight',
            'font-size',
            'line-height',
            'font-family',
        ),
        'list-style' => array(
            'list-style-type',
            'list-style-position',
            'list-style-image',
        ),
        'margin' => array(
            'margin-top',
            'margin-right',
            'margin-bottom',
            'margin-left',
        ),
        'outline' => array(
            'outline-style',
            'outline-color',
            'outline-width',
        ),
        'padding' => array(
            'padding-top',
            'padding-right',
            'padding-bottom',
            'padding-left',
        ),
    );

    /**
     * Message keys.
     *
     * @var array
     */
    protected $_messageKeys = array();

    /**
     * Message log.
     *
     * @var array
     */
    protected $_messages = array();

    /**
     * Properties that can be combined into aggregate properties.
     * Initialized on first instantiation.
     *
     * @var array
     */
    static protected $_parentProperties = null;

    /**
     * List of properties, with versions.
     */
    static protected $_propertyDefs = array();

    /**
     * All CSS units (CSS 3 units included)
     *
     * @var array
     */
    static protected $_unitDefs = array(
        '%',
        'cm',
        'em',
        'deg',
        'ex',
        'gd',
        'grad',
        'hz',
        'in',
        'khz',
        'mm',
        'ms',
        'pt',
        'pc',
        'px',
        'rad',
        'rem',
        's',
        'vh',
        'vm',
        'vw',
    );

     /**
     * Properties that need a value with units.
     *
     * @var array
     */
    static protected $_unitProperties = array (
        'azimuth',
        'background',
        //'background-attachment',
        'background-position',
        'border',
        'border-bottom',
        'border-bottom-width',
        'border-left',
        'border-left-width',
        'border-right',
        'border-right-width',
        'border-spacing',
        'border-top',
        'border-top-width',
        'border-width',
        'bottom',
        'font-size',
        'height',
        'left',
        'letter-spacing',
        'margin',
        'margin-bottom',
        'margin-left',
        'margin-top',
        'margin-right',
        'max-height',
        'max-width',
        'min-height',
        'min-width',
        'outline-width',
        'padding',
        'padding-bottom',
        'padding-left',
        'padding-right',
        'padding-top',
        'position',
        'right',
        'text-indent',
        'top',
        'width',
        'word-spacing',
    );

    /**
     * The CSS charset (@charset).
     *
     * @var string
     */
    public $charset = '';

    /**
     * List of @import URLs.
     *
     * @var array
     */
    public $imports = array();

    /**
     * Namespace for the sheet.
     *
     * @var string
     */
    public $namespace = '';

    /**
     * Parsed CSS rules.
     *
     * @var array
     */
    public $rules = array();

    /**
     * The raw token stream.
     * @var array
     */
    var $tokens = array();

    /**
     * Class constructor
     */
    function __construct() {
        if (! is_array(self::$_parentProperties)) {
            // Initialize the parent property mapping array.
            self::$_parentProperties = array();
            foreach (self::$_compoundProperties as $parent => $subs) {
                foreach ($subs as $sub) {
                    self::$_parentProperties[$sub] = $parent;
                }
            }
        }
    }

    /**
     * Return the accumulated message log.
     *
     * @return array
     */
    function getMessages() {
        $all = array();
        ksort($this -> _messages);
        foreach ($this -> _messages as $lineMsgs) {
            $all = array_merge($all, $lineMsgs);
        }
        return $all;
    }

    static function getProperty($propName) {
        if (empty(self::$_propertyDefs)) {
            $load = array(
                array('azimuth', 'aural', '2.0+', '.Angle', ),
                array('background', 'visual', '1.0+', '.Background', ),
                array(
                    'background-attachment', 'visual', '1.0+',
                    array('scroll', 'fixed'),
                ),
                array(
                    'background-color', 'visual', '1.0+',
                    array('<Color', 'transparent'),
                ),
                array('background-image', 'visual', '1.0+', '.GenericU0i', ),
                array('background-position', 'visual', '1.0+', '.BackgroundPosition', ),
                array(
                    'background-repeat', 'visual', '1.0+',
                    array('no-repeat', 'repeat', 'repeat-x', 'repeat-y'),
                ),
                array('border', 'visual', '1.0+', '.Border', ),
                array('border-bottom', 'visual', '1.0+', '.Border', ),
                array('border-bottom-color', 'visual', '2.0+', '.BorderColor', ),
                array('border-bottom-style', 'visual', '2.0+', '.BorderStyle', ),
                array('border-bottom-width', 'visual', '1.0+', '.BorderWidth', ),
                array('border-collapse', 'visual', '2.0+', array('collapse', 'inherit')),
                array('border-color', 'visual', '1.0+', '.BorderColor4', ),
                array('border-left', 'visual', '1.0+', '.Border', ),
                array('border-left-color', 'visual', '2.0+', '.BorderColor', ),
                array('border-left-style', 'visual', '2.0+', '.BorderStyle', ),
                array('border-left-width', 'visual', '1.0+', '.BorderWidth', ),
                array('border-right', 'visual', '1.0+', '.borderSide', ),
                array('border-right-color', 'visual', '2.0+', '.BorderColor', ),
                array('border-right-style', 'visual', '2.0+', '.BorderStyle', ),
                array('border-right-width', 'visual', '1.0+', '.BorderWidth', ),
                array('border-spacing', 'visual', '2.0+', '<Length'),
                array('border-style', 'visual', '1.0+', '.BorderStyle4', ),
                array('border-top', 'visual', '1.0+', '.Border', ),
                array('border-top-color', 'visual', '2.0+', '.BorderColor', ),
                array('border-top-style', 'visual', '2.0+', '.BorderStyle', ),
                array('border-top-width', 'visual', '1.0+', '.BorderWidth', ),
                array('border-width', 'visual', '1.0+', '.BorderWidth4', ),
                array('bottom', 'visual', '2.0+', '.GenericLpai'),
                array(
                    'caption-side', 'visual', '2.0+',
                    array('bottom', 'left', 'right', 'top', 'inherit')
                ),
                array(
                    'clear', 'visual', '1.0+',
                    array('none', 'left', 'right', 'both', 'inherit')
                ),
                array('clip', 'visual', '2.0+', '.Clip'),
                array('color', 'visual', '1.0+', '<Color', ),
                array('content', null, '2.0+', '.Content'),
                array('counter-increment', null, '2.0+',),
                array('counter-reset', null, '2.0+',),
                array('cue', 'aural', '2.0+', '.Cue2'),
                array('cue-after', 'aural', '2.0+', '.GenericU0i'),
                array('cue-before', 'aural', '2.0+', '.GenericU0i'),
                array('cursor', array('interactive', 'visual'), '2.0+', '.Cursor'),
                array('direction', 'visual', '2.0+', array('ltr', 'rtl', 'inherit'), ),
                array(
                    'display', 'visual', '1.0+',
                    array(
                        '1.0' => array('block', 'inline', 'list-item', 'none'),
                        '2.0+' => array(
                            'block',
                            'compact',
                            'inherit',
                            'inline',
                            'inline-table',
                            'list-item',
                            'marker',
                            'none',
                            'run-in',
                            'table',
                            'table-caption',
                            'table-cell',
                            'table-column',
                            'table-column-group',
                            'table-footer-group',
                            'table-header-group',
                            'table-row',
                            'table-row-group',
                        ),
                    ),
                ),
                array('elevation', 'aural', '2.0+', '.Elevation'),
                array('empty-cells', 'visual', '2.0+', array('hide', 'inherit', 'show', ), ),
                array(
                    'float', 'visual', '1.0+',
                    array('left', 'none', 'right'),
                ),
                array('font', 'visual', '1.0+', '.Font' ),
                array('font-family', 'visual', '1.0+', '.FontFamily' ),
                array('font-size', 'visual', '1.0+', '.FontSize', ),
                array('font-size-adjust', 'visual', '2.0', array('<Number', 'none', 'inherit'), ),
                array(
                    'font-stretch', 'visual', '2.0',
                    array(
                        'condensed', 'expanded', 'extra-condensed', 'extra-expanded',
                        'narrower', 'normal', 'semi-condensed', 'semi-expanded', 'ultra-condensed',
                        'ultra-expanded', 'wider', 'inherit',
                    ),
                ),
                array('font-style', 'visual', '1.0+', array('normal', 'italic', 'oblique') ),
                array('font-variant', 'visual', '1.0+', array('normal', 'small-caps'), ),
                array(
                    'font-weight', 'visual', '1.0+',
                    array(
                        'normal',
                        'bold',
                        'bolder',
                        'lighter',
                        '100',
                        '200',
                        '300',
                        '400',
                        '500',
                        '600',
                        '700',
                        '800',
                        '900'
                    )
                ),
                array('height', 'visual', '1.0+', '.GenericLpai', ),
                array('left', 'visual', '2.0+', '.GenericLpai',),
                array('letter-spacing', 'visual', '1.0+', '.GenericLni', ),
                array(
                    'line-height', 'visual', '1.0+',
                    array('normal', '<Number', '<Length', '<Percentage'),
                ),
                array('list-style', 'visual', '1.0+', '.ListStyle', ),
                array('list-style-image', 'visual', '1.0+', '.GenericU0i', ),
                array('list-style-position', 'visual', '1.0+', array('inside', 'outside'), ),
                array('list-style-type', 'visual', '.ListStyleType', ),
                array('margin', 'visual', '1.0+', '.GenericLpai4', ),
                array('margin-bottom', 'visual', '1.0+', '.GenericLpai', ),
                array('margin-left', 'visual', '1.0+', '.GenericLpai', ),
                array('margin-right', 'visual', '1.0+', '.GenericLpai', ),
                array('margin-top', 'visual', '1.0+', '.GenericLpai', ),
                array('marker-offset', 'visual', '2.0', '.GenericLai', ),
                array('marks', 'visual', '2.0', '.Marks'),
                array('max-height', 'visual', '2.0+', '.GenericLp0i', ),
                array('max-width', 'visual', '2.0+', '.GenericLp0i', ),
                array('min-height', 'visual', '2.0+', '.GenericLpi', ),
                array('min-width', 'visual', '2.0+', '.GenericLpi', ),
                array(
                    'orphans',
                    array('paged', 'visual'),
                    '2.0+',
                    array('<Integer', 'inherit'),
                ),
                array('outline', array('interactive', 'visual'), '2.0+',),
                array('outline-color', array('interactive', 'visual'), '2.0+', '.OutlineColor', ),
                array('outline-style', array('interactive', 'visual'), '2.0+', '.OutlineStyle', ),
                array('outline-width', array('interactive', 'visual'), '2.0+', '.BorderWidth', ),
                array(
                    'overflow', 'visual', '2.0+',
                    array('auto', 'hidden', 'scroll', 'visible', 'inherit'),
                ),
                array('padding', 'visual', '1.0+', '.GenericLpai4', ),
                array('padding-bottom', 'visual', '1.0+', '.GenericLpai', ),
                array('padding-left', 'visual', '1.0+', '.GenericLpai', ),
                array('padding-right', 'visual', '1.0+', '.GenericLpai', ),
                array('padding-top', 'visual', '1.0+', '.GenericLpai', ),
                array('page', 'visual', '2.0',),
                array(
                    'page-break-after', array('paged', 'visual'), '2.0+',
                    array('always', 'avoid', 'auto', 'left', 'right', 'inherit'),
                ),
                array(
                    'page-break-before', array('paged', 'visual'), '2.0+',
                    array('always', 'avoid', 'auto', 'left', 'right', 'inherit'),
                ),
                array(
                    'page-break-inside', array('paged', 'visual'), '2.0+',
                    array('avoid', 'auto', 'inherit'),
                ),
                array('pause', 'aural', '2.0+', '.Pause', ),
                array('pause-after', 'aural', '2.0+', '.Pause2', ),
                array('pause-before', 'aural', '2.0+', '.Pause', ),
                array('pitch', 'aural', '2.0+', '.Pitch', ),
                array('pitch-range', 'aural', '2.0+', '.GenericNi', ),
                array('play-during', 'aural', '2.0+',),
                array(
                    'position', 'visual', '2.0+', 
                    array('static', 'relative', 'absolute', 'fixed', 'inherit'), 
                ),
                array('quotes', 'visual', '2.0+', '.Quotes', ),
                array('richness', 'aural', '2.0+', '.GenericNi', ),
                array('right', 'visual', '2.0+', '.GenericLpai',),
                array('size', 'visual', '2.0', '.Size'),
                array(
                    'speak', 'aural', '2.0+',
                    array('none', 'normal', 'spell-out', 'inherit'),
                ),
                array(
                    'speak-header', 'visual', '2.0+',
                    array('always', 'once', 'inherit'),
                ),
                array(
                    'speak-numeral', 'aural', '2.0+',
                    array('continuous', 'digits', 'inherit'),
                ),
                array(
                    'speak-punctuation', 'aural', '2.0+',
                    array('code', 'none', 'inherit'),
                ),
                array('speech-rate', 'aural', '2.0+',),
                array('stress', 'aural', '2.0+', '.GenericNi', ),
                array(
                    'table-layout', 'visual', '2.0+',
                     array('auto', 'fixed', 'inherit'),
                ),
                array(
                    'text-align', 'visual', '1.0+', 
                    array(
                        '1.0' => array('left', 'right', 'center', 'justify'),
                        '2.0+' => '.TextAlign'
                    ), 
                ),
                array('text-decoration', 'visual', '1.0+', '.TextDecoration', ),
                array('text-indent', 'visual', '1.0+', array('<Length', '<Percentage') ),
                array('text-shadow', 'visual', '2.0',),
                array('text-transform', 'visual', '1.0+', array('capitalize', 'uppercase', 'lowercase', 'none'), ),
                array('top', 'visual', '1.0+', ),
                array('unicode-bidi', 'visual', '2.0+',),
                array(
                    'vertical-align', 'visual', '1.0+',
                    array(
                        'baseline',
                        'sub',
                        'super',
                        'top',
                        'text-top',
                        'middle',
                        'bottom',
                        'text-bottom',
                        '<Percentage'
                    ),
                ),
                array('visibility', 'visual', '2.0+',),
                array('voice-family', 'aural', '2.0+',),
                array('volume', 'aural', '2.0+',),
                array('white-space', 'visual', '1.0+', array('normal', 'pre', 'nowrap'), ),
                array('widows', array('paged', 'visual'), '2.0+',),
                array('width', 'visual', '1.0+', '.GenericLpai', ),
                array('word-spacing', 'visual', '1.0+', '.GenericLni', ),
                array('z-index', 'visual', '2.0+',),
            );
            foreach ($load as $args) {
                self::$_propertyDefs[$args[0]] = call_user_func_array(
                    array('AP5L_Css_PropertyDef', 'factory'), $args
                );
            }
        }
        return isset(self::$_propertyDefs[$propName]) ? self::$_propertyDefs[$propName] : false;
    }

    /**
     * Checks if a property name is valid.
     *
     * @param string The property name.
     * @return bool
     */
    function isProperty($propName) {
        if (!isset(self::$_propertyDefs[$propName])) {
            return false;
        }
        return self::$_propertyDefs[$propName] -> versionCheck($this -> cssVersion);
    }

    /**
     * Add a message to the message log.
     *
     * @param string Message text.
     * @param string Message type (one of the MSG_ constants).
     * @param integer Optional line number.
     */
    function log($message, $type, $line) {
        $line = intval($line);
        $key = md5($line . $message . $type);
        $add = array('message' => $message, 'type' => $type);
        if (!isset($this -> _messageKeys[$key])) {
            $this -> _messageKeys[$key] = true;
            $this -> _messages[$line][] = $add;
        }
    }

    /**
     * Resets the message log.
     */
    function logClear() {
        $this -> _messageKeys = array();
        $this -> _messages = array();
    }

    /**
     * Convert all styles into a normal form by eliminating compound values.
     *
     * @return unknown_type
     */
    function normalize() {
        foreach ($this -> rules as &$atPlane) {
            foreach ($atPlane as $selector => &$pairs) {
                foreach ($pairs as $propName => $value) {
                }
            }
        }
    }

    /**
     * Verify that the CSS rules are valid.
     *
     * @return unknown_type
     */
    function validate() {
        foreach ($this -> rules as &$atRules) {
            foreach ($atRules as $selector => &$pairs) {
                foreach ($pairs as $propName => &$value) {
                }
            }
        }
    }

}