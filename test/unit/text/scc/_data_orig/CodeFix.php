<?php

class CodeFix extends AP5L_Php_InflexibleObject {
    function process($path, $bPattern = '{dirname}/{filename}.bak.{extension}') {
        if ($bPattern != '') {
            $source = pathinfo($path);
            foreach ($source as $element => $value) {
                $bPattern = str_replace('{' . $element . '}', $value, $bPattern);
            }
            copy($path, $bPattern);
        }
        // Kills are lines we don't care about
        $kills = array(
            '_^\s*\*\s*@(access|private|protected|since|static)_'
        );
        // Maps define source transformations
        $maps = array(
            array('/\s*\(\s*/', '('),   // Change stuff ( ( x ) ) { to stuff((x ) ) {
            array('/\s+\)/', ')'),      // Change stuff ( ( x ) ) { to stuff ( ( x)) {
            array('/(for|foreach|if|switch|while)\(/', '\1 ('),     // Change if( to if (
            array('/\}\s*(else(\s*if)?)/', '} \1'),     // Nonstandard else spacing
            array('/(else(\s*if)?)\s*\{/', '\1 {'),     // Nonstandard else spacing
            array('_\s+,_', ' ,'),      // Whitespace before comma
            array('_\s+([\.\+\-\*!\|&/])?=_', ' \1='),  // Whitespace before assignment
            array('/=\s+/', '= '),                      // Whitespace after assignment
            array('/=&\s+/', '= &'),                    // Whitespace after reference assignment
            array('/=>\s+/', '=> '),                    // Whitespace after assignment
            array('/@(param|return)\s+([a-z0-9_\-$]+)\s+/i', '@\1 \2 '),
            array('/([a-z0-9_\)\]])\s*->/i', '\1 ->'),
            array('_case\s*\(\s*([^\)]+)\s*\)\s*:_i', 'case \1:'), // redundant () in case
            );
        // Convert tha maps into something easily iterated
        $transforms = array(array(), array());
        foreach ($maps as $pair) {
            $transforms[0][] = $pair[0];
            $transforms[1][] = $pair[1];
        }
        $buffer = file_get_contents($path);
        if ($buffer === '') {
            return;
        }
        // Normalize line endings and tabs
        $buffer = preg_replace('/ \t+/', ' ', $buffer);
        $buffer = str_replace(array("\r", "\t"), array("\n", '    '), $buffer);
        // Remove most empty lines
        $buffer = preg_replace('/\n+/', AP5L::LF, $buffer);
        // Do a line by line transform
        $buffer = explode(AP5L::LF, $buffer);
        $lastKey = -1;
        foreach ($buffer as $key => &$line) {
            $line = rtrim($line);
            if ($line == '') {
                unset($buffer[$key]);
                continue;
            }
            // Get rid of anything completely worthless.
            foreach ($kills as $killer) {
                if (preg_match($killer, $line)) {
                    unset($buffer[$key]);
                    continue;
                }
            }
            $line = preg_replace($transforms[0], $transforms[1], $line);
            // Blank line after function and class defs
            if ($line == '}' || $line == '    }') {
                $line .= AP5L::LF;
            }
            if ($lastKey != -1) {
                $trimmed = trim($line);
                $prevLine = $buffer[$lastKey];
                // Move dangling block opens and the like
                if (
                    in_array(
                        $trimmed,
                        array(
                            '{', 'else', 'elseif', 'else if',
                            'else {', // 'elseif {', 'else if {'
                        )
                    )
                ) {
                    // Look for end of line comment
                    $comment = '';
                    if (($posn = strrpos($prevLine, '//')) === false ) {
                        $posn = strrpos($prevLine, '/*');
                    }
                    if ($posn !== false) {
                        $comment = substr($prevLine, $posn);
                        $prevLine = substr($prevLine, 0, $posn);
                        $pad = strlen($prevLine);
                        $prevLine = rtrim($prevLine);
                        $comment = str_repeat(' ', $pad - strlen($prevLine)) . $comment;
                    }
                    $buffer[$lastKey] = $prevLine . ' ' . $trimmed . $comment;
                    unset($buffer[$key]);
                    continue;
                }
                // Setup for blank line inserts
                $prevTrim = trim($prevLine);
                $prevNotBlank = $prevTrim != ''
                    && $prevLine[strlen($prevLine) - 1] != AP5L::LF;
                // Insert blank lines in front of all but first docblock
                if (
                    substr($trimmed, 0, 3) == '/**'
                    && $lastKey > 3
                    && $prevNotBlank
                ) {
                    $line = AP5L::LF . $line;
                    $prevNotBlank = false;
                }
                if (
                    preg_match('/^\s*((abstract|final)\s+)*class/', $trimmed)
                    && $prevNotBlank
                    && strpos('*/', $prevTrim) === false
                ) {
                    $line = AP5L::LF . $line;
                    $prevNotBlank = false;
                }
                if (
                    preg_match('/^\s*((abstract|final|static|public|private|protected)\s+)*function/', $trimmed)
                    && $prevNotBlank
                    && strpos('*/', $prevTrim) === false
                ) {
                    $line = AP5L::LF . $line;
                    $prevNotBlank = false;
                }
                if (
                    substr($prevTrim, 0, 7) == 'global '
                    && substr($trimmed, 0, 7) != 'global '
                ) {
                    $line = AP5L::LF . $line;
                    $prevNotBlank = false;
                }
            }
            if (isset($buffer[$key])) {
                $lastKey = $key;
            }
        }
        // Kill trailing blank lines
        while (count($buffer) && trim(end($buffer)) == '') {
            unset($buffer[key($buffer)]);
        }
        // Kill trailing closing tag
        if ($lastKey != -1 && trim($buffer[$lastKey]) == '?>') {
            unset($buffer[$lastKey]);
        }
        $buffer = implode(AP5L::LF, $buffer) . AP5L::LF;
        file_put_contents($path, $buffer);
    }

    /**
     * Process a directory tree.
     *
     * @param string The root directory to scan.
     * @return unknown_type
     */
    function processDir($baseDir) {
        // Get the global event dispatcher
        $disp = AP5L_Event_Dispatcher::getInstance();
        $disp -> setOption('queue', false);
        // Listen to add directory and end directory events
        $disp -> listen($this);
        // Create a directory listing, set the dispatcher
        $listing = new AP5L_FileSystem_Listing();
        $listing -> setDispatcher($disp);
        // Get the listing
        $listing -> execute(
            $baseDir,
            array(
                'filter' => AP5L_FileSystem_Listing::TYPE_FILE,
                //'directories' => 'first'
            )
        );
    }

    /**
     * Receive a file event.
     *
     * This method uses events from the directory walk to process matching files.
     *
     * @param AP5L_Event_Notification The event object.
     * @return unknown_type
     */
    function update(AP5L_Event_Notification $subject) {
        switch ($subject -> getName()) {
            case 'addFile': {
                $info = $subject -> getInfo();
                $name = $info['name'];
                if (
                    preg_match('/\.(inc|php)$/i', $name)
                    && !preg_match('/\.bak\.(inc|php)$/i', $name)
                ) {
                    echo $info['fullname'] . AP5L::LF;
                    $this -> process($info['fullname'], '');
                    //print_r($info);
                }
            }
            break;

        }
    }

}

