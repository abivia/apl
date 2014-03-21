<?php
/**
 * Abivia PHP5 Library.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: ResultSet.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * This class is a wrapper for the underlying data store's result set object.
 *
 * The intention of this class is to provide facilities for both traditional
 * relational databases (flat tables) and for object based data stores that deal
 * with hierarchically structured results.
 * 
 * @package AP5L
 * @subpackage Db
 */
abstract class AP5L_Db_ResultSet {
    /**
     * Default fetch mode. Driver dependent.
     */
    const MODE_DEFAULT = 0;

    /**
     * For relational sets, return rows with integer ordinal column indicies.
     */
    const MODE_ORDINAL = 1;

    /**
     * For relational sets, return rows indexed by column name.
     */
    const MODE_ASSOC = 2;

    /**
     * Return results as objects.
     */
    const MODE_OBJECT = 3;

    /**
     * Return everything in the result set.
     *
     * @param array Options. Implementation specific.
     * @return mixed An array or object set representing the query results.
     */
    abstract function fetchAll($options = array());

    /**
     * Return the next member of the result set.
     *
     * @param array Options. Implementation specific.
     * @return mixed An array or object set representing the next element in the
     * result set.
     */
    abstract function fetchNext($options = array());

    /**
     * Return a single element from the result set.
     *
     * @param array Options. Implementation specific.
     * @return mixed An array or object set representing the first element in
     * the result set.
     */
    abstract function fetchOne($options = array());

    /**
     * Return a slice of the result set.
     *
     * In relational data stores, a slice is a column. In hierarchical stores,
     * it is (probably) an axis.
     *
     * @param mixed A specification of the requested slice, e.g. column
     * identifier.
     * @param array Options. Implementation specific.
     * @return mixed An array, object, or result set representing the slice.
     */
    abstract function fetchSlice($slice = null, $options = array());

    /**
     * Free internal resources.
     */
    function free() {
    }

}
