<?php
/**
 * Abivia PHP5 Library
 *
 * @package AP5L
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2008, Alan Langford
 * @version $Id: Text.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Text: Base and helper functions for text processing.
 */
class AP5L_Text extends AP5L_Php_InflexibleObject {

    /**
     * Determine line delimiter for a file.
     *
     * @param string Text with lines delimited by any combination of CR anf LF.
     * @return string Line ending used in the buffer.
     */
    static function sniffEol($buffer) {
        // Figure out what the source file is using for a line end
        $eol = '';
        $ref = "\n\r";
        for ($ind = 0; $ind < strlen($buffer); ++$ind) {
            if (($hit1 = strpos($ref, $buffer[$ind])) !== false) {
                $eol = $ref[$hit1];
                $next = $ind + 1;
                if ($next < strlen($buffer) && ($hit2 = strpos($ref, $buffer[$next])) !== false) {
                    if ($hit1 != $hit2) {
                        $eol .= $ref[$hit2];
                    }
                }
                break;
            }
        }
        return $eol;
    }

    /**
     * Strip a word (contiguous non-blanks) from the start of a string.
     *
     * @param string reference to the subject string. The string is
     * modified by this method.
     * @return string The first word of the string.
     */
    static function stripWord(&$args) {
        if ($args =='') {
            return '';
        }
        $scan = 0;
        while ($args[$scan] == ' ') {
            ++$scan;
        }
        if (($posn = strpos($args, ' ', $scan)) !== false) {
            $word = substr($args, $scan, $posn);
            $args = trim(substr($args, $posn));
        } else {
            $word = $args;
            $args = '';
        }
        return $word;
    }

}
