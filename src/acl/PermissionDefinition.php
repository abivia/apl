<?php
/**
 * Permission definition; specifies the values a permission can assume.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: PermissionDefinition.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A permission definition helps the user manage permissions by providing the
 * names (tag) and type for available permissions.
 * 
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_PermissionDefinition extends AP5L_Acl_DomainMember {
    protected $_default;
    protected $_permissionDefinitionID;
    protected $_permissionName;
    protected $_permissionSectionID;
    protected $_rules;
    protected $_type = 'text';
    static protected $_typeDefs = array(
        'choice', 'int', 'float', 'number', 'text'
    );

    static protected $_fieldMap = array(
        'id' => '_permissionDefinitionID',
        'name' => '_permissionName',
        'sectionid' => '_permissionSectionID',
        'type' => '_permissionType',
    );

    function __construct($name = '') {
        $this -> _permissionName = $name;
    }

    /**
     * Add a permission definition.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Permission section name. Must be the name of an existing
     * permission section.
     * @param AP5L_Acl_PermissionDefinition The permission definition object to
     * be added.
     * @param array Options.
     */
    static function &add(&$store, $sectionName, &$pdef, $options = array()) {
        if (is_string($sectionName)) {
            /*
             * Get the section
             */
            $section = AP5L_Acl_PermissionSection::fetchByName(
                $store, $sectionName, array('exists' => true)
            );
        } else {
            $section = $sectionName;
        }
        $pdef -> setSectionID($section -> getID());
        /*
         * Make sure the permission isn't already defined.
         */
        self::fetchByName($store, $section, $pdef -> getName(), array('exists' => false));
        $store -> put($pdef, array('replace' => false));
        return $pdef;
    }

    function definition($type = 'text', $rules = '', $defaultValue = '') {
        if (! in_array($type, self::$_typeDefs)) {
            throw new AP5L_Acl_Exception('Invalid type: ' . $type);
        }
        $this -> _type = $type;
        // TODO: validation of rules by type
        $this -> _rules = $rules;
        try {
            $this -> _default = $this -> validate($defaultValue);
        } catch (AP5L_Acl_Exception $e) {
            if ($defaultValue !== '') {
                throw $e;
            }
            switch ($this -> _type) {
                case 'choice': {
                    $this -> _default = $this -> _rules['choices'][0];
                }
                break;

                case 'float':
                case 'int':
                case 'number': {
                    $this -> _default = 0;
                }
                break;

                case 'text': {
                    $this -> _default = str_repeat(' ', $this -> _rules['length-min']);
                }
                break;
            }
        }
    }

    /**
     * Delete a permission definition.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Permission section name. Must be the name of an existing
     * permission section.
     * @param string The permission name to remove.
     * @param array Options.
     */
    static function delete(&$store, $sectionName, $pdefName, $options = array()) {
        if (isset($options['silent']) && $options['silent']) {
            $fetchOpts = array();
        } else {
            $fetchOpts = array('exists' => true);
        }
        /*
         * Get the section
         */
        $section = AP5L_Acl_PermissionSection::fetchByName(
            $store, $sectionName, $fetchOpts
        );
        if (! $section) {
            // This is a siltent failure
            return;
        }
        /*
         * Make sure the permission exists.
         */
        $pdef = self::fetchByName($store, $section, $pdefName, $fetchOpts);
        if (! $pdef) {
            // This is a silent failure
            return;
        }
        $store -> delete($pdef);
    }

    static function &factory($name = '') {
        $pdef = new AP5L_Acl_PermissionDefinition($name);
        return $pdef;
    }

    static function &fetchByName(&$store, $section, $pdefName, $options = array()) {
        if (is_string($section)) {
            $section = AP5L_Acl_PermissionSection::fetchByName(
                $store, $section, array('exists' => true)
            );
        }
        $pdef = &$store -> get(
            __CLASS__,
            array(
                '_permissionSectionID' => $section -> getID(),
                '_permissionName' => $pdefName
            ),
            array('first' => true)
        );
        if (isset($options['exists'])) {
            if ($options['exists'] && $pdef === false) {
                throw new AP5L_Acl_Exception(
                    'Permission Definition "' . $pdefName . '" does not exist.'
                );
            } elseif (! $options['exists'] && $pdef !== false) {
                throw new AP5L_Acl_Exception(
                    'Permission Definition "' . $pdefName . '" already exists.'
                );
            }
        }
        return $pdef;
    }

    function getDefault() {
        return $this -> _default;
    }

    function getID() {
        return $this -> _permissionDefinitionID;
    }

    function getName() {
        return $this -> _permissionName;
    }

    function getRules() {
        return $this -> _rules;
    }

    function getSectionID() {
        return $this -> _permissionSectionID;
    }

    function getType() {
        return $this -> _type;
    }

    /**
     * Get a list of permissions.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Asset section names.
     */
    static function &listing(&$store, $options = array()) {
        if (isset($options['order'])) {
            $options['order'] = self::_mapUserSort($options['order'], self::$_fieldMap);
        } else {
            $options['order'] = self::_mapUserSort('name', self::$_fieldMap);
        }
        $result = $store -> get(__CLASS__, null, $options);
        return $result;
    }

    static function update(&$store, $sectionName, $pdef, $options = array()) {
        $section = self::fetchByName($store, $sectionName, array('exists' => true));
        if (isset($options['hidden'])) {
            $pdef -> setHidden($options['hidden']);
        }
        if (isset($options['info'])) {
            $pdef -> setInfo($options['info']);
        }
        if (isset($options['name'])) {
            $pdef -> setName($options['name']);
        }
        $store -> update($pdef);
    }

    function setID($id) {
        $this -> _permissionDefinitionID = $id;
    }

    function setName($name) {
        $name = trim($name);
        if ($name === '') {
            throw new AP5L_Acl_Exception(
                'Permission definition name must be non-blank.'
            );
        }
        $this -> _permissionName = $name;
    }

    function setSectionID($id) {
        $this -> _permissionSectionID = $id;
    }

    function validate($input) {
        switch ($this -> _type) {
            case 'choice': {
                $found = false;
                if (isset($this -> _rules['case-sensitive']) && $this -> _rules['case-sensitive']) {
                    $found = in_array($input, $this -> _rules['choices']);
                    $valid = $input;
                } else {
                    foreach ($this -> _rules['choices'] as $value) {
                        if (! strcasecmp($input, $value)) {
                            $valid = $value;
                            $found = true;
                            break;
                        }
                    }
                }
                if (! $found) {
                    throw new AP5L_Acl_Exception(
                        'Invalid choice in ' . $this -> _permissionName . '.'
                    );
                }
            }
            break;

            case 'float':
            case 'int':
            case 'number': {
                if (! is_numeric($input)) {
                    throw new AP5L_Acl_Exception(
                        'Value is not a valid "' . $this -> _type . '".'
                    );
                }
                if ($this -> _type == 'int') {
                    $valid = (int) $input;
                } elseif ($this -> _type == 'float') {
                    $valid = (float) $input;
                } else {
                    $valid = $input;
                }
            }
            break;

            case 'text': {
                if (isset($this -> _rules['length-min'])
                    && strlen($input) < $this -> _rules['length-min']
                ) {
                    throw new AP5L_Acl_Exception(
                        'Value must be at least ' . $this -> _rules['length-min']
                        . ' characters in ' . $this -> _permissionName . '.'
                    );
                }
                if (isset($this -> _rules['length-max'])
                    && strlen($input) > $this -> _rules['length-max']
                ) {
                    throw new AP5L_Acl_Exception(
                        'Value must not exceed ' . $this -> _rules['length-max']
                        . ' characters in ' . $this -> _permissionName . '.'
                    );
                }
                $valid = $input;
            }
            break;

            default: {
                throw new AP5L_Acl_Exception(
                    'Corrupt permission definition: type "' . $this -> _type
                    . '" not valid in ' . $this -> _permissionName . '.'
                );
            }
            break;

        }
        return $valid;
    }

}
