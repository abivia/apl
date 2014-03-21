<?php
/**
 * ACL tree-structured data support.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Tree.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Support for tree-structured entities.
 *
 * Tree structured entities have a parent-child relationship with an arbitrary
 * number of children per parent. This tructure implements grouped items in the
 * ACL.
 *
 * @package AP5L
 * @subpackage Acl
 */
abstract class AP5L_Acl_Tree extends AP5L_Acl_DomainMember {
    /**
     * Element path IDs.
     *
     * This is an array of ID numbers that defines the path to this element. The
     * path can be absolute or relative. Absolute paths have a zero as the first
     * element; relative paths have the ID of the starting point. The last
     * element is always the current object ID. This may be false if the object
     * is new and not yet put to the data store.
     *
     * @var array
     */
    protected $_idPath;

    /**
     * Parent for hierarchical objects.
     *
     * @var int
     */
    protected $_parentID;

    /**
     * Add a new element.
     *
     * Adds an element to the ACL store. If no parent path is provided, an
     * element at the top level is created.
     *
     * @param array Information on the class.
     * @param AP5L_Acl_Store The data store object.
     * @param string Element section name. Must be the name of an existing element
     * section.
     * @param string|array The new element name, or a path to the new element
     * relative to the parent. All elements of the path except the last must
     * exist, unless the "create" option is specified.
     * @param array Optional path to the element's parent. If provided, each
     * element in the array names an element in the provided section.
     * @param array Options. Defined options are: [create]=boolean: if set, the
     * entire element path is created.
     * @return AP5L_Acl_Tree New element of the requested type.
     * @throws AP5L_Acl_Exception If the path to the new element does not exist
     * and the create option is not set.
     */
    static protected function &_add(
        $template, &$store, $sectionName, $elementPath, $options = array()
    ) {
        if (is_string($sectionName)) {
            $sectionTemplate = call_user_func(array($template['sectionClass'], 'template'));
            $section = AP5L_Acl_Section::genericFetchByName(
                $sectionTemplate, $store, $sectionName, array('exists' => true)
            );
        } else {
            $section = $sectionName;
        }
        /*
         * Get the parent
         */
        if (isset($options['parent'])) {
            $parentID = (int) $options['parent'];
        } else {
            $parentID = 0;
        }
        /*
         * Locate the parent, if there is a parent.
         */
        if (! is_array($elementPath)) {
            $elementPath = array($elementPath);
        }
        $lastName = array_pop($elementPath);
        if (count($elementPath)) {
            $toParent = self::_fetchByPath(
                $template, $store, $section, $elementPath, $options
            );
            if ($toParent) {
                /*
                 * Parent exists, make sure the target doesn't.
                 */
                self::_fetchByPath(
                    $template, $store, $section, $lastName,
                    array('parent' => $toParent -> getID(), 'exists' => false)
                );
            } elseif (isset($options['create']) && $options['create']) {
                /*
                 * Create the parent path
                 */
                $toParent = self::_fetchByPath(
                    $template, $store, $section, $elementPath, array('create' => true)
                );
            } else {
                throw new AP5L_Acl_Exception('Path not found.');
            }
            $parentID = $toParent -> getID();
        } else {
            $parentID = 0;
            /*
             * Ensure the target doesn't already exist
             */
            self::_fetchByPath(
                $template, $store, $section, $lastName, array('exists' => false)
            );
        }
        /*
         * Create the element.
         */
        $element = call_user_func(
            array($template['className'], 'factory'), $section, $parentID, $lastName
        );
        if (isset($options['hidden'])) {
            $element -> setHidden($options['hidden']);
        }
        if (isset($options['info'])) {
            $element -> setInfo($options['info']);
        }
        $store -> put($element);
        return $element;
    }

    /**
     * Remove an element from the ACL store.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string Element section name.
     * @param string|array Element name or path.
     * @param array Options.
     */
    static protected function _delete($template, &$store, $sectionName, $path, $options = array()) {
        $sectionTemplate = call_user_func(array($template['sectionClass'], 'template'));
        $section = AP5L_Acl_Section::genericFetchByName(
            $sectionTemplate, $store, $sectionName, array('exists' => true)
        );
        if (! is_array($path)) {
            $path = array($path);
        }
        $element = self::_fetchByPath(
            $template, $store, $section, $path, array('exists' => true)
        );
        $store -> delete($element);
    }

