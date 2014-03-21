<?php
require_once '../../../src/AP5L.php';
AP5L::install();

$actual = AP5L_Css_PropertyDef::validateBorderColor4('border-color', 'inherit genes', '');
echo 'actual ';print_r($actual);

