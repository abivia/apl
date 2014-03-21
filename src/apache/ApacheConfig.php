<?php
/**
 * Support for managing Apache configuration files
 * 
 * @package AP5L
 * @subpackage Apache
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ApacheConfig.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 * 
 * @todo Update to PHP5
 */

Class ApacheBaseConfigNode {
    var $directive;
    var $expr;                          // Expression passed to directive (when parsed)
    var $parent = null;
    var $sequence;
    var $sourceLine;                    // array(incude file id, line)
    var $text;
    
    function __construct($parent = null) {
        $this -> parent = $parent;
    }
    
    function ApacheBaseConfigNode($parent = null) {
        $this -> __construct($parent);
    }

}

class ApacheGroupConfigNode extends ApacheBaseConfigNode {
    var $commentCount;                  // The number of sub-nodes that are comments
    var $exprValue;                     // Expression result when evaluated
    var $loadValue;                     // For modules; value at this point (true=defined)
    var $scoped = true;                 // set when this directive is scope limiting
    var $subNodes = array();
    
    function __construct($parent = null) {
        parent::__construct($parent);
        $this -> commentCount = 0;
    }
    
    function ApacheGroupConfigNode($parent = null) {
        $this -> __construct($parent);
    }

}

class ApacheConfig {
    var $allConditionsTrue;             // Set when dumping the complete config tree
    var $configTree;                    // The post-evaluation configuration
    var $coreModules;                   // Typical core modules.
    var $directory = array();           // Array[dirpath] of directory nodes
    var $envVars = array();             // Environment variables used in the config
    var $file = array();                // Array[dirpath] of global file nodes
    var $includes = array();            // List of all included files with references
    var $location = array();            // Array[dirpath] of location nodes
    var $modules = array();             // All modules used or referenced
    var $moduleLoads = array();         // Array[module] of LoadModule nodes
    var $options = array();             // List of options array[opt] = true
    var $sequence;                      // Source line sequence number
    var $serverRoot;                    // The config's sserver root
    var $root;                          // The base of the parsed config
    var $verbose = false;               // When set, echo what we're doing
    
    function __construct() {
        // We guess at a reasonable set of core modules
        $this -> coreModules = array('core.c' => true, 'mpm_common.c' => true,
            'worker.c' => true);
    }
    
    function ApacheConfig() {
        $this -> __construct();
    }
    
    function deevaluate() {
        $this -> allConditionsTrue = true;
        $this -> directory = array();
        $this -> location = array();
        $this -> configTree = $this -> root;
        $this -> evaluateNode($this -> configTree);
    }
    
    function evaluate() {
        $this -> allConditionsTrue = false;
        $this -> directory = array();
        $this -> location = array();
        $this -> moduleLoads = array();
        foreach ($this -> modules as $mod => $val) {
            $this -> modules[$mod] = false;
        }
        foreach ($this -> coreModules as $mod => $val) {
            $this -> modules[$mod] = true;
        }
        $this -> configTree = $this -> root;
        if (! $this -> evaluateNode($this -> configTree)) {
            $this -> configTree = null;
        }
    }
    
