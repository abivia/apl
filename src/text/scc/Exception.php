<?php
/**
 * AP5L The Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007-2008, Alan Langford
 * @version $Id: Store.php 61 2008-06-01 17:20:30Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

/**
 * General exceptions thrown by text classes.
 */
class AP5L_Text_Scc_Exception extends AP5L_Text_Exception {
    const ABORT_FILE = 1;
    const ABORT_PATCH = 2;
    const ABORT_JOB = 3;
}
