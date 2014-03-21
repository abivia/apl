<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: PsuedoclassRule.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A CSS psuedoclass rule.
 * 
 * @package AP5L
 * @subpackage Css
 */
class AP5L_Css_PsuedoclassRule extends AP5L_Css_Rule {
    public $className;                     // Name of psuedoclass
    public $classValue;                    // Value of psuedoclass, as required
}
