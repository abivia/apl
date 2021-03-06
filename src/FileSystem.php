<?php
/**
 * Abivia PHP Library
 *
 * @package Apl
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2011, Alan Langford
 * @author Alan Langford <alan.langford@abivia.com>
 */
namespace Apl;

/**
 * Filesystem, root class for file related classes
 */
class Filesystem extends Php\InflexibleObject {
    const TYPE_DIRECTORY = 1;
    const TYPE_FILE = 2;
}