<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Cache.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Text message fetch and cache manager.
 *
 * Manage a cache of strings. This class is intended to be used as a singleton
 * see {@see getInstance()}, but other instances can be created for specialized
 * applications.
 */
class AP5L_Text_L10n_Cache extends AP5L_Php_InflexibleObject {
    /**
     * Cached messages. Array indexed by message code.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Cache age array. Size of cached data, indexed by message code. The oldest
     * element is first in the array.
     *
     * @var array
     */
    protected $_cacheAge = array();

    /**
     * Cache hints structure.
     *
     * @var array
     */
    protected $_cacheHint = array();

    /**
     * Cache hint age array. Size of cached data, indexed by cache hint message
     * code. The oldest element is first in the array.
     *
     * @var array
     */
    protected $_cacheHintAge = array();

    /**
     * "Cache time" chronometer for hints.
     *
     * @var int
     */
    protected $_chronoHint = 0;

    /**
     * "Cache time" chronometer for messages.
     *
     * @var int
     */
    protected $_chronoMsg = 0;

    /**
     * Data store connection status
     *
     * @var boolean
     */
    protected $_isOpen;

    /**
     * Language selectors. Array allows fallback languages, beyond the
     * intrinsic hierarchy.
     *
     * @var array
     */
    protected $_language = array();

    /**
     * Size of messages in the general cache
     *
     * @var int
     */
    protected $_memCacheGeneral = 0;

    /**
     * Size of messages in the hint cache
     *
     * @var int
     */
    protected $_memCacheHint = 0;

    /**
     * Size of messages in the locked cache
     *
     * @var int
     */
    protected $_memCacheLocked = 0;

    /**
     * Maximum memory consumption for cache, no limit if zero.
     *
     * @var int;
     */
    protected $_memLimitGeneral = 0;

    /**
     * Maximum memory consumption for hint controlled cache, no limit if zero.
     *
     * @var int;
     */
    protected $_memLimitHint = 0;

    /**
     * List of preloaded message groups.
     *
     * @var array AP5L_Text_L10n_Hint
     */
    protected $_preload = array();

    /**
     * Reference to singleton.
     *
     * @var AP5L_Text_L10n_Cache
     */
    static protected $_singleton;

    /**
     * Underlying data store.
     *
     * @var AP5L_Text_L10n_Store
     */
    protected $_store;

    /**
     * Find all hint structures that are relevant to this message
     */
    protected function &_getHint($messageCode) {
        // find the hint structure for the message and return it
        $path = explode('.', $messageCode);
        $hint = null;
        $walk = &$this -> _cacheHint;
        foreach ($path as $step) {
            if (! isset($walk[$step])) {
                break;
            }
            if ($walk[$step] -> getType() != AP5L_Text_L10n_Hint::TYPE_UNDEFINED) {
                $hints = &$walk[$step];
            }
            $walk = &$walk[$step] -> subHints;
        }
        return $hint;
    }

    /**
     * Record a cache hit.
     */
    protected function _hit($message, $hint = null) {
        if (! $hint) {
            $hint = $this -> _getHint($message -> messageCode);
        }
        if ($hint) {
            $cType = $hint -> getType();
            if ($cType == AP5L_Text_L10n_Hint::TYPE_NORMAL
                || ($cType == AP5L_Text_L10n_Hint::TYPE_SHORT && ! $message -> isLong)
            ) {
                unset($this -> cacheHintAge[$hint -> chrono]);
                $hint -> chrono = ++$this -> chronoHint;
                $this -> cacheHintAge[$hint -> chrono] = $hint;
            }
        } else {
            $message = &$this -> cache[$message -> messageCode];
            unset($this -> _cacheAge[$message -> chrono]);
            $message -> chrono = ++$this -> _chronoMsg;
            $this -> cacheAge[$message -> chrono] = &$message;
        }
    }

    protected function _loadHints() {
        // Get contents of hints table from store
        $hints = $this -> _store -> getHints();
        /*
         * Build hints and preload structures.
         */
        $this -> _cacheHint = array();
        $this -> _preload = array();
        foreach ($hints as $hint) {
            $path = explode('.', $hint -> messageCode);
            $lastPart = array_pop($path);
            /*
             * Make sure the path to this hint exists.
             */
            $walk = &$this -> _cacheHint;
            $walkPath = array($walk);
            if (! empty($path)) {
                $synthKey = '';
                $glue = '';
                foreach ($path as $step) {
                    $synthKey .= $glue . $step;
                    $glue = '.';
                    if (! isset($walk[$step])) {
                        $synth = new AP5L_Text_L10n_Hint;
                        $synth -> messageCode = $synthKey;
                        $walk[$step] = $synth;
                    }
                    $walkPath[] = &$walk[$step] -> subHints;
                    $walk = &$walk[$step] -> subHints;
                }
            }
            if (isset($walk[$lastPart])) {
                $walk[$lastPart] -> hint = $hint -> hint;
            } else {
                $walk[$lastPart] = $hint;
            }
            /*
             * Check for preloads
             */
            if ($hint -> hint & AP5L_Text_L10n_Hint::CACHE_PRELOAD) {
                $this -> _preload[] = $hint;
            }
        }
    }

