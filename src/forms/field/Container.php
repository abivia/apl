<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: Container.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A field container is a wrapper for a set of sub-fields, in a variety of
 * topologies.
 * 
 * @package AP5L
 * @subpackage Forms
 */
class AP5L_Forms_Field_Container extends AP5L_Forms_Field {
    /**
     * @var array Array[id] of fields to display in the container.
     */
    protected $fields = array();
    /**
     * @var boolean Set if fields are sorted in display order.
     */
    protected $fieldsSorted;
    /**
     * @var array List of types acceptable to this class
     */
    static $typeList = array(
        '_root'
        );

    /**
     * Form field constructor.
     *
     * @param string The field type.
     * @param string The field name.
     */
    function __construct($type, $name) {
        parent::__construct($type, $name);
    }

    /**
     * Compare function for the form arranger.
     *
     * @param AP5L_Forms_Field The left part of the comparison.
     * @param AP5L_Forms_Field The right part of the comparison.
     * @return int -1 if left$lt;right, 0 if left=right, 1 if left&gt;right
     */
    function _arrangeCmp($lf, $rf) {
        if ($lf -> getSortGroup() < $rf -> getSortGroup()) return -1;
        if ($lf -> getSortGroup() > $rf -> getSortGroup()) return 1;
        if ($lf -> fieldPosition < $rf -> fieldPosition) return -1;
        if ($lf -> fieldPosition > $rf -> fieldPosition) return 1;
        return 0;
    }

    protected function _findField($name) {
        foreach ($this -> fields as $ind => $field) {
            if ($field -> getName() == $name) {
                return $ind;
            }
        }
        throw new AP5L_Forms_Exception('Unable to find field ' . $name, AP5L_Forms_Exception::ERR_NO_FIELD);
    }

    function _getFieldValue($name) {
        return $this -> fields[$this -> _findField($name)] -> getValue();
    }

    function addField(&$field, $initValue = null) {
        if (! is_null($initValue)) {
            $field -> setValue($initValue);
        }
        try {
            $this -> fields[$this -> _findField($field -> _name)] = &$field;
        } catch (AP5L_Forms_Exception$e) {
            $this -> fields[] = &$field;
        }
        $this -> fieldsSorted = false;
    }

    /**
     * Determine the appearance of the form by applying sorting rules.
     */
    function arrange($preset) {
        if ($this -> fieldsSorted) return;
        $ind = 0;
        foreach ($this -> fields as $key => $field) {
            $this -> fields[$key] -> fieldPosition = $ind++;
            $this -> fields[$key] -> setStyle($preset);
            if ($field instanceof $this) {
                $field -> arrange($preset);
            }
        }
        $this -> fieldsSorted = usort($this -> fields, array(&$this, '_arrangeCmp'));
    }

    function clear() {
        for ($ind = 0; $ind < count($this -> fields); ++$ind) {
            $this -> fields[$ind] -> clear();
        }
    }

    function deleteField($name) {
        unset($this -> fields[$this -> _findField($name)]);
        return true;
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
        $object = &new AP5L_Forms_Field_Container($args[0], $args[1]);
        return $object;
    }

    function getCount() {
        return count($this -> fields);
    }

    function &getField($name) {
        $ind = $this -> _findField($name);
        return $this -> fields[$ind];
    }

    function &getFields() {
        return $this -> fields;
    }

    function getFieldValue($name) {
        return $this -> fields[$this -> _findField($name)] -> getValue();
    }

    function getResults($withTemps = false) {
        $results = array();
        foreach ($this -> fields as $field) {
            if ($withTemps || $field -> isVisible()) {
                $results[$field -> getName()] = $field -> getValue();
            }
        }
        return $results;
    }

    function setSorted($sorted) {
        $this -> fieldsSorted = $sorted;
    }

}
