<?php

$regex = '/^\s*([+\-]?[0-9]+(?:\.[0-9]*)?)(deg|rad|grad)?(\s+|[^a-z0-9\-]|$)/i';

$tests = array(
    '0',
    '0 ',
    '0 foo',
    '0deg',
    '0deg.',
    '0de',
    '0degr',
    '0deg stuff',
    '0deg   more-stuff',
    '1deg',
    '1.1deg',
    '5rad',
    '5.6grad',
    '-2.5deg',
    '+3.7rad',
);

foreach ($tests as $test) {
    echo '"'. $test . '"' . chr(10);
    if (preg_match($regex, $test, $matches)) {
        foreach ($matches as $key => $match) {
            echo $key . ': "' . $match . '"  ';
        }
    } else {
        echo 'No match.';
    }
    echo chr(10);
}

