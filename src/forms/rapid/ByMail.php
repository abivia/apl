<?php
/**
 * Abivia PHP5 Library
 * 
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: ByMail.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

require_once('Mail.php');
require_once('Mail/mime.php');


/**
 * A form that saves by sending mail.
 * 
 * @package AP5L
 * @subpackage Forms
 * @todo complete phpdocs
 */
class AP5L_Forms_Rapid_ByMail extends AP5L_Forms_Rapid {
    /**
     * The body of the message as created by onPrepareData.
     * 
     * @var string
     */
    var $_body;
    var $_mailBcc = array();
    var $_mailCc = array();
    var $_mailDriver;
    var $_mailFrom;
    var $_mailServer;
    var $_mailSubject = 'Comment from Web site';
    
    function __construct($verifier = null, $opts = null) {
        parent::__construct($verifier, $opts);
        $this -> _mailDriver = 'sendmail';
        $this -> _mailServer = array('host' => 'localhost');
    }
    
    function _dumpArray($arr, $nest) {
        $body = '';
        foreach ($arr as $name => $val) {
            $body .= str_repeat(chr(9), $nest) . '[' . $name . ']';
            if (is_object($val)) {
                $val = get_object_vars($val);
            }
            if (is_array($val)) {
                $body .= chr(10) . $this -> _dumpArray($val, $nest + 1);
            } else {
                $body .= chr(9) . $val . chr(10);
            }
        }
        return $body;
    }
    
    function addBcc($bcc) {
        foreach ($this -> _mailBcc as $haveBcc) {
            if ($haveBcc == $bcc) return true;
        }
        $this -> _mailBcc[] = $bcc;
        return true;
    }
    
    function addCc($cc) {
        foreach ($this -> _mailCc as $haveCc) {
            if ($haveCc == $cc) return true;
        }
        $this -> _mailCc[] = $cc;
        return true;
    }
    
    function deleteBcc($bcc) {
        foreach ($this -> _mailBcc as $key => $haveBcc) {
            if ($haveBcc == $bcc) {
                unset($this -> _mailBcc[$key]);
                return true;
            }
        }
        return false;
    }
    
    function deleteCc($cc) {
        foreach ($this -> _mailCc as $key => $haveCc) {
            if ($haveCc == $cc) {
                unset($this -> _mailCc[$key]);
                return true;
            }
        }
        return false;
    }
    
    function onDone() {
        return '';
    }
    
    function onError() {
        if ($this -> getDebug() && PEAR::isError($this -> _error)) {
            return $this -> _error -> toString();
        }
        return parent::onError();
    }
    
    /**
     * This function designed to make it easy for sub-classes to modify message
     * content without needing to deal with the mechanics of sending mail.
     */
    function onPrepareData() {
        $body = $this -> getContainer();
        $this -> _body = $body -> getFieldValue('comment');
        $route = $this -> getRoute();
        $stuff = $body -> getResults();
        unset($stuff['comment']);
        unset($stuff['route']);
        unset($stuff['verifier']);
        $this -> _body .= chr(10) . chr(10)
            . 'Route: ' . $route . chr(10)
            . 'Field Values:' . chr(10)
            . $this -> _dumpArray($stuff, 0);
        return true;
    }
    
    function onSave() {
        $headers = array();
        $headers['From'] = $this -> _mailFrom;
        $headers['Subject'] = $this -> _mailSubject;
        $to = $this -> getRoute();
        if (is_array($to)) {
            $headers['To'] = implode(',', $to);
        } else {
            $headers['To'] = $to;
        }
        $body = $this -> getContainer();
        $headers['Reply-To'] = $body -> getFieldValue('firstname')
            . ' ' . $body -> getFieldValue('lastname')
            . ' <' . $body -> getFieldValue('email') . '>';
        if (count($this -> _mailCc)) {
            $headers['Cc'] = implode(',', $this -> _mailCc);
        }
        if (count($this -> _mailBcc)) {
            $headers['Bcc'] = implode(',', $this -> _mailBcc);
        }
        //
        // Format and send the message
        //
        $mime = new Mail_mime();
        $mime -> setTXTBody($this -> _body);
        $body = $mime -> get();
        $mimeHeaders = $mime -> headers($headers);
        $mail = &Mail::factory($this -> _mailDriver, $this -> _mailServer);
        if (PEAR::isError($mail)) {
            return $mail;
        }
        $sendResult = $mail -> send($to, $mimeHeaders, $body);
        return $sendResult;
    }
    
    function setDriver($driver) {
        $this -> _mailDriver = $driver;
    }
    
    function setFrom($from) {
        $this -> _mailFrom = $from;
    }
    
    function setServer($server) {
        $this -> _mailServer = $server;
    }
    
    function setSubject($subj) {
        $this -> _mailSubject = $subj;
    }
    
}
