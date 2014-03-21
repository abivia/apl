<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: AttributeRule.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A CSS attribute rule.
 * 
 * @package AP5L
 * @subpackage Css
 */
class AP5L_Css_AtrributeRule extends AP5L_Css_Rule {
    public $attributeOperator;             // =, ~=, |=
    public $attributeValue;

    function __construct($attr, $oper, $value) {
        parent::_construct($attr);
        $this -> attributeOperator = $oper;
        $this -> attributeValue = $value;
    }

}
