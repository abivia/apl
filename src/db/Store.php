<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Store.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Interface for an abstract data store.
 *
 * This interface defines a data store with basic data manipulation operations.
 * The interface is designed to accomodate more than a typical relational
 * database as a back end store.
 * 
 * @package AP5L
 * @subpackage Db
 */
interface AP5L_Db_Store {
    /**
     * Deltete one or more objects.
     *
     * If an object is passed as the first parameter, then it should be used to
     * provide a primary key for the deletion. If a class name is passed, then
     * the criteria qualify the set of objects to be deleted.
     *
     * @param object|string Either the instance of the object to be deleted or
     * the name of the object's class.
     * @param AP5L_DB_Expr|array|string Either an expression object, an array of
     * key value pairs, or a criteria string. An array is considered to be a
     * list of equalities that need to be satisfied. A string must be handled by
     * the implementation.
     * @param array Any options. Options are implementation specific.
     * @throws AP5L_Db_Exception On error (for example, if the object class
     * cannot be handled by this store).
     */
    function delete($object, $criteria = null, $options = array());

    /**
     * Retrieve one or more objects.
     *
     * If an object is passed as the first parameter, then it should be used to
     * provide a primary key for the fetch. If a class name is passed, then the
     * criteria qualify the set of objects to be selected.
     *
     * @param object|string Either the instance of the objects to be retrieved
     * (by primary key values) or the name of the object's class.
     * @param AP5L_DB_Expr|array|string Either an expression object, an array of
     * key value pairs, or a criteria string. An array is considered to be a
     * list of equalities that need to be satisfied. A string must be handled by
     * the implementation.
     * @param array Options: [first]=boolean: return the first matching object.
     * Other options are implementation specific.
     * @return object|array|boolean Returns either an array of objects, or if
     * the "first" option is set, a single object if a match is found or false
     * if not.
     * @throws AP5L_Db_Exception On any failure.
     */
    function &get($object, $criteria = null, $options = array());

    /**
     * Prepare data store for objects.
     *
     * Install perfroms any operations required to prepare the data store to
     * accept objects. The install method is also responsible for managing
     * upgrades to the data store.
     *
     * @param string The version of the data store to install. Optional.
     * @param string The version of an existing data store, if any.
     * @param array Options are implementation specific.
     * @throws AP5L_Db_Exception On any failure.
     */
    function install($version = '', $oldVersion = '', $options = array());

    /**
     * Put an object into the data store.
     *
     * @param object An instance of the object to be saved.
     * @param array Options:
     * <ul><li>"deep" boolean(true) When true, save objects that are related to
     * this object. When false, ignore sub-objects.
     * </li><li>"replace" boolean(true): if true, overwrite any existing object
     * with the same primary keys, if false, throw an error if an object with
     * the same primary key exists.
     * </li></ul> Other options are implementation specific.
     * @throws AP5L_Db_Exception On any failure.
     */
    function put(&$object, $options = array());

    /**
     * Select a set of objects for retrieval.
     *
     * If an object is passed as the first parameter, then it should be used to
     * provide a primary key for the select. If a class name is passed, then the
     * criteria qualify the set of objects to be selected.
     *
     * @param object|string Either the instance of the objects to be retrieved
     * (by primary key values) or the name of the object's class.
     * @param AP5L_DB_Expr|array|string Either an expression object, an array of
     * key value pairs, or a criteria string. An array is considered to be a
     * list of equalities that need to be satisfied. A string must be handled by
     * the implementation.
     * @param array Options are implementation specific.
     * @return AP5L_Db_RecordSet The set of selected objects.
     * @throws AP5L_Db_Exception On any failure.
     */
    function &select($object, $criteria = null, $options = array());

    /**
     * Set data store level options.
     *
     * @param array List of option values, indexed by option name.
     */
    function setOptions($options);

    /**
     * Update an object in the data store.
     *
     * @param object An instance of the object to be saved.
     * @param array Other options are implementation specific.
     * @throws AP5L_Db_Exception On any failure.
     */
    function update($object, $criteria = null, $options = array());

}
