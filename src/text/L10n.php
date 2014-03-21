<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: L10n.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Text localization common functions.
 */
class AP5L_Text_L10n {

    static function messageCodeClean($messageCode, $allowWildcards = false) {
        $wild = $allowWildcards ? '%' : '';
        $messageCode = preg_replace(
            array('/[^a-z0-9_\.\-' . $wild . ']/i', '/\.+/'),
            array('', '.'),
            array('\.+', '.'),
            $messageCode
        );
        if (! empty($messageCode) && $messageCode[strlen($messageCode) - 1] == '.') {
            $messageCode = substr($messageCode, 0, strlen($messageCode) - 1);
        }
        return $messageCode;
    }

}
