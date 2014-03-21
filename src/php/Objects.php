<?php
/**
 * Object comparison class.
 *
 * @package AP5L
 * @subpackage Php
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Objects.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Object comparison class.
 */
class AP5L_Php_Objects {

    /**
     * Compare two objects by member and return true if they're equal. Unlike the
     * equality operators, two objects with null members are considered equal if
     * both corresponding members are in fact null.
     *
     * @param object Base object.
     * @param object Object to compare to base.
     * @param array|boolean Options. Boolean for compatibility with "where"
     * option. Valid values are "where": return a string identifying where
     * a mismatch ocurred; "depth": maximum depth to compare nested objects.
     */
    static function compare($left, $right, $returnWhere = false) {
        if (is_null($left) && is_null($right)) {
            return true;
        }
        if (is_null($left) || is_null($right)) {
            return false;
        }
        if ((! is_object($left)) || (! is_object($right))) {
            return false;
        }
        $tov = get_object_vars($left);
        $uov = get_object_vars($right);
        $uovKeys = array_keys($uov);
        foreach ($tov as $tMember => $tVal) {
            if (in_array($tMember, $uovKeys)) {
                if (is_null($tVal) && is_null($uov[$tMember])) {
                    // fall through to equal case
                } else {
                    if (is_object($tVal)) {
                        $equal = self::compare($tVal, $uov[$tMember]);
                    } else {
                        $equal = $tVal == $uov[$tMember];
                    }
                    if ($equal !== true) {
                        // FIXME: This is busted. return a path to the offending member.
                        if ($returnWhere) {
                            return 'left.' . $tMember;
                        }
                        return false;
                    }
                }
                // Both members are equal
                unset($tov[$tMember]);
                unset($uov[$tMember]);
            }
        }
        if (count($uov)) {
            if ($returnWhere) {
                $uovKeys = array_keys($uov);
                return 'right.' . $uovKeys[0];
            }
            return false;
        }
        return true;
    }

}
?>