    function evaluateNode(&$node) {
        if ($node instanceof ApacheGroupConfigNode) {
            $walkSubs = true;
            switch (strtolower($node -> directive)) {
                case 'directory':
                case 'directorymatch': {
                    $directory = $node -> expr;
                    if ($directory{0} == '"') {
                        $directory = substr($directory, 1, strlen($directory) - 2);
                    }
                    if (! isset($this -> directory[$directory])) {
                        $this -> directory[$directory] = array();
                    }
                    $this -> directory[$directory][] = &$node;
                } break;
                
                case 'files':
                case 'filesmatch': {
                    $isScoped = false;
                    $upLink = $node -> parent;
                    while ($upLink) {
                        if ($upLink -> scoped) {
                            $isScoped = true;
                            break;
                        }
                        $upLink = $upLink -> parent;
                    }
                    if (! $isScoped) {
                        $fid = $node -> expr;
                        if ($fid{0} == '"') {
                            $fid = substr($fid, 1, strlen($fid) - 2);
                        }
                        if (! isset($this -> file[$fid])) {
                            $this -> file[$fid] = array();
                        }
                        $this -> file[$fid][] = &$node;
                    }
                } break;
                
                case 'include': {
                    foreach ($node -> subNodes as $sInd => $subNode) {
                        foreach ($node -> subNodes[$sInd] -> subNodes as $ssInd => $subSubNode) {
                            if (! $this -> evaluateNode($node -> subNodes[$sInd] -> subNodes[$ssInd])) {
                                $node -> subNodes[$sInd] -> subNodes[$ssInd] = null;
                            }
                        }
                        for ($ssInd = count($node -> subNodes[$sInd] -> subNodes) - 1; $ssInd; $ssInd--) {
                            if (is_null($node -> subNodes[$sInd] -> subNodes[$ssInd])) {
                                unset($node -> subNodes[$sInd] -> subNodes[$ssInd]);
                            }
                        }
                        if (count($node -> subNodes[$sInd] -> subNodes) <= $node -> subNode[$sInd] -> commentCount) {
                            $node -> subNodes[$sInd] = null;
                        }
                    }
                    for ($sInd = count($node -> subNodes) - 1; $sInd; $sInd--) {
                        if (is_null($node -> subNodes[$sInd])) {
                            unset($node -> subNodes[$sInd]);
                        }
                    }
                    return count($node -> subNodes) > $node -> commentCount;
                } break;
                
                case 'ifdefine': {
                    $invert = $node -> expr{0} == '!';
                    if ($invert) {
                        $var = substr($node -> expr, 1);
                    } else {
                        $var = $node -> expr;
                    }
                    if (isset($this -> envVars[$var])) {
                        $walkSubs = $this -> envVars[$var];
                    } else {
                        $walkSubs = false;
                    }
                    if ($invert) {
                        $walkSubs = !$walkSubs;
                    }
                } break;
                
                case 'ifmodule': {
                    $invert = $node -> expr{0} == '!';
                    if ($invert) {
                        $var = substr($node -> expr, 1);
                    } else {
                        $var = $node -> expr;
                    }
                    if (isset($this -> modules[$var])) {
                        $walkSubs = $this -> modules[$var];
                    } else {
                        $walkSubs = false;
                    }
                    if ($invert) {
                        $walkSubs = !$walkSubs;
                    }
                    //echo 'IfMod: ' . $node -> expr . ' ' . ($walkSubs ? 'true' : 'false') . '<br/>';
                } break;
                
                case 'location': 
                case 'locationmatch': {
                    $directory = $node -> expr;
                    if ($directory{0} == '"') {
                        $directory = substr($directory, 1, strlen($directory) - 2);
                    }
                    if (! isset($this -> location[$directory])) {
                        $this -> location[$directory] = array();
                    }
                    $this -> location[$directory][] = &$node;
                } break;
                
                default: {
                } break;

            }
            if ($walkSubs || $this -> allConditionsTrue) {
                foreach ($node -> subNodes as $sInd => $subNode) {
                    if (! $this -> evaluateNode($node -> subNodes[$sInd])) {
                        $node -> subNodes[$sInd] = null;
                    }
                }
                for ($sInd = count($node -> subNodes) - 1; $sInd; $sInd--) {
                    if (is_null($node -> subNodes[$sInd])) {
                        unset($node -> subNodes[$sInd]);
                    }
                }
                return count($node -> subNodes) > $node -> commentCount;
            } else {
                return false;
            }
        } else {
            switch (strtolower($node -> directive)) {
                case 'loadmodule': {
                    $mod = $this -> extractModule($node -> expr);
                    $this -> modules[$mod] = true;
                    $this -> moduleLoads[$mod] = &$node;
                    //echo 'LoadMod: ' . $mod . ' true<br/>';
                } break;
                
            }
        }
        return true;
    }
    
    function extractModule($expr) {
        // expect module_name<whitespace>path
        // assume module name is <filename>.c
        $endMod = strcspn($expr, ' ' . chr(9));
        $path = trim(substr($expr, $endMod + 1));
        $bits = explode('/', $path);
        $soFile = $bits[count($bits) - 1];
        $mod = substr($soFile, 0, strlen($soFile) - 2) . 'c';
        return $mod;
    }
    
