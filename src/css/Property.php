<?php

class AP5L_Css_Property {
    /**
     * Flag set if the property has an !important tag.
     *
     * @var boolean
     */
    var $important;

    /**
     * Property name
     * @var string
     */
    var $name;

    /**
     * Property values.
     *
     * @var array
     */
    var $value;

    /**
     * Property value units.
     *
     * @var array
     */
    var $units;

    /**
     * Create an AP5L_Css_Property object.
     *
     * @param string Name of the property.
     * @param mixed Scalar value or array of values for the property (eg. properties
     * with width and height values).
     * @param mixed Scalar units or array of units. Must have the same number of
     * elements as the values.
     * @param boolean Flag set if this property is marked as important.
     * @return AP5L_Css_Property The new object.
     */
    static function &factory($name, $value, $units, $important) {
        $prop = new AP5L_Css_Property();
        $prop -> name = $name;
        if (is_array($value)) {
            $prop -> value = $value;
        } else {
            $prop -> value = array($value);
        }
        if (is_array($units)) {
            $prop -> units = $units;
        } else {
            $prop -> units = array($units);
        }
        $prop -> important = $important;
        return $prop;
    }

}

