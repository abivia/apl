<?php
/**
 * Abivia PHP Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2018, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */

namespace Apl\Mixins;

/**
 * Copy configuration information from the setup file.
 */
trait JsonConfiguration {
    /*
     * Looks for two properties in classes with this trait:
     * -- static jsonPropertyClass[] is an array of class names, indexed by property name.
     * If an association exists for a propery and the propery is not an object,
     * an object of the named class will be instantiated and initialized.
     * -- static jsonPropertyKey[] is an array of variable names in a json object, indexed
     * by property name. If the JSON data is an array, this function will use the named
     * variable as the key for creating an associative array. The default is "key", set to
     * an empty string to disable the feature.
     */

    /**
     * Called when all JSON data has been loaded
     */
    protected function jsonInit() {

    }

    /**
     * Copy configuration data to object properties.
     * @param object $config Object from decoding the configuration JSON file.
     */
    public function jsonLoad($config) {
        $classMap = isset(self::$jsonPropertyClass) ? self::$jsonPropertyClass : [];
        foreach ($config as $property => $value) {
            // Only populate declared properties
            if (!property_exists($this, $property)) {
                continue;
            }
            $className = isset($classMap[$property]) ? $classMap[$property] : '';
            if (is_object($value)) {
                $this -> $property = $this -> jsonLoadObject($this -> $property, $className, $value);
            } elseif (is_array($value)) {
                // See if the json array should become an associative array
                $arrayKey = isset(self::$jsonPropertyKey[$property])
                    ? self::$jsonPropertyKey[$property] : 'key';
                if (!is_array($this -> $property)) {
                    $this -> $property = [];
                }
                foreach ($value as $entry) {
                    if ($className == '' || !method_exists($className, 'jsonLoad')) {
                        $this -> $property[] = $entry;
                        continue;
                    }
                    if ($arrayKey == '' || !isset($entry -> $arrayKey)) {
                        $newObj = new $className;
                        $newObj -> jsonLoad($entry);
                        $this -> $property[] = $newObj;
                    } else {
                        $keyValue = $entry -> $arrayKey;
                        if (!isset($this -> $property[$keyValue])) {
                            $this -> $property[$keyValue] = new $className;
                        }
                        $this -> $property[$keyValue] = $this -> jsonLoadObject(
                            $this -> $property[$keyValue], $className, $entry
                        );
                    }
                }
            } else {
                $this -> $property = $value;
            }
        }
        $this -> jsonInit();
    }

    protected function jsonLoadObject($var, $className, $value) {
        // See if we can improve on a lowly stdClass
        if (
            !is_object($var)
            && $className != ''
            && method_exists($className, 'jsonLoad')
        ) {
            // The property does not exist and is associated with a configurable
            // object, create it.
            $var = new $className;
        }
        if (
            is_object($var)
            && method_exists($var, 'jsonLoad')
        ) {
            // The property is already a configurable object, update.
            $var -> jsonLoad($value);
        } else {
            // Just assign the stdClass
            $var = $value;
        }
        return $var;
    }

}

