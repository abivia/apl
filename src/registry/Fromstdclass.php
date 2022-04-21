<?php

/**
 *
 */
trait AP5L_Registry_FromStdclass {

    protected function loadStdlass($config) {
        foreach ($config as $property => $value) {
            if (property_exists($this, $property)) {
                if (
                    is_object($this -> $property)
                    && method_exists($this -> $property, 'loadStdlass')
                ) {
                    $this -> $property -> loadStdlass($value);
                } else {
                    $this -> $property = $value;
                }
            }
        }
    }

}