    function lineIncr(&$sourceLine) {
        $this -> sequence++;
        return ++$sourceLine[1];
    }
    
    function processIncludes(&$base, $args, $currLine) {
        if ($args{0} != '/') {
            $args = $this -> serverRoot . '/' . $args;
        }
        if ($args{strlen($args) - 1} == '/') {
            //
            // This is a full directory
            //
            $args .= '*';
        }
        $path = explode('/', substr($args, 1));
        //
        // Search the path for wildcards
        //
        $foundWild = false;
        $pathSoFar = '';
        for ($ind = 0; $ind < count($path); $ind++) {
            if (($posn = strcspn($path[$ind], '*?[]')) != strlen($path[$ind])) {
                $foundWild = true;
                //
                // Find all matching files
                //
                $matches = array();
                $dh = opendir($pathSoFar);
                while (($file = readdir($dh)) !== false) {
                    if (fnmatch($path[$ind], $file)) {
                        $matches[] = $file; 
                    }
                }
                $subPath = implode('/', array_slice($path, $ind + 1));
                if ($subPath != '') {
                    $subPath = '/' . $subPath;
                }
                for ($sub = 0; $sub < count($matches); $sub++) {
                    ++$this -> sequence;
                    $result = $this -> processIncludes($base, $pathSoFar . '/'
                        . $matches[$sub] . $subPath, $currLine);
                    if (! $result) {
                        return false;
                    }
                }
            }
            $pathSoFar .= '/' . $path[$ind];
        }
        if (! $foundWild) {
            if (false) {
                $this -> errMsg = 'Nested include file: ' . $args;
            }
            if ($this -> verbose) {
                echo '&gt;&gt;&gt;' . $args . '<br/>';
            }
            $node = new ApacheGroupConfigNode($base);
            $node -> directive = '<include>';
            $node -> scoped = false;
            $node -> sequence = $this -> sequence;
            $node -> sourceLine = $currLine;
            $node -> text = '';
            $node -> expr = $args;
            $fh = @fopen($args, 'r');
            if (! $fh) {
                $this -> errMsg = 'Failed to open include file: ' . $args;
                return false;
            }
            if (! isset($this -> includes[$args])) {
                $incID = count($this -> includes);
                $this -> includes[$args] = array($incID, array());
            }
            $this -> includes[$args][1][] = $currLine;
            $currLine = array($incID, 0);
            while (! feof($fh)) {
                $this -> lineIncr($currLine);
                if (! $this -> readLine($node, $fh, $currLine)) {
                    break;
                }
            }
            fclose($fh);
            $base -> subNodes[] = &$node;
            if ($this -> verbose) {
                echo '&lt;&lt;&lt;' . $args . '<br/>';
            }
        }
        return true;
    }
    
    function read($fid) {
        $this -> envVars = array();
        $this -> includes = array();
        $this -> includes[''] = array(0, array());
        $this -> modules = $this -> coreModules;
        $this -> sequence = 0;
        $this -> serverRoot = '/usr/local/apache';
        $this -> root = new ApacheGroupConfigNode();
        $curr = &$this -> root;
        $curr -> directive = '<root>';
        $curr -> expr = $fid;
        $curr -> sequence = $this -> sequence;
        $fh = @fopen($fid, 'r');
        if (! $fh) {
            $this -> errMsg = 'Failed to open config file: ' . $fid;
            return false;
        }
        $currLine = array(0, 0);
        while (! feof($fh)) {
            $this -> lineIncr($currLine);
            if (! $this -> readLine($curr, $fh, $currLine)) {
                break;
            }
        }
        fclose($fh);
        $this -> deevaluate();
    }
    
