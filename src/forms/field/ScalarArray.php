<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: ScalarArray.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Field arrays are containers for scalars, arranged by either rows or columns.
 *
 * The fields member contains the definitions for array elements. The values
 * are maintained as arrays in this object.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Field_ScalarArray extends AP5L_Forms_Field_Container {
    /**
     * @var int The number of elements in the array.
     */
    protected $elementCount = 0;
    /**
     * @var array List of types acceptable to this class
     */
    static $typeList = array(
        'cols', 'rows'
        );
    /**
     * @var array Values associated with the defined fields, indexed by field
     * name.
     */
    protected $values = array();

    /**
     * Form field constructor.
     *
     * @param string The field type.
     * @param string The field name.
     * @param int The minimum number of data elements.
     */
    function __construct($type, $name, $count = 0) {
        parent::__construct($type, $name);
        $this -> elementCount = $count;
    }

    function addField(&$field, $initValue = null) {
        if (! $field instanceof AP5L_Forms_Field_Scalar) {
            throw new AP5L_Forms_Exception('Can only add scalar fields to ' . __CLASS__);
        }
        if (! is_null($initValue)) {
            $field -> setValue($initValue);
        }
        try {
            $ind = $this -> _findField($field -> _name);
        } catch (AP5L_Forms_Exception $e) {
            $ind = count($this -> fields);
        }
        $field -> valueIndex = $ind;
        $this -> fields[$ind] = &$field;
        if ($this -> elementCount) {
            $this -> values[$ind] = array_fill(0, $this -> elementCount, '');
        } else {
            $this -> values[$ind] = array();
        }
        $this -> fieldsSorted = false;
    }

    function clear() {
        for ($ind = 0; $ind < count($this -> fields); ++$ind) {
            $field = &$this -> fields[$ind];
            $field -> clear();
            if (! $field -> isStatic()) {
                $slot = $field -> valueIndex;
                if ($this -> elementCount) {
                    $this -> values[$slot] = array_fill(0, $this -> elementCount, '');
                } else {
                    $this -> values[$slot] = array();
                }
            }
        }
    }

    /**
     * Create an array field object.
     *
     * @param array|string The field type. If array, an array of parameters
     * (type, name, etc).
     * @param string The field name.
     * @param int Element count. Optional.
     * @param int Sort group. Optional
     * @return AP5L_Forms_Field_ScalarArray The new array object.
     */
    static function &factory() {
        $args = func_get_args();
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }
        $argc = count($args);
        if (count($args) < 2) {
            throw new AP5L_Forms_Exception(__CLASS__ . ':: factory requires($type, $name)');
        }
        $object = &new AP5L_Forms_Field_ScalarArray($args[0], $args[1]);
        if (count($args) >= 3) {
            $object -> setElementCount($args[2]);
        }
        if (count($args) >= 4) {
            $object -> setSortGroup($args[3]);
        }
        return $object;
    }

    function getElementCount() {
        return $this -> elementCount;
    }

    /**
     * Return the value of the field as a matrix indexed by field name and
     * entry number.
     * @return array Field values.
     */
    function getValue($fieldName = '', $index = false) {
        if ($fieldName) {
            $ind = $this -> fields[$this -> _findField($fieldName)] -> valueIndex;
            if ($index === false) {
                $values = $this -> values[$ind];
            } else {
                $values = $this -> values[$ind][$index];
            }
        } else {
            $values = array();
            foreach ($this -> fields as $field) {
                $values[$field -> getName()] = $this -> values[$field -> valueIndex];
            }
        }
        return $values;
    }

    /**
     * Return a reference to the field value.
     * @return string Reference to the field's value.
     */
    function &getValueRef() {
        return $this -> values;
    }

    function setElementCount($count) {
        $this -> elementCount = $count;
    }

    /**
     * Set the value of a field.
     * @param string|int The field name, if string, or index, if integer.
     * @param int The index to set
     * @param mixed The value to set.
     * @return string The field's current value.
     */
    function setValue($fieldName, $index, $value) {
        if (gettype($fieldName) != 'integer') {
            // Make sure the field is defined
            $ind = $this -> _findField($fieldName);
            $slot = $this -> fields[$ind] -> valueIndex;
        } else {
            // We assume this is a view that knows how to handle direct access.
            $slot = $fieldName;
        }
        if (! isset($this -> values[$slot])) {
            $this -> values[$slot] = array();
        }
        $this -> values[$slot][$index] = $value;
    }


}
