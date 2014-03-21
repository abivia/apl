<?php
/**
 * Baseline test result generator for the lexer.
 *
 * Takes a .plex file as input, creates a parser, advances through all tokens,
 * storing the results in an array. The array is then JSON-encoded and output to
 * the second argument (or stdout if no argument provided).
 *
 * @author Alan Langford <jal@ambitonline.com>
 * @copyright 2007 Alan Langford
 * @license http://www.php.net/license/3_01.txt  PHP License 3.01
 * @package PHP_LexerGenerator
 * @version $Id: lexerBaselineGenerator.php 246683 2007-11-22 04:43:52Z instance $
 */

$base = dirname(__FILE__);
$base = str_replace('\\', '/', $base);
while (! class_exists('AP5L', false)) {
    $lib = $base . '/src/AP5L.php';
    if (file_exists($lib)) {
        require_once $lib;
        AP5L::install();
        break;
    }
    if ($base == dirname($base)) {
        echo 'Unable to find AP5L';
        exit(1);
    }
    $base = dirname($base);
}

require_once 'LexerOutputRecorder.php';

/**
 * Interpret arguments and invoke the recorder.
 *
 * @version @package_version@
 * @param array Command line arguments after stripping of PHP script, etc.
 */
function main($args) {

    if (count($args)) {
        $plexFile = array_shift($args);
    } else {
        echo 'Usage: infile [outfile]';
        return 1;
    }
    $gen = new LexerOutputRecorder();
    $states = $gen -> process('file', $plexFile);
    if (count($args)) {
        file_put_contents(array_shift($args), json_encode($states));
    } else {
        echo json_encode($states);
    }
    return 0;
}

$args = $argv;
array_shift($args);
exit(main($args));
