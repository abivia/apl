<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Message.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A localized message.
 */
class AP5L_Text_L10n_Message extends AP5L_Php_InflexibleObject {
    /**
     * When this hint was cached, in "cache time".
     */
    public $chrono;

    /**
     * Long message flag. The data store is responsible for detemining when a
     * message is long.
     *
     * @var boolean
     */
    public $isLong;

    /**
     * Language for this message. Not always populated.
     *
     * @var string
     */
    public $language;

    /**
     * The message text.
     *
     * @var string
     */
    public $message;

    /**
     * The message identifier.
     *
     * @var string
     */
    public $messageCode;

    /**
     * Size of the message.
     */
    public $messageSize;

    /**
     * Date of last update to the message. Format: yyyy-mm-dd hh:nn:ss.
     *
     * @var string
     */
    public $updateDate;
}