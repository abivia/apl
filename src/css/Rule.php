<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Rule.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A CSS rule.
 * 
 * @package AP5L
 * @subpackage Css
 */
class AP5L_Css_Rule {
    /**
     * Comment to insert when generating.
     *
     * @var string
     */
    protected $_comment;

    /**
     * Style properties.
     *
     * @var array
     */
    public $propertySet;

    /**
     * The selector for this rule (element/class/etc.)
     *
     * @var string
     */
    public $selector;

    /**
     * Attribute rules.
     *
     * @var array
     */
    public $subAttributes;

    /**
     * Child rules.
     *
     * @var array
     */
    public $subChildren;

    /**
     * Sub-class rules.
     *
     * @var array
     */
    public $subClasses;

    /**
     * Descendant rules.
     *
     * @var array
     */
    public $subDescendants;

    /**
     * ID based rules.
     *
     * @var array
     */
    public $subIds;

    /**
     * Psuedo-class rules.
     *
     * @var array
     */
    public $subPsuedoclasses;

    /**
     * Sibling rules.
     *
     * @var array
     */
    public $subSiblings;

    function __construct($selector) {
        $this -> propertySet = new AP5L_Css_PropertySet();
        $this -> selector = $selector;
    }

    function addAttributeRules($attrs) {
        $this -> subAttributes[] = $attrs;
    }

    function getChildren() {
        return $this -> subChildren;
    }

    function &getClass($className) {
        if (! isset($this -> subClasses[$className])) {
            $this -> subClasses[$className] = new AP5L_Css_ClassRule($className);
        }
        return $this -> subClasses[$className];
    }

    function getClasses() {
        return $this -> subClasses;
    }

    function getComment() {
        return $this -> _comment;
    }

    function getDescendants() {
        return $this -> subDescendants;
    }

    function getProperties() {
        return $this -> propertySet;
    }

    function setComment($comment) {
        $this -> _comment = $comment;
    }

    function setProperty($name, $value = '') {
        $this -> propertySet -> setProperty($name, $value);
    }

}