    function readLine(&$base, $fh, &$currLine) {
        $line = trim(fgets($fh));
        if ($this -> verbose) {
            echo str_pad($currLine[1], 4, ' ', STR_PAD_LEFT) . '|' . $line . '<br/>';
        }
        if ($line == '') {
            // eat whitespace
            $node = null;
        } else if ($line{0} == '#') {
            //
            // Just insert the comment verbatim
            //
            $node = new ApacheBaseConfigNode($base);
            $node -> directive = '#';
            $node -> text = $line;
        } else if ($line{0} == '<') {
            if ($line{1} == '/') {
                //
                // Here's a place where we assume the file is well structured..
                //
                return false;
            }
            $node = new ApacheGroupConfigNode($base);
            $node -> text = $line;
            $line = substr($line, 1, strlen($line) - 2);
            $endDir = strcspn($line, ' ' . chr(9));
            $node -> directive = substr($line, 0, $endDir);
            $node -> expr = trim(substr($line, $endDir + 1));
            if ($this -> verbose) {
                echo '&nbsp;&nbsp;&nbsp;directive=' . $node -> directive 
                    . ' expr=' . $node -> expr . '<br/>';
            }
            switch (strtolower($node -> directive)) {
                case 'ifdefine': {
                    $node -> scoped = false;
                    $var = $node -> expr;
                    if ($var{0} == '!') {
                        $var = substr($var, 1);
                    }
                    if (! isset($this -> envVars[$var])) {
                        $this -> envVars[$var] = true;
                    }
                } break;
                
                case 'ifmodule': {
                    $node -> scoped = false;
                    $var = $node -> expr;
                    if ($var{0} == '!') {
                        $var = substr($var, 1);
                    }
                    $node -> loadValue = isset($this -> modules[$var]);
                } break;
                
            }
            while (! feof($fh)) {
                $this -> lineIncr($currLine);
                if (! $this -> readLine($node, $fh, $currLine)) {
                    break;
                }
            }
        } else {
            $node = new ApacheBaseConfigNode($base);
            $node -> text = $line;
            $endDir = strcspn($line, ' ' . chr(9));
            $node -> directive = substr($line, 0, $endDir);
            $args = trim(substr($line, $endDir + 1));
            if (($args != '') && ($args{0} == '"')) {
                $args = substr($args, 1, strlen($args) - 2);
            }
            if ($this -> verbose) {
                echo '&nbsp;&nbsp;&nbsp;directive=' . $node -> directive . ' args=' . $args . '<br/>';
            }
            switch (strtolower($node -> directive)) {
                case 'include': {
                    //
                    // We're going to consider the include file(s) as a group
                    // so that we can record the filename(s) in the group node
                    //
                    $gnode = new ApacheGroupConfigNode($base);
                    $gnode -> text = $node -> text;
                    $gnode -> directive = $node -> directive;
                    $node = $gnode;
                    $node -> scoped = false;
                    if (! $this -> processIncludes($node, $args, $currLine)) {
                        return false;
                    }
                } break;
                
                case 'loadmodule': {
                    $node -> expr = $args;
                    $mod = $this -> extractModule($node -> expr);
                    $this -> modules[$mod] = true;
                } break;

                case 'serverroot': {
                    $this -> serverRoot = $args;
                } break;
            }
        }
        if ($node) {
            $node -> sourceLine = $currLine;
            $node -> sequence = $this -> sequence;
            $base -> subNodes[] = &$node;
            if ($node -> directive == '#') {
                $base -> commentCount++;
            }
        }
        return true;
    }
    
    /**
     * Read apache environment from a gentoo-like setup
     */
    function readOpts($fid, $key = 'APACHE2_OPTS') {
        $this -> options = array();
        $fh = @fopen($fid, 'r');
        if (! $fh) {
            $this -> errMsg = 'Failed to open config file: ' . $fid;
            return false;
        }
        while (! feof($fh)) {
            $line = trim(fgets($fh));
            if (substr($line, 0, strlen($key)) != $key) {
                continue;
            }
            $line = trim(substr($line, strlen($key)));
            if ($line{0} != '=') {
                continue;
            }
            $line = trim(substr($line, 1));
            $line = trim(substr($line, 1, strlen($line) - 2));
            $opts = explode('-D', $line);
            foreach ($opts as $opt) {
                $opt = trim($opt);
                if ($opt) {
                    $this -> options[trim($opt)] = true;
                }
            }
        }
        fclose($fh);
    }
}

class ApacheConfigWriter {
    var $options;
    
