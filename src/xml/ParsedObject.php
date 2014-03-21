<?php
/**
 * XML Parsing support class.
 *
 * This is a base class that provides methods to support the ParserCore.
 *
 * @package AP5L
 * @subpackage Xml
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ParsedObject.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * This is essentially an interface definition for objects that can be used by
 * an XmlParserCore, but since some methods are optional it is implemented as a
 * class.
 */
class AP5L_Xml_ParsedObject {
    /**
     * Element close handler.
     *
     * This function is called once an element has been closed and the
     * constructed object is available.
     *
     * @param AP5L_Xml_ParserCore The parser object.
     */
    public function xmlElementClose($parser) {
    }

    /**
     * Element open handler.
     *
     * This function is called when an element open tag for this object type tag
     * is encountered. It is also called when new non-object tags inside the
     * current object are encountered.
     *
     * @param AP5L_Xml_ParserCore The parser object.
     */
    public function xmlElementOpen($parser) {
    }

    /**
     * Element pre-close handler.
     *
     * This function is called when an element close tag has been constructed
     * and before the object is complete. It is most useful for capturing CDATA
     * contained inside the element.
     *
     * @param AP5L_Xml_ParserCore The parser object.
     */
    public function xmlElementPreClose($parser) {
    }

    /**
     * Manufacture an instance of the class.
     *
     * @return Object An instance of the host class.
     */
    static public function &xmlFactory() {
        throw new AP5L_Exception('Must override xmlFactory.');
    }

    /**
     * Register tags and classes associated with this object.
     *
     * This function should contain one or more calls to the parser's
     * registerElement method {@see AP5L_Xml_ParserCore::registerElement}.
     *
     * @param AP5L_Xml_ParserCore The parser object.
     */
    static public function xmlRegister(&$parser) {
    }

}?>
