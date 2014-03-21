<?php
/**
 * Title_here
 *
 * full_description_here
 *
 * @package package_name
 * @version $Id: $
 * @author Alan Langford <addr>
 */

require_once '../../../src/AP5L.php';
AP5L::install();

$parser = new AP5L_Css_Parser();

$buffer = '/* Based on the original Style Sheet for the fisubsilver v2 Theme
 */
/* The content of the posts (body of text) */
/* General page style */

/* begin suggest post */
.float-l, .bounce {
    float: left;
}

.form-suggest{
    height:200px;
    background:#DEE2D0;
    vertical-align: top;
 }

 @media print {
     .foo {
         background: #ffffff;
     }
 }
';

$parser -> parse($buffer);
//$parser -> parseUrl('old/fisubsilver.css');
print_r($parser -> sheet -> rules);