    function __construct() {
        $this -> options = array();
        $this -> options['format'] = 'html';    // html, string
        $this -> options['indentdepth'] = 4;
        $this -> options['return'] = true;
        $this -> options['showcomments'] = true;
    }
    
    function ApacheConfigWriter() {
        $this -> __construct();
    }
    
    function output($str, $depth) {
        $bol = str_pad('', $depth * $this -> options['indentdepth']);
        if ($this -> options['format'] == 'html') {
            $eol = '<br/>';
            $bol = str_replace(' ', '&nbsp;', $bol);
            $str = str_replace(' ', '&nbsp;', htmlentities($str));
        } else {
            $eol = chr(10);
        }
        if ($this -> options['return']) {
            return $bol . $str . $eol;
        } else {
            echo $bol . $str . $eol;
        }
        return '';
    }
    
    function report($config) {
        if ($this -> options['format'] != 'html') {
            return 'Report only available in HTML';
        }
        $result = '';
        $root = $config -> configTree;
        if (($posn = strrpos($root -> expr, '/')) != false) {
            $trim = substr($root -> expr, 0, $posn + 1);
        } else {
            $trim = '';
        }
        $result .= $this -> write('<table class="acwrapper">');
        //
        // Header
        //
        $result .= $this -> write('<tr><td>');
        $result .= $this -> write('<table class="acblk">');
        $result .= $this -> write('<tr><th class="acblkhdr" colspan="2">Apache Configuration</th></tr>');
        $result .= $this -> write('<tr><td class="acrighthdr">Base file:</td><td class="accell">',
            $root -> expr, '</td></tr>');
        $result .= $this -> write('<tr><td class="acrighthdr">Included files:</td><td class="accell">');
        $scratch = $config -> includes;
        ksort($scratch);
        $incById = array();
        foreach ($scratch as $fid => $refs) {
            if ($fid) {
                if (substr($fid, 0, strlen($trim)) == $trim) {
                    $incByID[$refs[0]] = substr($fid, strlen($trim));
                } else {
                    $incByID[$refs[0]] = $fid;
                }
                $result .= $this -> write('', $incByID[$refs[0]], '<br/>');
            } else {
                $incByID[$refs[0]] = substr($root -> expr, strlen($trim));
            }
            
        }
        $result .= $this -> write('</td></tr>');
        $result .= $this -> write('</table>');
        $result .= $this -> write('</td></tr>');
        //
        // Loaded modules
        //
        $result .= $this -> write('<tr><td>');
        $result .= $this -> write('<table class="acblk">');
        $result .= $this -> write('<tr><th class="acblkhdr" colspan="2">Modules (Alphabetically)</th></tr>');
        $result .= $this -> write('<tr><td class="acrighthdr">Core Modules:</td><td class="accell">');
        $scratch = $config -> coreModules;
        ksort($scratch);
        $scratch = array_keys($scratch);
        $result .= $this -> write('', implode(', ', $scratch));
        $result .= $this -> write('</td></tr>');
        $result .= $this -> write('<tr><th class="acblkhdr">Module</th><th class="acblkhdr">Source File : Line</th></tr>');
        $scratch = $config -> moduleLoads;
        ksort($scratch);
        foreach ($scratch as $mod => $node) {
            $result .= $this -> write('<tr><td class="accell">', $mod, '</td>');
            $sourceLine = $node -> sourceLine;
            $source = $incByID[$sourceLine[0]] . ' : ' . $sourceLine[1];
            $result .= $this -> write('<td>', $source, '</td></tr>');
        }

        $result .= $this -> write('</table>');
        $result .= $this -> write('</td></tr>');
        //
        // Global directives
        //
        $globalDirectives = array();    // fill this with [directive] = array[i] of node ref
        foreach ($root -> subNodes as $node) {
            $this -> extractGlobals($globalDirectives, $node);
        }
        ksort($globalDirectives);
        $result .= $this -> write('<tr><td>');
        $result .= $this -> write('<table class="acblk">');
        $result .= $this -> write('<tr><th class="acblkhdr" colspan="2">' .
            'Other Global Directives (Alphabetically)</th></tr>');
        foreach ($globalDirectives as $directive => $nodes) {
            foreach ($nodes as $node) {
                $result .= $this -> write('<tr><td class="accell">', $node -> text, '</td>');
                $sourceLine = $node -> sourceLine;
                $source = $incByID[$sourceLine[0]] . ' : ' . $sourceLine[1];
                $result .= $this -> write('<td class="accell">', $source, '</td></tr>');
            }
        }
        
        $result .= $this -> write('</table>');
        $result .= $this -> write('</td></tr>');
        //
        // Directories
        //
        ksort($config -> directory);
        $result .= $this -> write('<tr><td>');
        $result .= $this -> write('<table class="acblk">');
        $result .= $this -> write('<tr><th class="acblkhdr" colspan="2">' .
            'Directory Directives (Alphabetically)</th></tr>');
        foreach ($config -> directory as $dir => $refs) {
            $result .= $this -> write('<tr><th class="accell" colspan="2">' , $dir, '</th></tr>');
            foreach ($refs as $base) {
                $sourceLine = $base -> sourceLine;
                $source = $incByID[$sourceLine[0]];
                $result .= $this -> write('<tr><th>&nbsp;</th><th class="accell">' , $source, '</th></tr>');
                foreach ($base -> subNodes as $node) {
                    if ($node -> directive != '#') {
                        $result .= $this -> write('<tr><td class="accell">', $node -> text, '</td>');
                        $result .= $this -> write('<td class="accell">', $node -> sourceLine[1], '</td></tr>');
                    }
                }
            }
        }
        
        $result .= $this -> write('</table>');
        $result .= $this -> write('</td></tr>');
        //
        // End wrapper
        //
        $result .= $this -> write('</table>');
        return $result;
    }
    
