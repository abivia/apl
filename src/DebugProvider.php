<?php
/**
 * Abivia PHP5 Library
 *
 * @package AP5L
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2006-2008, Alan Langford
 * @version $Id: DebugProvider.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * Interface for generators of diagnostic output
 */
interface AP5L_DebugProvider {
    /**
     * Send a backtrace to the debug output stream.
     *
     * @param string|int A scope identifier (string) or scope handle (integer).
     * @param array Options.
     */
    function backtrace($handle = null, $options = array());

    /**
     * Dump any buffered output to the stream.
     */
    function flush();

    /**
     * Release a configuration handle.
     *
     * @param int A scope handle allocated by {@see getHandle()}
     */
    function freeHandle($handle);

    /**
     * Get a configuration handle.
     *
     * @param string A scope identifier.
     * @return int A handle that corresponds to the setting for the passed key.
     */
    function getHandle($key = '');

    /**
     * Get the setting for a particluar state.
     *
     * If the explicit setting is not set, the closest matching setting in the
     * evaluation hierarchy is returned.
     */
    function getState($key = null);

    /**
     * See if debug output is enabled.
     *
     * This method is useful when there's significant computation required to
     * create a diagnostic string, by allowing a branch around it if it's not
     * required.
     *
     * @param int|string An optional debugger handle or scope identifier. See
     * {@see write()}.
     */
    function isEnabled($handle = null);

    /**
     * Configure the debugger.
     *
     * @param string A scope identifier.
     * @param mixed The corresponding value. Typically boolean, but it can be
     * anything.
     */
    function setState($key, $value);

    /**
     * Clear a state setting.
     *
     * @param string A scope identifier.
     */
    function unsetState($key);

    /**
     * Write a string to the diagnostic stream, if it's enabled for this handle.
     *
     * @param string The string to write.
     * @param string|int Optional scope identifier (string) or scope handle
     * (integer). If not provided, the namespace, class, and method of the
     * calling routine is used.
     * @param array Options.
     */
    function write($data, $handle = null, $options = array());

    /**
     * Write a string followed by a line break, if it's enabled for this handle.
     *
     * @param string The string to write.
     * @param string|int Optional scope identifier (string) or scope handle
     * (integer). If not provided, the namespace, class, and method of the
     * calling routine is used.
     * @param array Options.
     */
    function writeln($data, $handle = null, $options = array());

}

