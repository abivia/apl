<?php
/**
 * Abivia PHP Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2019, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */

namespace Apl\Sql;

use \Apl\Breaker;

/**
 * Clean up variables and escape for a SQL statement
 */
class Escape {

    static protected $caseMap = [
        'lcase' => MB_CASE_LOWER,
        'title' => MB_CASE_TITLE,
        'ucase' => MB_CASE_UPPER,
    ];

    /**
     * Clean, escape, and quote a value according to the format specifier.
     * @param mysqli Database connection.
     * @param string $value The value to be escaped.
     * @param string|array $colType Column format type.optional.subtype.subtype; arrays use the dbFormat index.
     * @return string The cleaned, escaped, and quoted value.
     * @throws \Exception, \Apl\Breaker
     */
    static public function _($dbc, $value, $colType = 'char') {
        if (is_array($colType)) {
            $colType = $colType['dbFormat'];
        }
        $parts = explode('.', $colType . '...');
        if ($parts[1] == 'opt' && ($value === '' || $value === null)) {
            throw new Breaker();
        }
        if ($value === null) {
            return 'NULL';
        }
        $result = '';
        switch ($parts[0]) {
            case 'char': {
                $result = '\'' . $dbc -> real_escape_string(self::caseMap($value, $parts[2])) . '\'';
            }
            break;
            case 'date': {
                if ($value === '') {
                    $result = 'NULL';
                } else {
                    $result = '\'' . $dbc -> real_escape_string($value) . '\'';
                }
            }
            break;
            case 'flag': {
                $result = $value != '' ? 1 : 0;
            }
            break;
            case 'float': {
                if (is_numeric($value)) {
                    if (is_numeric($parts[2])) {
                        $result = round($value, $parts[2]);
                    } else {
                        $result = (float)$value;
                    }
                } else {
                    $result = 'NULL';
                }
            }
            break;
            case 'int': {
                if (is_numeric($value)) {
                    $result = (int) $value;
                } else {
                    $result = 'NULL';
                }
            }
            break;
            default: {
                throw new \Exception('Unknown column type: ' . $colType);
            }
            break;
        }
        return $result;
    }

    static function caseMap($value, $op) {
        if (isset(self::$caseMap[$op])) {
            $value = mb_convert_case($value, self::$caseMap[$op]);
        }
        return $value;
    }

}