    /**
     * Retrieve an element by path/name.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param AP5L_Acl_ElementSection The element section.
     * @param array|string One or more path elements.
     * @param array Options: [create]=boolean: if set, the entire element path is
     * created. [exists] =boolean: if set, the element must exist. If false, the
     * element must not exist. [parent] A base element that the pathis relative to.
     * [_path] =array (internal) current path elements for error reporting.
     * @return AP5L_Acl_Element|false Element if found, false if not.
     */
    static protected function &_fetchByPath(
        $template, &$store, $section, $path, $options = array()
    ) {
        $sectionID = $section -> getID();
        if (isset($options['parent'])) {
            $parentID = (int) $options['parent'];
        } else {
            $parentID = 0;
        }
        if (is_array($path)) {
            $elementName = array_shift($path);
        } else {
            $elementName = $path;
            $path = array();
        }
        if (! isset($options['_path'])) {
            $options['_path'] = array();
        }
        $options['_path'][] = $elementName;
        if (! isset($options['_idPath'])) {
            $options['_idPath'] = array();
        }
        $options['_idPath'][] = $parentID;
        $element = &$store -> get(
            $template['className'],
            array(
                $template['sectionID'] => $sectionID,
                $template['name'] => $elementName,
                '_parentID' => $parentID
            ),
            array('first' => true)
        );
        if (count($path)) {
            if ($element) {
                $options['parent'] = $element -> getID();
                $element = self::_fetchByPath($template, $store, $section, $path, $options);
            } elseif (isset($options['create']) && $options['create']) {
                $element = call_user_func(
                    array($template['className'], 'factory'), $section, $parentID, $elementName
                );
                $element -> setStore($store);
                $store -> put($element);
                $element -> setIDPath($options['_idPath']);
            }
        }
        if (isset($options['exists'])) {
            if ($options['exists'] && $element === false) {
                throw new AP5L_Acl_Exception(
                    $template['displayName'] . ' "' . implode('/', $options['_path']) . '" does not exist.'
                );
            } elseif (! $options['exists'] && $element !== false) {
                throw new AP5L_Acl_Exception(
                    $template['displayName'] . ' "' . implode('/', $options['_path']) . '" already exists.'
                );
            }
        }
        return $element;
    }

    /**
     * Get a list of elements contained in a path.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param AP5L_Acl_Section|string The section in which to list.
     * @param array The base path to list.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Elements.
     */
    static protected function &_listing(
        $template, &$store, $sectionName, $path = null, $options = array()
    ) {
        if (is_string($sectionName)) {
            $sectionTemplate = call_user_func(array($template['sectionClass'], 'template'));
            $section = AP5L_Acl_Section::genericFetchByName(
                $sectionTemplate, $store, $sectionName, array('exists' => true)
            );
        } else {
            $section = $sectionName;
        }
        if (is_null($path)) {
            $parentID = 0;
        } else {
            if (! is_array($path)) {
                $path = array($path);
            }
            $element = self::_fetchByPath(
                $template, $store, $section, $path, array('exists' => true)
            );
            $parentID = $element -> getID();
        }
        $result = $store -> get(
            $template['className'],
            array($template['sectionID'] => $section -> getID(), '_parentID' => $parentID),
            $options
        );
        return $result;
    }

    /**
     * Merge one element into another.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section of the source element.
     * @param array|string The element path to merge from.
     * @param string The section of the target element.
     * @param array|string The element path to merge to.
     * @param array Options.
     */
    static protected function _merge(
        $template, &$store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
     ) {
        $sectionTemplate = call_user_func(array($template['sectionClass'], 'template'));
        $fromSection = AP5L_Acl_Section::genericFetchByName(
            $sectionTemplate, $store, $fromSectionName, array('exists' => true)
        );
        $toSection = AP5L_Acl_Section::genericFetchByName(
            $sectionTemplate, $store, $toSectionName, array('exists' => true)
        );
        /*
         * Get the source and destination elements
         */
        if (! is_array($fromPath)) {
            $fromPath = array($fromPath);
        }
        if (! is_array($toPath)) {
            $toPath = array($toPath);
        }
        $fromElement = self::_fetchByPath(
            $template, $store, $fromSection, $fromPath, array('exists' => true)
        );
        $toElement = self::_fetchByPath(
            $template, $store, $toSection, $toPath, array('exists' => true)
        );
        $store -> treeMmerge($fromElement, $toElement);
    }

