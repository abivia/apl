<?php
/**
 * Generic support for XML.
 * 
 * @package AP5L
 * @subpackage Xml
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: Lib.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * XmlLib support functions for handling of XML
 */
class AP5L_Xml_Lib {
    /**
     * Statically callable conversion to XML-friendly string
     */
    static function toXmlString($data) {
        // Escape the characters that can cause problems with the parser.
        // Less than (<) is obvious; & causes "Hi&amp;Lo" to be sent as "Hi&amp;amp;Lo",
        // thus pushing entity resolution to the receiver application.
        // Note: the order of translation is important or < gets translated to &amp;lt;
        return str_replace(array('&', '<'), array('&amp;', '&lt;'), $data);
    }

}
?>
