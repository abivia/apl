<?php
/**
 * Test code (convert to unit test)
 *
 * @package AP5L
 * @subpackage Sql
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: mysql.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 */

// copy the arguments and remove the script name
$args = $argv;
array_shift($args);
// First generate the parser unless explicitly told not to
if (count($args) && $args[0] == 'nogen') {
    array_shift($args);
} else {
    require_once 'PHP/LexerGenerator.php';
    $x = new PHP_LexerGenerator('../mysql/Mysql.plex');
}
require_once '../mysql/Mysql.php';

if (count($args)) {
    $data = file_get_contents(array_shift($args));
} else {
    $data = 'SELECT x.*, `y.foo`, y.bar FROM `table_x` AS x INNER JOIN table_y as y'
        . ' ON x.key=y.key';
}
$lex = new Sql_Parser_Mysql($data);
while ($lex -> yylex()) {
    echo 'advance: "' . $lex -> token . '" st=' . $lex -> getState() . chr(10);
    var_dump($lex -> value);
}
?>
