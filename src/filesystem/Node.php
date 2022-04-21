<?php
/**
 * Abivia PHP Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2018, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */

namespace Apl\Filesystem;

/**
 * Information on a node in the filesystem.
 */
class Node {
    public $fullName;
    public $name;
    public $relDir;
    public $relName;

    function __construct($fullName = '', $name = '', $relDir = '', $relName = '') {
        $this -> fullName = $fullName;
        $this -> name = $name;
        $this -> relDir = $relDir;
        $this -> relName = $relName;
    }

    /**
     * Create a node form a regular path.
     * @param type $path Path, treated as a directory if trailing slash present.
     * @return \Apl\Filesystem\Node
     */
    static function fromPath($path) {
        $node = new Node;
        $node -> fullName = $path;
        $node -> name = baseename($path);
        $node -> relDir = dirname($path);
        $node -> relName = $path;
        return $node;
    }

    /**
     * Normalize a file path to use the native OS delimiters.
     * @param string $path A file path
     * @return string path normalized to the native OS
     */
    static function native($path) {
        return preg_replace('![\\/]+!', DIRECTORY_SEPARATOR, $path);
    }

}