    /**
     * Move element to a different location.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param string The section of the source element.
     * @param array|string The element path to move from.
     * @param string The section of the renamed element.
     * @param array|string The element path to move to.
     * @param array Options.
     */
    static protected function _move(
        $template, &$store, $fromSectionName, $fromPath, $toSectionName, $toPath, $options = array()
     ) {
        $sectionTemplate = call_user_func(array($template['sectionClass'], 'template'));
        $fromSection = AP5L_Acl_Section::genericFetchByName(
            $sectionTemplate, $store, $fromSectionName, array('exists' => true)
        );
        $toSection = AP5L_Acl_Section::genericFetchByName(
            $sectionTemplate, $store, $toSectionName, array('exists' => true)
        );
        /*
         * Get the source element
         */
        if (! is_array($fromPath)) {
            $fromPath = array($fromPath);
        }
        $fromElement = self::_fetchByPath(
            $template, $store, $fromSection, $fromPath, array('exists' => true)
        );
        /*
         * Ensure the target doesn't already exist
         */
        if (! is_array($toPath)) {
            $toPath = array($toPath);
        }
        $lastName = array_pop($toPath);
        /*
         * Locate the parent, if there is a parent.
         */
        if (count($toPath)) {
            $toParent = self::_fetchByPath(
                $template, $store, $toSection, $toPath
            );
            if ($toParent) {
                /*
                 * Parent exists, make sure the target doesn't.
                 */
                self::_fetchByPath(
                    $template, $store, $toSection, $lastName,
                    array('parent' => $toParent -> getID(), 'exists' => false)
                );
            } else {
                /*
                 * Create the parent path
                 */
                $toParent = self::_fetchByPath(
                    $template, $store, $toSection, $toPath, array('create' => true)
                );
            }
            $parentID = $toParent -> getID();
        } else {
            $parentID = 0;
            /*
             * Ensure the target doesn't already exist
             */
            self::_fetchByPath(
                $template, $store, $toSection, $lastName, array('exists' => false)
            );
        }
        $fromElement -> setName($lastName);
        $fromElement -> setParentID($parentID);
        $store -> update($fromElement);
    }

    /**
     * Get a list of elements contained in a path.
     *
     * @param AP5L_Acl_Store The data store object.
     * @param AP5L_Acl_Section|string The section in which to list.
     * @param string The element name to match.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort orders. Sort identifiers are defined in
     * {@see $_fieldMap} and can be followed with "asc" or "desc" to specify
     * sequence.
     * </li></ul>
     * @return array Elements.
     */
    static protected function &_search(
        $template, &$store, $sectionName, $elementName, $options = array()
    ) {
        if (is_string($sectionName)) {
            $sectionTemplate = call_user_func(array($template['sectionClass'], 'template'));
            $section = AP5L_Acl_Section::genericFetchByName(
                $sectionTemplate, $store, $sectionName, array('exists' => true)
            );
        } else {
            $section = $sectionName;
        }
        $element = &$store -> get(
            $template['className'],
            array(
                $template['sectionID'] => $section -> getID(),
                $template['name'] => $elementName
            )
        );
        return $element;
    }

    function getIDPath($returnRoot = true) {
        if ($this -> _idPath === false) {
            return false;
        }
        if ($this -> _idPath[0]) {
            $parents = $this -> _store -> getParentPath($this, $this -> _idPath[0]);
            $this -> _idPath = array_merge($parents, $this -> _idPath);
        }
        if (! $returnRoot) {
            return array_slice($this -> _idPath, 1);
        }
        return $this -> _idPath;
    }

    function getParentID() {
        return $this -> _parentID;
    }

    function load($keysOrBoth, $vals = null) {
        parent::load($keysOrBoth, $vals);
        $this -> setID($this -> getID());
    }

    function setID($id) {
        if ($this -> _parentID !== false) {
            $this -> _idPath = array($this -> _parentID, $id);
        } else {
            $this -> _idPath = array($id);
        }
    }

    function setIDPath($path) {
        if (! count($path)) {
            if (($id = $this -> getID()) === false) {
                $this -> _idPath = false;
            }
            return;
        }
        $this -> setParentID(array_pop($path));
        $this -> _idPath = array_merge($path, $this -> _idPath);
    }

    function setParentID($parentID) {
        $this -> _parentID = $parentID;
        if (($id = $this -> getID()) !== false) {
            $this -> _idPath = array($parentID, $id);
        } else {
            $this -> _idPath = false;
        }
    }

}
