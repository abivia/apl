<?php
/**
 * AP5L The Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Hint.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Cache hint structure.
 */
class AP5L_Text_L10n_Hint {
    /**
     * Bitmask for cache type settings
     */
    const CACHE_TYPE = 15;

    /**
     * Preload (bitmask): Load into cache on open.
     */
    const CACHE_PRELOAD = 16;

    /**
     * Lock (bitmask): hold in cache once loaded.
     */
    const CACHE_LOCK = 32;

    /**
     * Cache type for no cache.
     */
    const TYPE_NEVER = 0;

    /**
     * Cache type for normal caching.
     */
    const TYPE_NORMAL = 1;

    /**
     * Cache type for cache short messages only.
     */
    const TYPE_SHORT = 2;

    /**
     * Cache type when no value specified.
     */
    const TYPE_UNDEFINED = CACHE_TYPE;

    /**
     * Total size of messages in this cache segment.
     */
    public $cacheSize = 0;

    /**
     * Messages cached by this hint, indexed by message code.
     */
    public $cacheTrack = array();

    /**
     * When this hint was cached, in "cache time".
     */
    public $chrono;

    /**
     * The hint value, built from the CACHE_ and TYPE_ constants.
     *
     * @var int
     */
    public $hint = self::TYPE_UNDEFINED;

    /**
     * The message scope for this hint.
     *
     * @var string
     */
    public $messageCode;

    /**
     * Subsidiary hints. Computed after load from data store.
     *
     * @var array AP5L_Text_L10n_Hint
     */
    public $subHints = array();

    /**
     * Create a hint with the details required to define it.
     *
     * A simple convenience function to define a hint that will be stored.
     * Requires a valid base message code and suitable settings for the hint
     * field. The hint field is validated and an exception is thrown if the
     * value is not valid.
     *
     * @param string Message code. Messages with this code or messages derived
     * from this code will be subject to this hint (unless another hint
     * explicitly applies to a derived code).
     * @param int Hint instruction. A combination of a hint type and cache
     * instruction flags.
     */
    static function &factory($messageCode, $hint) {
        if ($hint & self::CACHE_TYPE == self::TYPE_UNDEFINED) {
            throw new AP5L_Text_L10n_Exception('Cache type cannot be undefined.');
        }
        $hobj = new AP5L_Text_L10n_Hint;
        $hobj -> messageCode = AP5L_Text_L10n::messageCodeClean($messageCode);
        $hobj -> hint = $hint;
        return $hobj;
    }

    function getType() {
        return $this -> hint & AP5L_Text_L10n_Hint::CACHE_TYPE;
    }

}