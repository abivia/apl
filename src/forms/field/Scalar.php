<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: Scalar.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A field on a AP5L_Forms_Rapid
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Field_Scalar extends AP5L_Forms_Field {
    /**
     * @var array Choices for select and group fields
     */
    private $_choices = array();

    /**
     * @var int|array Field size on form; integer. If textarea, field size array
     * (x, y); if setpass, acceptable length (min, max) inclusive.
     */
    private $_displaySize;

    /**
     * @var int Maximum size of field.
     */
    private $_maxLength;

    /**
     * @var string Current field value.
     */
    private $_value = '';

    /**
     * @var boolean Data error flag; set when data provided but not valid
     */
    public $isError;

    /**
     * @var boolean Set if data has passed validation.
     */
    public $isValid;

    /**
     * @var array List of types acceptable to this class
     */
    static $typeList = array(
        'button', 'check', 'email', 'header', 'hidden', 'label', 'radio',
        'pass', 'select', 'setpass', 'submit', 'text', 'textarea', 'verifier'
        );

    /**
     * Form field constructor.
     *
     * Notes on field types: Button is a non-submit button; the field value will
     * be used as the "onclick" event in HTML renderings. Submit is s submit
     * style button; any value provided will be used in the value element.
     *
     * @param string The field type (text, email, pass, hidden, textarea,
     * select, radio, check, verifier).
     * @param string The field name.
     * @param int The maximum length of the field.
     * @param int|array The display size (array(cols, rows) if type is
     * textarea).
     * @param int Sorting group for arranging fields on the form.
     */
    function __construct($type, $name, $maxLen = 0, $size = 0, $sort = 100) {
        parent::__construct($type, $name);
        $this -> _displaySize = $size;
        $this -> _maxLength = $maxLen;
        $this -> _sortGroup = $sort;
        $this -> isValid = true;
    }

    /**
     * Add a choice, if this is a multiple selection field.
     *
     * @param string The text to be added as a choice.
     * @param string The value to be returned with this choice.
     * @param int Optional insert position (default is to end of list)
     * @throws AP5L_Forms_Exception if the field isn't multiple selection capable.
     */
    function addChoice($value, $label, $posn = -1) {
        if (! in_array($this -> _type, array('check', 'select', 'radio'))) {
            throw new AP5L_Forms_Exception(
                'Cannot add choice to field ' . $this -> _name,
                AP5L_Forms_Exception::ERR_BADOP);
        }
        $choice = array('label' => $label, 'value' => (string) $value);
        if ($posn < 0 || $posn >= count($this -> _choices)) {
            $this -> _choices[] = $choice;
        } else {
            $this -> _choices = array_splice($this -> _choices, $posn, 0, array($choice));
        }
    }

    function clear() {
        if (! $this -> _static) {
            $this -> _value = '';
        }
        $this -> isValid = true;
    }

    static function &factory() {
        $args = func_get_args();
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }
        $argc = count($args);
        if (count($args) < 2) {
            throw new AP5L_Forms_Exception(__CLASS__ . ':: factory requires($type, $name)');
        }
        $object = &new AP5L_Forms_Field_Scalar($args[0], $args[1]);
        if ($argc >= 3) {
            $object -> setMaxLength($args[2]);
        }
        if ($argc >= 4) {
            $object -> setDisplaySize($args[3]);
        }
        if ($argc >= 5) {
            $object -> setSortGroup($args[4]);
        }
        return $object;
    }

    /**
     * Get choice string for the current field value
     * @return string Selection string corresponding to current value
     * @throws AP5L_Forms_Exception if no choice is defined for the value.
     */
    function getChoice() {
        if (! isset($this -> _choices[$this -> _value])) {
            throw new AP5L_Forms_Exception(
                'Cannot get choice ' . $this -> _value . ' from field ' . $this -> _name,
                AP5L_Forms_Exception::ERR_BADOP);
        }
        foreach ($this -> _choices as $choice) {
            if ($choice['value'] == $this -> _value) {
                return $choice['label'];
            }
        }
    }

    /**
     * Get choices for the field
     * @return array Array of choices, each an array with 'label' and 'value'
     * enties
     */
    function getChoices() {
        return $this -> _choices;
    }

    /**
     * Get the field's display size.
     * @return int|array Field size if not a textarea; if textarea, size is
     * array(x,y).
     */
    function getDisplaySize() {
        return $this -> _displaySize;
    }

    /**
     * Get the name of the message to use as a heading for this phase.
     * @see hasPhase()
     * @param int Processing phase.
     */
    function getHeadingName($phase = 0) {
        if (($phase == 0) && ($this -> _type == 'setpass')) {
            return $this -> _name . '_verify';
        }
        return $this -> _name;
    }

    /**
     * Get the maximum data size of the field
     * @return int Character capacity of the field
     */
    function getMaxLength() {
        return $this -> _maxLength;
    }

    /**
     * Get the name of the field to use in HTML generation.
     * @see hasPhase()
     * @param int Processing phase.
     */
    function getName($phase = 0) {
        if (($phase == -1) && ($this -> _type == 'setpass')) {
            return $this -> _name . '_verify';
        }
        return $this -> _name;
    }

    /**
     * Return the current field value.
     * @return string The field's current value.
     */
    function getValue() {
        return $this -> _value;
    }

    /**
     * Return a reference to the field value.
     * @return string Reference to the field's value.
     */
    function &getValueRef() {
        return $this -> _value;
    }

    /**
     * Determine if the field particpates in a phase.
     *
     * The "phase" is a concept for multi-part fields (i.e. setpass). phase -1
     * is the part before the main body; phase 0 is the main body, where
     * error/status messages will be generated; phase 1 is the part after the
     * main body.
     *
     * @param int The phase being queried.
     * @return boolean True if the field participates in the phase.
     */
    function hasPhase($phase) {
        //
        if ($phase == 0) {
            return true;
        } else if ($phase < 0) {
            return $this -> _type == 'setpass';
        }
        return false;
    }

    function setDisplaySize($size) {
        $this -> _displaySize = $size;
    }

    function setMaxLength($length) {
        $this -> _maxLength = $length;
    }

    /**
     * Set the field's value.
     *
     * @param string New value for the field.
     */
    function setValue($val) {
        $this -> _value = $val;
    }

    /**
     * Get the default value for the persistent flag for scalar fields.
     */
    protected function persistentDefault() {
        return ! in_array(
            $this -> _type,
            array('button', 'header', 'hidden', 'label', 'submit')
        );
    }

    /**
     * Get the default value for the static flag for scalar fields.
     */
    protected function staticDefault() {
        return in_array(
            $this -> _type,
            array('button', 'header', 'hidden', 'label', 'submit')
        );
    }

    /**
     * Get the default value for the visible flag for scalar fields.
     */
    protected function visibleDefault() {
        return ! in_array($this -> _type, array('hidden'));
    }

}

?>
