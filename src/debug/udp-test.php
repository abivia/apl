<?php
/**
 * Abivia PHP5 Library
 *
 *
 * @package AP5L
 * @version $Id: udp-test.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <addr>
 */

/**
 *
 */
require_once '../AP5L.php';

AP5L::install();

$dl = AP5L_Debug_Udp::getInstance();
$dl -> setState('', true);
$dl -> writeln('test output');

?>