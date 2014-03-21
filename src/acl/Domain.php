<?php
/**
 * ACL Domain object.
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Domain.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * A domain is a completely distinct ACL space within a data store.
 * 
 * @package AP5L
 * @subpackage Acl
 */
class AP5L_Acl_Domain extends AP5L_Acl_DomainMember {
    
    /**
     * The name of this domain.
     * 
     * @var string
     */
    protected $_domainName = '';
    
    /**
     * Hashed password for this domain.
     * 
     * @var string
     */
    protected $_passMD5 = '';
    
    /**
     * Random hash value for the password.
     * 
     * @var string
     */
    protected $_passHash = '';

    /**
     * Class constructor.
     * 
     * @param string Optional name of the domain.
     * @param string Optional domain password.
     */
    function __construct($name = '', $pass = '') {
        $this -> _domainName = $name;
        if ($pass) {
            $this -> _passHash = md5(uniqid($name, true));
            $this -> _passMD5 = md5($pass . $this -> _passHash);
        }
    }

    /**
     * Check a pssword for validity.
     * 
     * @param string Password.
     * @return boolean True if password is correct.
     */
    function checkPassword($pass) {
        return $this -> _passMD5 == md5($pass . $this -> _passHash);
    }

    /**
     * Create a new domain object.
     * 
     * @param string Optional name of the domain.
     * @param string Optional domain password.
     * @return AP5L_Acl_Domain The new domain object.
     */
    static function &factory($name = '', $pass = '') {
        $domain = new AP5L_Acl_Domain($name, $pass);
        return $domain;
    }

    /**
     * Get the domain identifier.
     * 
     * @return int The domain identifier.
     */
    function getID() {
        return $this -> _domainID;
    }

    /**
     * Get the domain name.
     * 
     * @param string The domain name.
     */
    function getName() {
        return $this -> _domainName;
    }

    /**
     * Maps sort orders into matching properties in this class.
     * 
     * @var array
     */
    static protected $_fieldMap = array(
        'id' => '_assetID',
        'name' => '_assetName',
        'sectionid' => '_assetSectionID',
    );

    /**
     * Set the domain identifier.
     * 
     * @param int The domain identifier.
     */
    function setID($id) {
        $this -> _domainID = $id;
    }

    /**
     * Set the domain name.
     * 
     * @return string The domain name.
     */
    function setName($name) {
        $this -> _domainName = $name;
    }

}
