<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: Field.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Common elements for all field classes.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Field extends AP5L_Php_InflexibleObject {
    /**
     * @var array Custom events, indexed by event name.
     */
    protected $_events = array();
    /**
     * @var int Label generation mode: -1 = no label; 0 = empty label cell; 1 =
     * generate label in cell.
     */
    protected $_labelMode = 1;
    /**
     * @var string Name of the field.
     */
    protected $_name;
    /**
     * @var Set if this field's value is "persistent", i.e. saved on completion.
     */
    protected $_persistent;
    /**
     * @var Set if a value must be provided for this field.
     */
    protected $_required;
    /**
     * @var int Sort group for field ordering. Default is 100.
     */
    protected $_sortGroup;
    /**
     * @var boolean Set if this field is invariant on clear operations
     */
    protected $_static;
    /**
     * @var string List of styles field participates in (*=all)
     */
    protected $_styles = array();
    /**
     * @var string Field type; implemented by sub-classes. One of: check, email,
     * header, hidden, label, radio, select, setpass, pass, text, textarea,
     * verifier.
     */
    protected $_type;
    /**
     * @var boolean Set if this field is visible on the form.
     */
    protected $_visible = true;
    /**
     * @var string|array|false Class to use when generating this field. If this
     * value is an array and the field appears in an array, then the array index
     * is used to select an array element. If the value is false, then no value
     * (or a parent value) is used.
     */
    protected $classes = false;
    /**
     * @var int Field position; used for field sorting
     */
    public $fieldPosition;
    /**
     * @var string Text to use in a user help display
     */
    public $helpText;
    /**
     * @var string|false Class to use when generating a label for this field,
     * false if no value (or a parent value) to be used.
     */
    public $labelClass = false;
    /**
     * @var int Index where values are stored, when this field is in a scalar
     * array container.
     */
    public $valueIndex;

    /**
     * Form field constructor.
     *
     * @param string The field type.
     * @param string The field name.
     */
    function __construct($type, $name) {
        $this -> _name = $name;
        $this -> _type = $type;
        $this -> _persistent = $this -> persistentDefault();
        $this -> _required = $this -> requiredDefault();
        $this -> _static = $this -> staticDefault();
        $this -> _visible = $this -> visibleDefault();
    }

    /**
     * Parse a flag string into an array of settings
     *
     * @param string A list of flags selected from I=invisible, O=Optional,
     * P=persistent, R=required, S=static, T=temporary, V=visible.
     * @return array Array of booleans with indexes 'p', 'r', 's', or 'v' for
     * each affected flag.
     */
    function _parseFlags($rule) {
        $rule = strtolower($rule);
        $flags = array();
        for ($ind = 0; $ind < strlen($rule); ++$ind) {
            switch ($rule[$ind]) {
                case 'i': {
                    $flags['r'] = false;
                    $flags['v'] = false;
                } break;

                case 'o': {
                    $flags['o'] = false;
                } break;

                case 'p': {
                    $flags['p'] = true;
                } break;

                case 'r': {
                    $flags['r'] = true;
                    $flags['v'] = true;
                } break;

                case 's': {
                    $flags['s'] = true;
                } break;

                case 't': {
                    $flags['p'] = false;
                } break;

                case 'v': {
                    $flags['r'] = false;
                    $flags['v'] = true;
                } break;

            }
        }
        return $flags;
    }

    /**
     * Manufacture a AP5L_Forms_Rapid field, see derived classes for additional
     * parameters.
     *
     * @param string The type of the field to create.
     * @param string The name of the parameter.
     *
     */
    static function &factory($type, $name) {
        $args = func_get_args();
        $fieldType = $args[0];
        if (in_array($fieldType, AP5L_Forms_Field_Scalar::$typeList)) {
            $object = &AP5L_Forms_Field_Scalar::factory($args);
        } elseif (in_array($fieldType, AP5L_Forms_Field_Container::$typeList)) {
            $object = &AP5L_Forms_Field_Container::factory($args);
        } elseif (in_array($fieldType, AP5L_Forms_Field_ScalarArray::$typeList)) {
            $object = &AP5L_Forms_Field_ScalarArray::factory($args);
        } else {
            throw new AP5L_Forms_Exception('Don\'t know how to create field of type ' . $fieldType);
        }
        return $object;
    }

    /**
     * Get a field's class.
     * @return string|array For simple classes, the class name to use when
     * generating this field; For array classes, an array of classes to cycle
     * through by array index.
     */
    function getClass() {
        return $this -> classes;
    }

    /**
     * Get the event handlers.
     *
     * @return array Event definitions, indexed by event name.
     */
    function getEvents() {
        return $this -> _events;
    }

    /**
     * Return the label mode.
     * @return int The field's labelling mode (-1, 0, 1).
     */
    function getLabelMode() {
        return $this -> _labelMode;
    }

    /**
     * Get the name of the field.
     */
    function getName() {
        return $this -> _name;
    }

    /**
     * Get the sort group for this field.
     *
     * @see setSortGroup
     */
    function getSortGroup() {
        return $this -> _sortGroup;
    }

    /**
     * Return the field type.
     * @return string The field type.
     */
    function getType() {
        return $this -> _type;
    }

    /**
     * Determine if the field particpates in a phase.
     *
     * The "phase" is a concept for multi-part scalar fields. In other field
     * classes, it's not present.
     *
     * @param int The phase being queried.
     * @return boolean Always true only if phase is zero, unless overidden.
     */
    function hasPhase($phase) {
        return $phase == 0;
    }
    /**
     * Determine if the field should be persistent in a database
     *
     * @return boolean True if the data in this field should be saved.
     */
    function isPersistent() {
        return $this -> _persistent;
    }

    /**
     * Determine if a value for this field is mandatory.
     *
     * @return boolean True if this field must have a value.
     */
    function isRequired() {
        return $this -> _required;
    }

    /**
     * Determine if this field has a fixed value.
     *
     * @return boolean True if this field is fixed.
     */
    function isStatic() {
        return $this -> _static;
    }

    /**
     * Determine if this field is visible to the user.
     *
     * @return boolean True if this field is visible.
     */
    function isVisible() {
        return $this -> _visible;
    }

    /**
     * Get the default value for the persistent flag for this field type.
     *
     * This function should be overridden when required.
     */
    protected function persistentDefault() {
        return true;
    }

    /**
     * Get the default value for the required flag for this field type.
     *
     * This function should be overridden when required.
     */
    protected function requiredDefault() {
        return false;
    }

    /**
     * Set a field's class.
     * @param string|array For simple classes, the class name to use when
     * generating this field; For array classes, an array of classes to cycle
     * through by array index.
     */
    function setClass($class) {
        $this -> classes = $class;
    }

    /**
     * Set an event handler.
     *
     * @param string The event name.
     * @param string The contents of the event trigger.
     */
    function setEvent($event, $data) {
        $this -> _events[$event] = $data;
    }

    /**
     * Define field visibility, persistence, etc. rules based on a flag string.
     *
     * @param string A list of flags selected from I=invisible,
     * O=Optional, P=persistent, R=required, S=static, T=temporary, V=visible.
     */
    function setFlags($rule) {
        $flags = $this -> _parseFlags($rule);
        if (isset($flags['p'])) {
            $this -> setPersistent($flags['p']);
        }
        if (isset($flags['r'])) {
            $this -> setRequired($flags['r']);
        }
        if (isset($flags['s'])) {
            $this -> setStatic($flags['s']);
        }
        if (isset($flags['v'])) {
            $this -> setVisible($flags['v']);
        }
    }

    /**
     * Set help text for this field.
     * @param string The text to be displayed as field help; this is HTML
     * escaped. To insert HTML, use the helpText property directly.
     */
    function setHelp($text) {
        $this -> helpText = htmlentities($text);
    }

    /**
     * Set the label mode.
     * @param int -1=no label; 0=empty label cell; 1= use label
     */
    function setLabelMode($mode) {
        $this -> _labelMode = $mode;
    }

    /**
     * Set field persistence state.
     *
     * @param boolean If set, the field is persistent, otherwise the field is
     * temporary.
     */
    function setPersistent($flag) {
        $this -> _persistent = $flag;
    }

    /**
     * Set field required state.
     *
     * @param boolean If set, the field is required, otherwise the field is
     * optional.
     */
    function setRequired($flag) {
        $this -> _required = $flag;
    }

    /**
     * Set the sort group for this field.
     *
     * Fields can be added to multiple points in the form by using sort groups.
     * The default value is 100. fields with a lower group will be placed before
     * the intrinsic fields, fields with a higher value will be placed after the
     * intrinsics. The application can define an arbitrary number of groups,
     * allowing for easy control over form layout. Within a group, fields are
     * displayed in the order that they were created.
     */
    function setSortGroup($sort) {
        $this -> _sortGroup = $sort;
    }

    /**
     * Set field static state.
     *
     * @param boolean If set, the field is static, otherwise the field is
     * variable.
     */
    function setStatic($flag) {
        $this -> _static = $flag;
    }

    /**
     * Set visibility, persistence and data required attributes based on a
     * predefined form style.
     *
     * @param string Name of the style to use.
     */
    function setStyle($style) {
        if (isset($this -> _styles['*'])) {
            $this -> _persistent = isset($this -> _styles['*']['p'])
                ? $this -> _styles['*']['p'] : $this -> persistentDefault();
            $this -> _required = isset($this -> _styles['*']['r'])
                ? $this -> _styles['*']['r'] : $this -> requiredDefault();
            $this -> _static = isset($this -> _styles['*']['s'])
                ? $this -> _styles['*']['s'] : $this -> staticDefault();
            $this -> _visible = isset($this -> _styles['*']['v'])
                ? $this -> _styles['*']['v'] : $this -> visibleDefault();
        } else {
            $this -> _persistent = $this -> persistentDefault();
            $this -> _required = $this -> requiredDefault();
            $this -> _static = $this -> staticDefault();
            $this -> _visible = $this -> visibleDefault();
        }
        foreach ($this -> _styles as $tag => $flags) {
            if ($tag == $style || ($tag[0] == '~' && substr($tag, 1) != $style)) {
                if (isset($flags['p'])) {
                    $this -> _persistent = $flags['p'];
                }
                if (isset($flags['r'])) {
                    $this -> _required = $flags['r'];
                }
                if (isset($flags['s'])) {
                    $this -> _static = $flags['s'];
                }
                if (isset($flags['v'])) {
                    $this -> _visible = $flags['v'];
                }
            }
        }
    }

    /**
     * Define field  visibility, persistence, etc. rules based on style.
     *
     * @param string A visibility expression in one of these forms: *=flags
     * [~]type[,[~]type][,...]=flags; where flags are I=invisible,
     * O=Optional, P=persistent, R=required, S=static, T=temporary, V=visible.
     * The type is * or a preset form type.
     */
    function setStyleRules($rules) {
        if (! $rules) {
            $rules = '*';
        }
        $exprs = explode(' ', $rules);
        $this -> _styles = array();
        foreach ($exprs as $rule) {
            if ($rule == '') continue;
            $posn = strpos($rule, '=');
            if ($posn === false) continue;
            $tag = substr($rule, 0, $posn);
            $tags = explode(',', $tag);
            $flags = $this -> _parseFlags(substr($rule, $posn + 1));
            foreach ($tags as $tag) {
                $this -> _styles[$tag] = $flags;
            }
        }
    }

    /**
     * Set field visibility.
     *
     * @param boolean If set, the field is visible, otherwise the field is
     * hidden.
     */
    function setVisible($flag) {
        $this -> _visible = $flag;
    }

    /**
     * Get the default value for the static flag for this field type.
     *
     * This function should be overridden when required.
     */
    protected function staticDefault() {
        return false;
    }

    /**
     * Get the default value for the visible flag for this field type.
     *
     * This function should be overridden when required.
     */
    protected function visibleDefault() {
        return true;
    }

}