    function extractGlobals(&$gDirs, $node) {
        if ($node instanceof ApacheGroupConfigNode) {
            if ((! $node -> scoped) && (strtolower($node -> directive) == 'include')) {
                foreach ($node -> subNodes as $subNode) {
                    foreach ($subNode -> subNodes as $subSubNode) {
                        $this -> extractGlobals($gDirs, $subSubNode);
                    }
                }
            }
        } else {
            $directive = strtolower($node -> directive);
            switch ($directive) {
                case '#':
                case 'loadmodule': {
                    // We ignore these
                } break;
                
                default: {
                    if (! isset($gDirs[$directive])) {
                        $gDirs[$directive] = array();
                    }
                    $gDirs[$directive][] = &$node;
                }
            }
        }
    }
    
    function setOption($name, $value) {
        $this -> options[$name] = $value;
    }
    
    function simpleDump($config) {
        $result = '';
        $root = $config -> configTree;
        foreach ($root -> subNodes as $node) {
            $result .= $this -> toSimpleString($node, 0);
        }
        return $result;
    }
    
    function toSimpleString($node, $depth) {
        $result = '';
        if ($node instanceof ApacheGroupConfigNode) {
            if (strtolower($node -> directive) == 'include') {
                $result .= $this -> output('# Include: ' . $node -> expr, $depth);
                foreach ($node -> subNodes as $subNode) {
                    $result .= $this -> output('# File: ' . $subNode -> expr, $depth);
                    foreach ($subNode -> subNodes as $subSubNode) {
                        $result .= $this -> toSimpleString($subSubNode, $depth + 1);
                    }
                }
            } else {
                $result .= $this -> output($node -> text, $depth);
                foreach ($node -> subNodes as $subNode) {
                    $result .= $this -> toSimpleString($subNode, $depth + 1);
                }
                $result .= $this -> output('</' . $node -> directive . '>', $depth);
            }
        } else {
            if ($node -> directive == '#') {
                if ($this -> options['showcomments']) {
                    $result .= $this -> output($node -> text, $depth);
                }
            } else {
                $result .= $this -> output($node -> text, $depth);
            }
        }
        return $result;
    }

    function write($pre, $str = '', $post = '') {
        if ($this -> options['format'] == 'html') {
            $str = str_replace(' ', '&nbsp;', htmlentities($str));
        }
        if ($this -> options['return']) {
            return $pre . $str . $post;
        } else {
            echo $pre . $str . $post;
        }
        return '';
    }
    
}

?>