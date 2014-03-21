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
 * Apply a patch with some room for context shift
 *
 */
class AP5L_Text_Scc_Patch extends AP5L_Text {
    /**
     * Atomic change flag. When set, a patch must either succeed or fail on
     * all files named in the patch.
     *
     * @var boolean
     */
    protected $_atomic = true;

    /**
     * Base path relative to the patch file.
     *
     * @var string
     */
    protected $_basePath;

    /**
     * Reference to the patch format processing object.
     *
     * @var object
     */
    protected $_formatPlugin;

    /**
     * The number of lines of "drift" we'll accept in a context match, n<0
     * means try anywhere.
     *
     * @var int
     */
    protected $_fuzziness = 3;

    /**
     * If set, leading space is significant.
     *
     * @var boolean
     */
    protected $_outerSpaceStrict = true;

    /**
     * Information on the current patch file.
     *
     * @var object
     */
    protected $_patchFile;

    /**
     * The patch broken up into individual files.
     *
     * @var array
     */
    protected $_patchFiles;

    /**
     * Reverse flag. Set if we're removing a patch.
     *
     * @var boolean
     */
    protected $_reverse;

    /**
     * Which part of the patch we're transforming from.
     *
     * @var string
     */
    protected $_stateFrom = 'old';

    /**
     * Which part of the patch we're transforming to.
     *
     * @var string
     */
    protected $_stateTo = 'new';

    /**
     * Determine if lines are the same for context matching.
     *
     * @param string The first line.
     * @param string The second line.
     * @return boolean True if lines are considered a match.
     */
    protected function _lineMatch($left, $right) {
        if (!$this -> _outerSpaceStrict) {
            $left = trim($left);
            $right = trim($right);
        }
        return $left == $right;
    }

    /**
     * Generate a message (stub).
     *
     * @param string The message text.
     */
    protected function _message($message) {
    }

    /**
     * Context match a patch chunk.
     *
     * @param array Source lines.
     * @param array A patch chunk.
     * @param int Number of lines to shift the start of the match
     * @return int Matching position in the buffer if found, -2 if outside the
     * range of the buffer, -1 if in range but no text match.
     */
    function contextMatch($buffer, $chunk, $shift = 0) {
        $start = $chunk[$this -> _stateFrom]['start'] + $shift - 1;
        for ($ind = 0; $ind < $chunk[$this -> _stateFrom]['size']; ++$ind) {
            $checkLine = $start + $ind;
            if (!isset($buffer[$checkLine]) || !isset($chunk[$this -> _stateFrom]['lines'][$ind])) {
                return -2;
            }
            if (
                !$this -> _lineMatch(
                    $buffer[$checkLine],
                    $chunk[$this -> _stateFrom]['lines'][$ind]
                )
            ) {
                return -1;
            }
        }
        return $start;
    }

    /**
     * Create a patch object
     *
     * @param string|object Patch file format (string) or patch parser (object).
     * @param string Optional class to instantiate.
     */
    static function &factory($format = 'Udiff', $myClass = '') {
        if ($myClass == '') {
            $myClass = __CLASS__;
        }
        $object = new $myClass;
        if (is_string($format)) {
            // Check for a user class first
            $formatClass = $format;
            if (! class_exists($formatClass, true)) {
                // Now check for a provided class
                $formatClass = __CLASS__ . '_' . $format;
                if (! class_exists($formatClass, true)) {
                    // Last try, see if the caller messed up case
                    $formatClass = __CLASS__ . '_' . ucfirst(strtolower($format));
                    if (! class_exists($formatClass, true)) {
                        throw new AP5L_Text_Scc_Exception(
                            'Unable to load patch format class: ' . $format,
                            AP5L_Text_Scc_Exception::ABORT_JOB
                        );
                    }
                }
            }
            $plugin = new $formatClass();
        } else {
            $plugin = $format;
        }
        $object -> setFormatPlugin($plugin);
        return $object;
    }

    /**
     * Get the state of the atomic change flag.
     *
     * @return boolean
     */
    function getAtomic() {
        return $this -> _atomic;
    }

