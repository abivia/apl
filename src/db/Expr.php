<?php
/**
 * Generalized database expression.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Expr.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Database expression.
 *
 * This class represents an expression for a data store. Currently this is a
 * skeletal class that simply stores the text representation of the expression.
 * The intent is to have parsers create expression trees that can be translated
 * for the specific database implementation.
 * 
 * @package AP5L
 * @subpackage Db
 */
class AP5L_Db_Expr {
    /**
     * Expression string. This should be deprecated and removed in a full
     * implementation.
     */
    private $_expr;

    function __construct($expr = '') {
        $this -> _expr = $expr;
    }

    static function &factory($expr) {
        $exprObj = new AP5L_Db_Expr($expr);
        return $exprObj;
    }

    function getExpr() {
        return $this -> _expr;
    }

}
