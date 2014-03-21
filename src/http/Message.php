<?php
/**
 * HTTP message.
 *
 * @package AP5L
 * @subpackage Http
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Message.php 100 2011-03-21 18:26:21Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * HTTP message class
 */
class AP5L_Http_Message {
    var $body = '';
    var $headers = array();
    var $start = '';

    /**
     * Decode a compressed message body.
     *
     * Only handles gzip, but designed to handle compound / other methods.
     *
     * @param string The compression method(s) to be undone.
     * @return string Uncompressed buffer.
     */
    protected function _decode($method) {
        $len = strlen($this -> body);
        if ($method != 'gzip') {
            throw new AP5L_Http_Exception($method . ' compression not supported.');
        }
        // Look for a compression signature and sufficient header info
        if ($len < 18 || strcmp(substr($this -> body, 0, 2), "\x1f\x8b")) {
            return $this -> body;
        }
        $mode = ord(substr($this -> body, 2, 1));
        if ($mode != 8) {
            throw new AP5L_Http_Exception('Compression mode ' . $mode . ' unknown.');
        }
        $flags = ord(substr($this -> body, 3, 1));
        if ($flags & 224) {
            throw new AP5L_Http_Exception('Reserved bits set (flags 0x' . hexdec($flags) . ').');
        }

        // Parse through the header. A meaningful value for dataLen is calculated below.
        $hLen = 10;
        $dataLen = true;
        // Account for extra fields
        if ($flags & 4) {
            $dataLen = $len - $hLen - 2 - 8;
            if ($dataLen >= 0) {
                $addLen = unpack('v', substr($this -> body, 10, 2));
                $dataLen -= $addLen[1];
                if ($dataLen >= 0) {
                    $hLen += $addLen[1] + 2;
                }
            }
        }
        // There shouldn't be a file name, but if there is, skip it.
        if ($dataLen >= 0 && ($flags & 8)) {
            $dataLen = $len - $hLen - 1 - 8;
            if ($dataLen >= 0) {
                if (($vLen = strpos(substr($this -> body, $hLen), chr(0))) === false) {
                    throw new AP5L_Http_Exception('Filename not terminated.');
                }
                $dataLen -= $vLen;
                if ($dataLen >= 0) {
                    $hLen += $vLen + 1;
                }
            }
        }
        // A comment should not be present either, but if it is, skip it.
        if ($dataLen >= 0 && ($flags & 16)) {
            $dataLen = $len - $hLen - 1 - 8;
            if ($dataLen >= 0) {
                if (($vLen = strpos(substr($this -> body, $hLen), chr(0))) === false) {
                    throw new AP5L_Http_Exception('Comment not terminated.');
                }
                $dataLen -= $vLen;
                if ($dataLen >= 0) {
                    $hLen += $vLen + 1;
                }
            }
        }
        // Check for a CRC in the header.
        if ($dataLen >= 0 && ($flags & 1)) {
            $dataLen = $len - $hLen - 2 - 8;
            if ($dataLen >= 0) {
                $crcCalc = 0xffff & crc32(substr($this -> body, 0, $hLen));
                $crcData = unpack('v', substr($this -> body, $hLen, 2));
                if ($crcCalc != $crcData[1]) {
                    throw new AP5L_Http_Exception('Header CRC failure.');
                }
                $hLen += 2;
            }
        }
        if ($dataLen < 0) {
            throw new AP5L_Http_Exception('Insufficient data in gzip stream.');
        }
        // Trailer contains CRC and unpacked size.
        $tmp = unpack('V2', substr($this -> body, -8));
        $dataCrc  = $tmp[1];
        $dataSize = $tmp[2];

        // Now we know where to inflate!
        $inflated = @gzinflate(substr($this -> body, $hLen, -8), $dataSize);
        // Verify that we got what we expected.
        if ($inflated === false) {
            throw new AP5L_Http_Exception('Error unpacking gzip stream.');
        }
        if ($dataSize != strlen($inflated)) {
            throw new AP5L_Http_Exception('Unpacked data is wrong size.');
        }
        if ((0xffffffff & $dataCrc) != (0xffffffff & crc32($inflated))) {
            throw new AP5L_Http_Exception('Data CRC failure..');
        }
        return $inflated;
    }

    /**
     * Rudimentary string conversion, print the body.
     */
    function __toString() {
        return $this -> getBody();
    }

    /**
     * Get the message body even if it's compressed
     */
    function getBody() {
        if (isset($this -> headers['content-encoding'])){
            return $this -> _decode($this -> headers['content-encoding']);
        }
        return $this -> body;
    }

}