    /**
     * Write a message into cache.
     *
     * This method determines if there is a cache hint applicable to the
     * message, and if so, uses the hint to determine the storage policy,
     * caching it if required. If there is no hint, it is written to the general
     * cache.
     */
    protected function _write($message, $hint = null) {
        if (! isset($this -> _cache[$message -> messageCode])) {
            if (! $hint) {
                $hint = $this -> _getHint($message -> messageCode);
            }
            if ($hint) {
                $cType = $hint -> getType();
                if ($hint -> hint & AP5L_Text_L10n_Hint::CACHE_LOCKED) {
                    // Locked: Write to general cache with no age tracking
                    $this -> cache[$message -> messageCode] = &$message;
                    $this -> _memCacheLocked += $message -> messageSize;
                } elseif ($cType == AP5L_Text_L10n_Hint::TYPE_NORMAL
                    || ($cType == AP5L_Text_L10n_Hint::TYPE_SHORT && ! $message -> isLong)
                ) {
                    $this -> cache[$message -> messageCode] = &$message;
                    $hint -> cacheTrack[$message -> messageCode] = &$message;
                    $hint -> cacheSize += $message -> messageSize;
                    unset($this -> cacheHintAge[$hint -> chrono]);
                    $hint -> chrono = ++$this -> chronoHint;
                    $this -> cacheHintAge[$hint -> chrono] = $hint;
                    $this -> _memCacheHint += $message -> messageSize;
                    if ($this -> _memLimitHint) {
                        /*
                         * Swap out entire hint caches, oldest first.
                         */
                        while ($this -> _memCacheHint > $this -> _memLimitHint) {
                            $out = array_shift($this -> cacheHintAge);
                            $this -> _memCacheHint -= $out -> cacheSize;
                            foreach ($out -> cacheTrack as $code => $msg) {
                                unset($out -> cacheTrack[$code]);
                                unset($this -> cache[$code]);
                            }
                        }
                    }

                }
            } else {
                unset($this -> _cacheAge[$message -> chrono]);
                $message -> chrono = ++$this -> _chronoMsg;
                $this -> _cache[$message -> messageCode] = &$message;
                $this -> _cacheAge[$message -> chrono] = &$message;
                $this -> _memCacheGeneral += $message -> messageSize;
                if ($this -> _memLimitGeneral) {
                    while ($this -> _memCacheGeneral > $this -> _memLimitGeneral) {
                        $out = array_shift($this -> cacheAge);
                        $this -> _memCacheGeneral -= $out -> messageSize;
                        unset($this -> _cache[$out -> messageCode]);
                    }
                }
            }
        } else {
            $this -> _hit($message, $hint);
        }
    }

    function _init() {
        $this -> _cache = array();
        $this -> _cacheHint = array();
        $this -> _memCacheGeneral = 0;
        $this -> _memCacheHint = 0;
        $this -> _memCacheLocked = 0;
    }

    function close() {
        if ($this -> _isOpen) {
            $this -> _store -> close();
            $this -> _isOpen = false;
            $this -> _init();
        }
    }

    function get($messageCode) {
        if (! $this -> isOpen) {
            throw new AP5L_Text_Exception('Data store is not open.');
        }
        if (isset($this -> _cache[$messageCode])) {
            $this -> _hit($messageCode);
            $result = $this -> _cache[$messageCode] -> message;
        } else {
            $result = false;
            $hint = $this -> _getHint($messageCode);
            if ($hint && $hint -> getType() != AP5L_Text_L10n_Hint::TYPE_NEVER) {
                $messages = $this -> _store -> readBlock($this -> language, array($hint));
                foreach ($messages as $message) {
                    if ($message -> messageCode == $messageCode) {
                        $result = $message -> message;
                    }
                    $this -> _write($message, $hint);
                }
                if (isset($this -> _cache[$messageCode])) {
                    $result = $this -> _cache[$messageCode];
                } else {
                }
            } else {
                $message = $this -> _store -> read($messageCode);
                if ($message !== false) {
                    $this -> _write($message);
                    $result = $message -> message;
                }
            }
            if ($result === false) {
                throw new AP5L_Text_Exception('Message ' . $messageCode . ' not found.');
            }
        }
        return $result;
    }

    static function getInstance() {
        if (! self::$_singleton) {
            self::$_singleton = new AP5L_Message_Manager();
        }
        return self::$_singleton;
    }

    function open($language, $options = array()) {
        if (isset($options['store']) && $options['store']) {
            $this -> setStore($options['store']);
            if ($this -> _isOpen) {
                $this -> close();
            }
        } elseif ($this -> _isOpen) {
            throw new AP5L_Text_Exception('Data store is already open.');
        }
        if (! is_array($language)) {
            $language = array($language);
        }
        $this -> _language = $language;
        if (! $this -> _store -> isOpen()) {
            $this -> _store -> open($options);
        }
        $this -> _isOpen = true;
        $this -> _loadHints();
        if (! empty($this -> _preload)) {
            $messages = $this -> _store -> readBlock($this -> _language, $this -> _preload);
            foreach ($messages as $message) {
                $this -> _write($message);
            }
        }
    }

    function setStore(AP5L_Text_L10n_Store $store) {
        $this -> close();
        $this -> _store = $store;
    }

}