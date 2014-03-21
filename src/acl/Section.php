<?php
/**
 * Abstract ACL section support.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Section.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * ACL section support.
 * 
 * This class contains the bulk of the code used to implement sections in the
 * ACL.
 * 
 * @package AP5L
 * @subpackage Acl
 */
abstract class AP5L_Acl_Section extends AP5L_Acl_DomainMember {
    
    /**
     * Add a section to the ACL store.
     *
     * @param array Information on the class.
     * @param AP5L_Acl_Store The data store object.
     * @param string Section name. The section must not already exist.
     * @param array Options.
     * @return AP5L_Acl_Section The new section object.
     * @throws AP5L_Acl_Exception If the name is empty or if the section already
     * exists.
     * @throws AP5L_Db_Exception On any data store error.
     */
    static protected function &_add($template, &$store, $sectionName, $options = array()) {
        $sectionName = trim($sectionName);
        if ($sectionName === '') {
            throw new AP5L_Acl_Exception(
                $template['displayName'] . ' name must be non-blank.'
            );
        }
        $result = self::genericFetchByName($template, $store, $sectionName, array('exists' => false));
        $section = call_user_func(array($template['className'], 'factory'), $sectionName);
        if (isset($options['hidden'])) {
            $section -> setHidden($options['hidden']);
        }
        if (isset($options['info'])) {
            $section -> setInfo($options['info']);
        }
        $store -> put($section);
        return $section;
    }

    /**
     * Remove a section from the ACL store.
     *
     * @param array Information on the class.
     * @param AP5L_Acl_Store The data store object.
     * @param string Section name.
     * @param array Options.
     */
    static protected function _delete($template, &$store, $sectionName, $options = array()) {
        $section = self::genericFetchByName($template, $store, $sectionName);
        if (! $section) {
            if (isset($options['silent']) && $options['silent']) {
                return;
            }
            throw new AP5L_Acl_Exception(
                'Cannot delete ' . self::DISPLAY_NAME
                . ' "' . $sectionName . '", doesn\'t exist.'
            );
        }
        $store -> delete($section);
    }

    /**
     * Retrieve a section by name.
     *
     * @param array Information on the class.
     * @param AP5L_Acl_Store The data store object.
     * @param The section name.
     * @param array Options: [exists]=boolean: if set, the section must exist.
     * If false, the section must not exist.
     * @return object|false Section if found, false if not.
     * @throws AP5L_Acl_Exception On conflict between "exists" option and
     * section existance.
     */
    static function &genericFetchByName($template, &$store, $sectionName, $options = array()) {
        $section = &$store -> get(
            $template['className'],
            array($template['sectionName'] => $sectionName),
            array('first' => true)
        );
        if (isset($options['exists'])) {
            if ($options['exists'] && $section === false) {
                throw new AP5L_Acl_Exception(
                    $template['displayName'] . ' "' . $sectionName . '" does not exist.'
                );
            } elseif (! $options['exists'] && $section !== false) {
                throw new AP5L_Acl_Exception(
                    $template['displayName'] . ' "' . $sectionName . '" already exists.'
                );
            }
        }
        return $section;
    }

    /**
     * Get a list of sections.
     *
     * @param array Information on the class.
     * @param AP5L_Acl_Store The data store object.
     * @param array Defined options are:
     * <ul><li>"order" An array of sort object property names. Properties can be
     * followed with "asc" or "desc" to specify sequence.
     * </li></ul>
     * @return array Asset section names.
     */
    static protected function &_listing($template, &$store, $options = array()) {
        $result = $store -> get($template['className'], null, $options);
        return $result;
    }

    /**
     * Merge one section into another.
     *
     * @param array Information on the class.
     * @param AP5L_Acl_Store The data store object.
     * @param string Section name to merge from.
     * @param string Section name merge into.
     * @param array Options.
     */
    static protected function _merge($template, &$store, $fromSectionName, $toSectionName, $options = array()) {
        $fromSection = self::genericFetchByName($template, $store, $fromSectionName, array('exists' => true));
        $toSection = self::genericFetchByName($template, $store, $toSectionName, array('exists' => true));
        $store -> sectionMerge($fromSection, $toSection);
    }

    /**
     * Update a section.
     *
     * @param array Information on the class.
     * @param AP5L_Acl_Store The data store object.
     * @param string Section name.
     * @param array Options.
     */
    static protected function _update($template, &$store, $sectionName, $options = array()) {
        $section = self::genericFetchByName($template, $store, $sectionName, array('exists' => true));
        if (isset($options['hidden'])) {
            $section -> setHidden($options['hidden']);
        }
        if (isset($options['info'])) {
            $section -> setInfo($options['info']);
        }
        if (isset($options['name'])) {
            $newSectionName = trim($options['name']);
            if ($newSectionName === '') {
                throw new AP5L_Acl_Exception(
                    'New ' . $template['displayName'] . ' name must be non-blank.'
                );
            }
            $section -> setName($newSectionName);
        }
        $store -> update($section);
    }

}
