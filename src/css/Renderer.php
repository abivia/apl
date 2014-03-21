<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Renderer.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Generate CSS code for a sheet.
 * 
 * @package AP5L
 * @subpackage Css
 */
class AP5L_Css_Renderer {

    /**
     * Render a style sheet.
     *
     * @param AP5L_Css_Sheet The style sheet to be rendered.
     * @param array Options. Defined options are: "compact" Generates smaller
     * sheets.
     * @return string CSS for the sheet
     */
    static function render($sheet, $options = array()) {
        $code = '';
        $rules = $sheet -> getRules();
        foreach ($rules as $rule) {
            $code .= self::renderRule($rule, '', $options);
        }
        return $code;
    }

    /**
     * Render a rule's intrinsic properties, then any subsidiary elements.
     *
     * @param AP5L_Css_Rule the rule to be rendered
     * @param string Optional selector prefix.
     * @param array Options. Defined options are: "compact" Generates smaller
     * rules.
     * @return string CSS for the rule.
     */
    static function renderRule($rule, $baseSelect = '', $options = array()) {
        $compact = isset($options['compact']) ? $options['compact'] : false;
        if ($compact) {
            $ifmt = '';
            $indent = '';
        } else {
            $ifmt = chr(10);
            $indent = '    ';
        }
        $code = '';
        if ($comment = $rule -> getComment()) {
            if ($compact) {
                $code .= '/* ' . $comment . ' */' . chr(10);
            } else {
                $code .= '/*' . chr(10) . ' * ' . $comment . chr(10) . ' */' . chr(10);
            }
        }
        $localSelect = $baseSelect . $rule -> selector;
        $pSet = $rule -> getProperties();
        $properties = $pSet -> properties;
        if ($properties) {
            $code .= $localSelect;
            $code .= ' {' . $ifmt;
            foreach ($properties as $prop => $val) {
                $code .= $indent . $prop . ':' . $val . ';' . $ifmt;
            }
            $code .= '}' . $ifmt . chr(10);
        }
        $subs = $rule -> getClasses();
        if ($subs) {
            foreach ($subs as $subRule) {
                $code .= self::renderRule($subRule, $localSelect . '.', $options);
            }
        }
        $subs = $rule -> getDescendants();
        if ($subs) {
            foreach ($subs as $subRule) {
                $code .= self::renderRule($subRule, $localSelect . ' ', $options);
            }
        }
        $subs = $rule -> getChildren();
        if ($subs) {
            foreach ($subs as $subRule) {
                $code .= self::renderRule($subRule, $localSelect . ' > ', $options);
            }
        }
        return $code;
    }

}
