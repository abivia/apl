<?php
/**
 * Abivia PHP5 Library
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2009, Alan Langford
 * @version $Id: $
 * @author Alan Langford <alan.langford@abivia.com>
 */


/**
 * Apply a unified diff style patch with some room for context shift
 *
 */
class AP5L_Text_Scc_Patch_Udiff extends AP5L_Text {

    /**
     * The host patch manager.
     *
     * @var AP5L_Text_Scc_Patch
     */
    protected $_host;

    /**
     * Parse a set of patch chunks into usable data structures.
     *
     * @param array Patch chunks, as an array of lines
     * @return array
     */
    protected function _chunkParser($lines) {
        // Get rid of empty trailing lines
        while (end($lines) === '') {
            array_pop($lines);
        }
        $segments = array();
        $segment = array();
        foreach ($lines as $line) {
            if (substr($line, 0, 2) == '@@') {
                if (!empty($segment)) {
                    $segments[] = $segment;
                }
                $segment = array();
            }
            $segment[] = $line;
        }
        if (!empty($segment)) {
            $segments[] = $segment;
        }
        $parsed = array();
        foreach ($segments as $segment) {
            $line = array_shift($segment);
            if (substr($line, 0, 2) != '@@' || substr($line, -2, 2) != '@@') {
                throw new AP5L_Text_Scc_Exception(
                    'Malformed patch: bad @@ syntax. ' . $line,
                    AP5L_Text_Scc_Exception::ABORT_JOB
                );
            }
            $work = trim(substr($line, 2, -2));
            $work = explode(' ', $work);
            /*
             * There should only have been a single space, but grab the
             * endpoints just in case.
             */
            $old = explode(',', reset($work));
            if (count($old) == 1) {
                $old[1] = 1;
            }
            $new = explode(',', end($work));
            if (count($new) == 1) {
                $new[1] = 1;
            }
            if (count($old) != 2 || count($new) != 2) {
                throw new AP5L_Text_Scc_Exception(
                    'Malformed patch: bad @@ syntax. ' . $line,
                    AP5L_Text_Scc_Exception::ABORT_JOB
                );
            }
            $chunk = array(
                'old' => array(
                    'start' => (int) -$old[0],
                    'size' => (int) $old[1],
                    'lines' => array()
                ),
                'new' => array(
                    'start' => (int) $new[0],
                    'size' => (int) $new[1],
                    'lines' => array()
                ),
            );
            foreach ($segment as $line) {
                switch ($line[0]) {
                    case ' ': {
                        // Add the line to both old and new
                        $chunk['old']['lines'][] = substr($line, 1);
                        $chunk['new']['lines'][] = substr($line, 1);
                    }
                    break;

                    case '-': {
                        // Add the line to old
                        $chunk['old']['lines'][] = substr($line, 1);
                    }
                    break;

                    case '+': {
                        // Add the line to new
                        $chunk['new']['lines'][] = substr($line, 1);
                    }
                    break;

                    case '\\': {
                        // treat as comment
                    }
                    break;

                    default: {
                        throw new AP5L_Text_Scc_Exception(
                            'Malformed patch: unexpected "' . $line[0] . '" in column 1.',
                            AP5L_Text_Scc_Exception::ABORT_JOB
                        );
                    }
                    break;
                }
            }
            $parsed[] = $chunk;
        }
        // Verify chunk sizes
        foreach ($parsed as $chunk) {
            foreach ($chunk as $state => $seg) {
                if ($seg['size'] != count($seg['lines'])) {
                    throw new AP5L_Text_Scc_Exception(
                        'Malformed patch: expected ' . $seg['size'] . ' lines in '
                        . $state . ' segment, got ' . count($seg['lines']) . '.',
                        AP5L_Text_Scc_Exception::ABORT_JOB
                    );
                }
            }
        }
        return $parsed;
    }

