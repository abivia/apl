<?php
/**
 * @package AP5L
 */

/**
 * 
 */
require_once '../AP5L.php';

AP5L::install();

$dl = new AP5L_Debug_Listener;
if ($argc > 1) {
    $port = (int) $argv[1];
    if (! $port) {
        echo 'Bad port.';
        exit;
    }
    $dl -> dump($port);
} else {
    $dl -> dump();
}
?>