    /**
     * Get the current base path.
     *
     * @return string
     */
    function getBasePath() {
        return $this -> _basePath;
    }

    /**
     * Get the size of the allowable shift in patch window, in lines.
     *
     * @return int
     */
    function getFuzziness() {
        return $this -> _fuzziness;
    }

    /**
     * Get the patch direction (reverse is set if the patch is being undone).
     */
    function getReverse() {
        return $this -> _stateFrom != 'old';
    }

    /**
     * Get the name of the state we're transformaing from.
     *
     * @return string State name.
     */
    function getStateFrom() {
        return $this -> _stateFrom;
    }

    /**
     * Get the name of the state we're transformaing to.
     *
     * @return string State name.
     */
    function getStateTo() {
        return $this -> _stateTo;
    }

    /**
     * Get the ignore leading/trailing whitespace flag.
     *
     * @return boolean True when outer whitespace is ignored.
     */
    function getTrimOuterSpace() {
        return $this -> _outerSpaceStrict;
    }

    /**
     * Event handler for a missing file.
     *
     * This event provides an opportunity to copy in a base file from
     * anothher source before an exception is thrown.
     *
     * @param string Relative path of the missing file.
     * @return void
     */
    function onFileMissing($source) {
    }

    /**
     * Event handler for the completion of patch application.
     *
     * @param array File to be patched, as an array of lines.
     * @param array List of patch chunks to be applied
     * @return boolean True if the patch should proceed.
     */
    function onPatchDone(&$buffer, &$patchChunks) {
        return true;
    }

    /**
     * Event handler for the start of a patch parse.
     *
     * @param array Line by line patch data.
     * @return boolean True if the patch should proceed.
     */
    function onPatchParse(&$patch) {
        return true;
    }

    /**
     * Event handler for the start of patch application.
     *
     * @param array File to be patched, as an array of lines.
     * @param array List of patch chunks to be applied
     * @return boolean True if the patch should proceed.
     */
    function onPatchStart(&$buffer, &$patchChunks) {
        return true;
    }

    /**
     * Apply a patch set.
     *
     * This method applies a multi-file patch to a set of files. In the event of
     * a serious error, such as an error in the patch file, no files are modified.
     * If the patch supplies a name and the type of file being modified is known,
     * then a comment indicating that the patch has been applied is written into
     * the file header.
     * A patch can be partially applied when
     *
     * @param string A patch in universal diff format.
     * @throws AP5L_Text_Scc_Exception On unrecoverable I/O error or bad
     * patch format.
     * @return AP5L_Text_Scc_Patch The current object.
     */
    function &patchBuffer($patch) {
        if (is_string($patch)) {
            // Figure out what the patch is using for a line end
            $patchEol = self::sniffEol($patch);
            // Normalize line endings
            if ($patchEol != "\n") {
                $patch = str_replace($patchEol, "\n", $patch);
            }
            $patch = explode("\n", $patch);
        } elseif (!is_array($patch)) {
            throw new AP5L_Text_Scc_Exception(
                'Patch must be string or array of strings.',
                AP5L_Text_Scc_Exception::ABORT_JOB
            );
        }
        if (!$this -> onPatchParse($patch)) {
            /*
             * A patch can be excluded without failure. If an error
             * ocurrs the event handler should throw the exception.
             */
            return $this;
        }
        $this -> _patchFiles = $this -> _formatPlugin -> patchParser($patch);
        /*
         * File level parsing is done, now go get the files and patch them
         */
        foreach ($this -> _patchFiles as &$fileData) {
            try {
                $this -> _patchFile = $fileData;
                $source = $this -> _basePath . $fileData[$this -> _stateFrom][0];
                if (!file_exists($source)) {
                    $this -> onFileMissing($fileData[$this -> _stateFrom][0]);
                }
                if (!file_exists($source)) {
                    throw new AP5L_Text_Scc_Exception(
                        'File not found: ' . $source,
                        AP5L_Text_Scc_Exception::ABORT_FILE
                    );
                }
                $fileData['source'] = @file_get_contents($source);
                $fileData['patched'] = $this -> _formatPlugin -> applyBuffer(
                    $fileData['source'], $fileData['patch']
                );
            } catch (AP5L_Text_Scc_Exception $e) {
                if ($e -> getCode() == AP5L_Text_Scc_Exception::ABORT_JOB) {
                    throw $e;
                }
                $this -> _message($e -> getMessage() . ' ' . $fileData[$this -> _stateFrom][0]);
                if ($this -> _atomic) {
                    $this -> _message('Atomic patch mode on. Patch aborted.');
                    throw new AP5L_Text_Scc_Exception(
                        $e -> getMessage() . ' Atomic patch mode on. Patch aborted.',
                        $e -> getCode()
                    );
                }
                if ($e -> getCode() == AP5L_Text_Scc_Exception::ABORT_PATCH) {
                    throw $e;
                }
            }
        }
        /*
         * If we got here without an exception, then all patches were
         * either already in place or applied sucessfully, so now we
         * can try to write all changes out.
         */
        $commit = array();
        try {
            foreach ($this -> _patchFiles as $outFile) {
                if (isset($outFile['patched'])) {
                    // Get a backup of the modified file
                    $outFile['undo'] = file_get_contents(
                        $this -> _basePath . $outFile[$this -> _stateTo][0]
                    );
                    // Write the new file
                    file_put_contents(
                        $this -> _basePath . $outFile[$this -> _stateTo][0],
                        $outFile['patched']
                    );
                    $commit[] = $outFile;
                }
            }
        } catch (Exception $e) {
            // Probably hit a permissions error. Undo.
            foreach ($commit as $fileData) {
                file_put_contents(
                    $this -> _basePath . $fileData[$this -> _stateTo][0],
                    $fileData['undo']
                );
            }
            throw $e;
        }
        return $this;
    }

