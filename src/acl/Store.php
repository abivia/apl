<?php
/**
 * ACL Data Storage.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Store.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Storage manager for ACL. Specific implementations based on this template.
 * 
 * @package AP5L
 * @subpackage Acl
 */
abstract class AP5L_Acl_Store implements AP5L_Db_Store {
    /**
     * Current domain identifier.
     *
     * @var int
     */
    protected $_domainID = 0;

    abstract function assetMerge($fromAsset, $toAsset);

    static function &factory($dsn) {
        if (class_exists('MDB2', true)) {
            /*
             * If MDB2 is present, see if the DSN refers to a connection it can
             * handle.
             */
            $dsnInfo = MDB2::parseDSN($dsn);
            $driver = $dsnInfo['phptype'];
            if (! PEAR::isError(MDB2::loadClass('MDB2_Driver_' . $driver, false))) {
                $driver = 'Mdb2';
            }
        } else {
            $driver = @parse_url($dsn, PHP_URL_SCHEME);
            if ($driver === false) {
                throw new AP5L_Acl_Exception('Bad DSN: ' . $dsn);
            }
        }
        $driver = ucfirst(strtolower($driver));
        $storeClass = 'AP5L_Acl_Store_' . $driver;
        if (! class_exists($storeClass, true)) {
            throw new AP5L_Acl_Exception('Unknown driver: ' . $driver);
        }
        $store = &call_user_func(array($storeClass, 'factory'), $dsn);
        return $store;
    }

    abstract function sectionMerge($fromSection, $toSection);

    function setDomain($domain) {
        $this -> _domainID = $domain -> getID();
    }

    /**
     * Set data store level options.
     *
     * @param array List of option values, indexed by option name.
     */
    function setOptions($options) {
        $unknowns = array();
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'prefix': {
                    $this -> _tablePrefix = $value;
                }
                break;

                default: {
                    // Don't throw an exception until all valid options are set.
                    $unknowns[] = $key;
                }
                break;

            }
        }
        if (count($unknowns)) {
            throw new AP5L_Acl_Exception(
                'Unknown option(s) "' . implode(',', $unknowns) . '".'
            );
        }
    }

}