    /**
     * Apply a set of patch chunks to a buffer.
     *
     * @param string The original source.
     * @param string|array One or more chunks, each starting with a range.
     * @return string The buffer after application of the patch.
     */
    function applyBuffer($buffer, $patchChunks) {
        // Figure out what the source file is using for a line end
        $eol = self::sniffEol($buffer);
        // Normalize line endings
        if ($eol != "\n") {
            $buffer = str_replace($eol, "\n", $buffer);
        }
        $buffer = explode("\n", $buffer);
        if (is_string($patchChunks)) {
            // Figure out what the patch is using for a line end
            $patchEol = self::sniffEol($patchChunks);
            // Normalize line endings
            if ($patchEol != "\n") {
                $patchChunks = str_replace($patchEol, "\n", $patchChunks);
            }
            $patchChunks = explode("\n", $patchChunks);
        }
        $chunks = self::_chunkParser($patchChunks);
        // Now try applying the chunks
        $fuzziness = $this -> _host -> getFuzziness();
        $stateFrom = $this -> _host -> getStateFrom();
        $stateTo = $this -> _host -> getStateTo();
        $scope = abs($fuzziness);
        $lineDelta = 0;
        $this -> _host -> onPatchStart($buffer, $chunks);
        foreach ($chunks as $chunk) {
            $match = -1;
            $actualStart = -1;
            $limit = $fuzziness < 0 ? count($buffer) : $scope;
            $scanLow = true;
            $scanHigh = true;
            // The context
            for ($shift = 0; $shift <= $scope; ++$shift) {
                // Look at offset 0 and higher
                $actualStart = $this -> _host -> contextMatch(
                    $buffer, $chunk, $lineDelta + $shift
                );
                if ($scanHigh && $actualStart >= 0) {
                    break;
                }
                if ($actualStart == -2) {
                    // Stop scanning forward if out of range
                    $scanHigh = false;
                    $actualStart = -1;
                }
                // Look at negative offsets
                if (
                    $shift && $scanLow
                    && ($actualStart = $this -> _host -> contextMatch($buffer, $chunk, -$shift)) >= 0
                ) {
                    break;
                }
                // Stop scanning backwards if out of range
                if ($actualStart == -2) {
                    $scanLow = false;
                    $actualStart = -1;
                }
                // If we've stopped scanning completely get out of the loop
                if (!$scanLow && !$scanHigh) {
                    break;
                }
            }
            if ($actualStart < 0) {
                // This may just mean the patch is already in place.
                throw new AP5L_Text_Scc_Exception(
                    'Unable to find patch point (start line '
                    . $chunk[$this -> _host -> getStateFrom()]['start'] . ').',
                    AP5L_Text_Scc_Exception::ABORT_PATCH
                );
            }
            // Replace old with new.
            array_splice(
                $buffer,
                $actualStart,
                $chunk[$stateFrom]['size'],
                $chunk[$stateTo]['lines']
            );
            $lineDelta += $chunk[$stateTo]['size'] - $chunk[$stateFrom]['size'];
        }
        $this -> _host -> onPatchDone($buffer, $chunks);
        // re-assemble and return the buffer
        return implode($eol, $buffer); // FIXME: [no] eol case
    }

    /**
     * Parse a patch into segments that affect individual files.
     *
     * @param array Line by line patch data.
     * @return array List of patches by file.
     */
    function patchParser($source) {
        $patchFiles = array();
        /*
         * Break the patch up into segments, one segment per file.
         */
        $fileData = array('headers' => array(), 'patch' => array());
        $patchData = false;
        $inHeaders = false;
        foreach ($source as $line) {
            if (strlen($line) && strpos(' +-@', $line[0]) === false) {
                if ($patchData) {
                    $patchFiles[] = $fileData;
                    $patchData = false;
                    $fileData = array('headers' => array(), 'patch' => array());
                }
                if (substr($line, 0, 10) != '==========') {
                    $inHeaders = true;

                    if (($posn = strpos($line, ':')) !== false) {
                        $label = substr($line, 0, $posn);
                        $fileData['headers'][$label] = trim(substr($line, $posn + 1));
                    }
                }
            } else {
                $inHeaders = false;
                $work = substr($line, 0, 4);
                if ($work == '--- ' || $work == '+++ ') {
                    if ($patchData) {
                        $patchFiles[] = $fileData;
                        $patchData = false;
                        $fileData = array('headers' => array(), 'patch' => array());
                    }
                    if ($work == '--- ') {
                        $fileData['old'] = explode(chr(9), substr($line, 4));
                    } else {
                        $fileData['new'] = explode(chr(9), substr($line, 4));
                    }
                } else {
                    $patchData = true;
                    $fileData['patch'][] = $line;
                }
            }
        }
        if ($patchData) {
            $patchFiles[] = $fileData;
        }
        return $patchFiles;
    }

    /**
     * Set the host patch control object
     *
     * @param AP5L_Text_Scc_Patch The host patch manager.
     */
    function setHost(&$host) {
        $this -> _host = $host;
    }

}