    /**
     * Apply a patch set from a file. See {@see patchBuffer()}
     *
     * @param string Name of a file containing a patch in universal diff format.
     * @return AP5L_Text_Scc_Patch The current object.
     */
    function &patchFile($path) {
        $this -> patchBuffer(file_get_contents($path));
        return $this;
    }

    /**
     * Set the state of the atomic change flag.
     *
     * @param boolean True if changes should be atomic.
     * @return AP5L_Text_Scc_Patch The current object.
     */
    function &setAtomic($isAtomic) {
        $this -> _atomic = $isAtomic;
        return $this;
    }

    /**
     * Set the base path to files to be patched.
     *
     * @param string The directory where files are located.
     * @return AP5L_Text_Scc_Patch The current object.
     */
    function &setBasePath($path) {
        $this -> _basePath = AP5L_Filesystem_Directory::clean($path);
        return $this;
    }

    /**
     * Set the patch parser.
     *
     * @param AP5L_Text_Scc_Patch
     */
    function setFormatPlugin($plugin) {
        $plugin -> setHost($this);
        $this -> _formatPlugin = $plugin;
    }

    /**
     * Set the size of the allowable shift in patch window, in lines.
     *
     * @param int The number of lines to scan (forwards and backwards) to
     * locate where the patch should be applied.
     * @return AP5L_Text_Scc_Patch The current object.
     */
    function &setFuzziness($factor) {
        $this -> _fuzziness = $factor;
        return $this;
    }

    /**
     * Set the patch direction (reverse is set if the patch is being undone).
     */
    function setReverse($unPatch) {
        $this -> _reverse = $unPatch;
        if ($unPatch) {
            $this -> _stateFrom = 'new';
            $this -> _stateTo = 'old';
        } else {
            $this -> _stateFrom = 'old';
            $this -> _stateTo = 'new';
        }
    }

    /**
     * Set the ignore leading/trailing whitespace flag.
     *
     * @param boolean True when outer whitespace is ignored.
     * @return AP5L_Text_Scc_Patch The current object.
     */
    function &setTrimOuterSpace($strict) {
        $this -> _outerSpaceStrict = $strict;
        return $this;
    }

}
