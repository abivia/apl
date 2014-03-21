<?php
/**
 * Read/write apache format user/group files
 * 
 * @package AP5L
 * @subpackage Apache
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @copyright 2007, Alan Langford
 * @version $Id: ApacheUserAuth.php 91 2009-08-21 02:45:29Z alan.langford@abivia.com $
 * @author Alan Langford <alan.langford@abivia.com>
 * 
 * @todo Update to PHP5
 */

/**
 * Manipulate Apache httpd user and goup files.
 */
class ApacheUserAuth {
    var $errMsg;                        // User readable error message
    var $groupFile = 'groups';
    var $groupList;
    var $lockFile = 'httpd.lock';
    var $passFile = 'passwords';
    var $result;                        // Program readable error code
    var $rootDir = '';
    var $userList;
    
    function _acquireLock($timeoutUsec) {
        //
        // Open and lock a mutex file (flock() seems to not work)
        //
        $time = 0;
        $uwait = 1000;
        do {
            $lfh = @fopen($this -> rootDir . $this -> lockFile, 'w');
            if (! $lfh) {
                usleep($uwait);
                $time += $uwait;
            }
        } while (! $lfh && $time < $timeoutUsec);
        return $lfh;
    }
    
    function &_readGroups($gfh) {
        $groups = array();
        while (! feof($gfh)) {
            $line = fgets($gfh);
            $line = substr($line, 0, strlen($line) - 1);
            if (($pos = strpos($line, ':')) === false) {
                if ($line) {
                    $this -> genError(3, $line);
                    return false;
                }
                break;
            }
            $group = substr($line, 0, $pos);
            $users = explode(' ', substr($line, $pos + 2));
            $groups[$group] = array();
            foreach ($users as $user) {
                $groups[$group][$user] = $user;
            }
        }
        return $groups;
    }
    
    function &_readPasswords($pfh) {
        //
        // Read the password file
        //
        $pwfile = array();
        while (! feof($pfh)) {
            $line = fgets($pfh);
            $line = substr($line, 0, strlen($line) - 1);
            if (($pos = strpos($line, ':')) === false) {
                if ($line) {
                    $this -> genError(3, $line);
                    return false;
                }
                break;
            }
            $pwfile[substr($line, 0, $pos)] = substr($line, $pos + 1);
        }
        return $pwfile;
    }
    
    function _releaseLock($lfh) {
        if ($lfh) {
            fclose($lfh);
        }
    }
    
    function addGroup($group, $userList, $timeoutUsec = 1000000) {
        //
        // Make sure we can open the password and group files
        //
        $pfh = @fopen($this -> rootDir . $this -> passFile, 'r');
        if (! $pfh) {
            $this -> genError(1, $this -> passFile);
            return false;
        }
        $gfh = @fopen($this -> rootDir . $this -> groupFile, 'r');
        if (! $gfh) {
            $this -> genError(1, $this -> groupFile);
            return false;
        }
        $this -> genError(0);
        
        $lfh = $this -> _acquireLock($timeoutUsec);
        if (! $lfh) {
            $this -> generror(2);
        } else {
            if (($groups = $this -> _readGroups($gfh)) !== false) {
                //
                // Verify that the group is not present
                //
                if (isset($groups[$group])) {
                    $this -> genError(5, $group);
                } else {
                    if (($pwfile = $this -> _readPasswords($pfh)) !== false) {
                        if (! $userList) {
                            $userList = array();
                        } else if (! is_array($userList)) {
                            $userList = array($userList);
                        }
                        //
                        // Verify that each user exists
                        //
                        foreach ($userList as $user) {
                            if (! isset($pwfile[$user])) {
                                $this -> genError(4, $user);
                                break;
                            }
                        }
                        if ($this -> errMsg == '') {
                            $groups[$group] = $userList;
                        }
                    }
                }
            }
        }
        if ($this -> errMsg == '') {
            //
            // Close the group file and reopen truncated
            //
            fclose($gfh); 
            $gfh = @fopen($this -> rootDir . $this -> groupFile, 'w+');
            ksort($groups);
            //
            // Write the updated file
            //
            foreach($groups as $g => $ul) {
                fwrite($gfh, $g . ': ' . implode(' ', $ul) . chr(10));
            }
        }
        //
        // Close the data files
        //
        fclose($gfh);
        fclose($pfh);
        //
        // Close the lock file
        //
        $this -> _releaseLock($lfh);
        return ($this -> errMsg == '');
    }

    function addUser($user, $pass, $timeoutUsec = 1000000) {
        //
        // Make sure we can open the password file
        //
        $pfh = @fopen($this -> rootDir . $this -> passFile, 'r');
        if (! $pfh) {
            $this -> genError(1, $this -> passFile);
            return false;
        }
        $this -> genError(0);
        
        $lfh = $this -> _acquireLock($timeoutUsec);
        if (! $lfh) {
            $this -> genError(2);
        } else {
            if (($pwfile = $this -> _readPasswords($pfh)) !== false) {
                //
                // Verify that the user is not present
                //
                if (isset($pwfile[$user])) {
                    $this -> genError(5, $user);
                } else {
                    //
                    // Password includes the {SHA}
                    //
                    $pwfile[$user] = '{SHA}' . base64_encode(pack('H*', sha1($pass)));
                }
            }
        }
        if ($this -> errMsg == '') {
            //
            // Close the password file and reopen truncated
            //
            fclose($pfh); 
            $pfh = @fopen($this -> rootDir . $this -> passFile, 'w+');
            ksort($pwfile);
            //
            // Write the updated file
            //
            foreach($pwfile as $u => $p) {
                fwrite($pfh, $u . ':' . $p . chr(10));
            }
        }
        //
        // Close the password file
        //
        fclose($pfh);
        //
        // Close the lock file
        //
        $this -> _releaseLock($lfh);
        return ($this -> errMsg == '');
    }

    function addUserToGroup($group, $userList, $timeoutUsec = 1000000) {
        //
        // Make sure we can open the password and group files
        //
        $pfh = @fopen($this -> rootDir . $this -> passFile, 'r');
        if (! $pfh) {
            $this -> genError(1, $this -> passFile);
            return false;
        }
        $gfh = @fopen($this -> rootDir . $this -> groupFile, 'r');
        if (! $gfh) {
            $this -> genError(1, $this -> groupFile);
            return false;
        }
        $this -> genError(0);
        
        $lfh = $this -> _acquireLock($timeoutUsec);
        if (! $lfh) {
            $this -> genError(2);
        } else {
            if (($groups = $this -> _readGroups($gfh)) !== false) {
                //
                // Verify that the group is present
                //
                if (isset($groups[$group])) {
                    if (($pwfile = $this -> _readPasswords($pfh)) !== false) {
                        if (! $userList) {
                            $userList = array();
                        } else if (! is_array($userList)) {
                            $userList = array($userList);
                        }
                        //
                        // Verify that each user exists
                        //
                        $mergeUsers = array();
                        foreach ($userList as $user) {
                            if (! isset($pwfile[$user])) {
                                $this -> genError(4, $user);
                                break;
                            }
                            $mergeUsers[$user] = $user;
                        }
                        if ($this -> errMsg == '') {
                            //
                            // Merge (and clean) the existing users
                            //
                            foreach ($groups[$group] as $user) {
                                if (isset($pwfile[$user])) {
                                    $mergeUsers[$user] = $user;
                                }
                            }
                            ksort($mergeUsers);
                            //
                            // Users are space delimited
                            //
                            $groups[$group] = $mergeUsers;
                        }
                    }
                } else {
                    $this -> genError(4, $group);
                }
            }
        }
        if ($this -> errMsg == '') {
            //
            // Close the group file and reopen truncated
            //
            fclose($gfh); 
            $gfh = @fopen($this -> rootDir . $this -> groupFile, 'w+');
            ksort($groups);
            //
            // Write the updated file
            //
            foreach($groups as $g => $ul) {
                fwrite($gfh, $g . ': ' . implode(' ', $ul) . chr(10));
            }
        }
        //
        // Close the data files
        //
        fclose($gfh);
        fclose($pfh);
        //
        // Close the lock file
        //
        $this -> _releaseLock($lfh);
        return ($this -> errMsg == '');
    }

    function deleteGroup($group, $timeoutUsec = 1000000) {
        //
        // Make sure we can open the group file
        //
        $gfh = @fopen($this -> rootDir . $this -> groupFile, 'r');
        if (! $gfh) {
            $this -> genError(1, $this -> groupFile);
            return false;
        }
        $this -> genError(0);
        
        $lfh = $this -> _acquireLock($timeoutUsec);
        if (! $lfh) {
            $this -> genError(2);
        } else {
            if (($groups = $this -> _readGroups($gfh)) !== false) {
                //
                // Verify that the group is present
                //
                if (isset($groups[$group])) {
                    unset($groups[$group]);
                } else {
                    $this -> genError(4, $group);
                }
            }
        }
        if ($this -> errMsg == '') {
            //
            // Close the group file and reopen truncated
            //
            fclose($gfh); 
            $gfh = @fopen($this -> rootDir . $this -> groupFile, 'w+');
            ksort($groups);
            //
            // Write the updated file
            //
            foreach($groups as $g => $ul) {
                fwrite($gfh, $g . ': ' . implode(' ', $ul) . chr(10));
            }
        }
        //
        // Close the group file
        //
        fclose($gfh);
        //
        // Close the lock file
        //
        $this -> _releaseLock($lfh);
        return ($this -> errMsg == '');
    }

    function deleteUser($user, $timeoutUsec = 1000000) {
        //
        // Make sure we can open the password file
        //
        $pfh = @fopen($this -> rootDir . $this -> passFile, 'r');
        if (! $pfh) {
            $this -> genError(1, $this -> passFile);
            return false;
        }
        $this -> genError(0);
        
        $lfh = $this -> _acquireLock($timeoutUsec);
        if (! $lfh) {
            $this -> genError(2);
        } else {
            if (($pwfile = $this -> _readPasswords($pfh)) !== false) {
                //
                // Verify that the user is present
                //
                if (isset($pwfile[$user])) {
                    unset($pwfile[$user]);
                } else {
                    $this -> genError(4, $user);
                }
            }
        }
        if ($this -> errMsg == '') {
            //
            // Close the password file and reopen truncated
            //
            fclose($pfh); 
            $pfh = @fopen($this -> rootDir . $this -> passFile, 'w+');
            ksort($pwfile);
            //
            // Write the updated file
            //
            foreach($pwfile as $u => $p) {
                fwrite($pfh, $u . ':' . $p . chr(10));
            }
        }
        //
        // Close the password file
        //
        fclose($pfh);
        //
        // Close the lock file
        //
        $this -> _releaseLock($lfh);
        return ($this -> errMsg == '');
    }

    function deleteUserFromGroup($group, $userList, $timeoutUsec = 1000000) {
        //
        // Make sure we can open the password and group files
        //
        $pfh = @fopen($this -> rootDir . $this -> passFile, 'r');
        if (! $pfh) {
            $this -> genError(1, $this -> passFile);
            return false;
        }
        $gfh = @fopen($this -> rootDir . $this -> groupFile, 'r');
        if (! $gfh) {
            $this -> genError(1, $this -> groupFile);
            return false;
        }
        $this -> genError(0);
        
        $lfh = $this -> _acquireLock($timeoutUsec);
        if (! $lfh) {
            $this -> genError(2);
        } else {
            if (($groups = $this -> _readGroups($gfh)) !== false) {
                //
                // Verify that the group is present
                //
                if (isset($groups[$group])) {
                    if (($pwfile = $this -> _readPasswords($pfh)) !== false) {
                        if (! $userList) {
                            $userList = array();
                        } else if (! is_array($userList)) {
                            $userList = array($userList);
                        }
                        //
                        // Remove each user
                        //
                        $mergeUsers = $groups[$group];
                        foreach ($userList as $user) {
                            unset($mergeUsers[$user]);
                        }
                        //
                        // Clean the remaining users
                        //
                        foreach ($mergeUsers as $key => $user) {
                            if (! isset($pwfile[$user])) {
                                unset($mergeUsers[$key]);
                            }
                        }
                        $groups[$group] = $mergeUsers;
                    }
                } else {
                    $this -> genError(4, $group);
                }
            }
        }
        if ($this -> errMsg == '') {
            //
            // Close the group file and reopen truncated
            //
            fclose($gfh); 
            $gfh = @fopen($this -> rootDir . $this -> groupFile, 'w+');
            ksort($groups);
            //
            // Write the updated file
            //
            foreach($groups as $g => $ul) {
                fwrite($gfh, $g . ': ' . implode(' ', $ul) . chr(10));
            }
        }
        //
        // Close the data files
        //
        fclose($gfh);
        fclose($pfh);
        //
        // Close the lock file
        //
        $this -> _releaseLock($lfh);
        return ($this -> errMsg == '');
    }

    function fileLock($fh, $timeoutUsec) {
        $time = 0;
        $uwait = 1000;
        do {
            $gotLock = flock($fh, LOCK_EX);
            if (! $gotLock) {
                usleep($uwait);
                $time += $uwait;
            }
        } while (! $gotLock && $time < $timeoutUsec);
        return $gotLock;
    }
    
    function fileUnlock($fh) {
        flock($fh, LOCK_UN);            // release the lock
    }
    
    function genError($result, $info = '') {
        $this -> result = $result;
        switch ($result) {
            case 0: {
                $this -> errMsg = '';
            } break;
            
            case 1: {
                $this -> errMsg = 'Error opening user/group file:' . $info;
            } break;

            case 2: {
                $this -> errMsg = 'Unable to obtain write lock.';
            } break;

            case 3: {
                $this -> errMsg = 'User/group file ' . $info . ' has unexpected format.';
            } break;

            case 4: {
                $this -> errMsg = 'User/group ' . $info . ' not found.';
            } break;

            case 5: {
                $this -> errMsg = 'User/group ' . $info . ' already exists.';
            } break;

            default: {
                $this -> errMsg = 'Error ' . $result . ' info ' . $info;
            } break;
        }
    }
    
    function getGroups($user = '') {
        if ($user == '') {
            $result = array();
            foreach ($this -> groupList as $group => $users) {
                $result[] = $group;
            }
            return $result;
        } else if (isset($this -> userList[$user])) {
            return $this -> userList[$user]['groups'];
        }
        return array();
    }
    
    function getUsers($group = '') {
        if ($group == '') {
            $result = array();
            foreach ($this -> userList as $user => $stuff) {
                $result[] = $user;
            }
            return $result;
        } else if (isset($this -> groupList[$group])) {
            return $this -> groupList[$group];
        }
        return array();
    }
    
    function isUserInGroup($user, $group) {
        if (isset($this -> userList[$user])) {
            if (isset($this -> userList[$user]['groups'][$group])) {
                return true;
            }
        }
        return false;
    }
    
    function loadGroups() {
        $gfh = @fopen($this -> rootDir . $this -> groupFile, 'r');
        if (! $gfh) {
            return 1;
        }
        $result = 0;
        $this -> groupList = array();
        while (! feof($gfh)) {
            $line = fgets($gfh);
            $line = substr($line, 0, strlen($line) - 1);
            if (($pos = strpos($line, ':')) === false) {
                if ($line) $result = 3;
                break;
            }
            $group = substr($line, 0, $pos);
            $users = explode(' ', substr($line, $pos + 2));
            $this -> groupList[$group] = $users;
            foreach ($users as $user) {
                if (isset($this -> userList[$user])) {
                    $this -> userList[$user]['groups'][$group] = $group;
                }
            }
        }
        fclose($gfh);
        ksort($this -> groupList);
        return $result;
    }

    function loadUsers() {
        $pfh = @fopen($this -> rootDir . $this -> passFile, 'r');
        if (! $pfh) {
            $this -> genError(1, $this -> groupFile);
            return false;
        }
        $this -> genError(0);

        $this -> userList = array();
        while (! feof($pfh)) {
            $line = fgets($pfh);
            $line = substr($line, 0, strlen($line) - 1);
            if (($pos = strpos($line, ':')) === false) {
                if ($line) {
                    $this -> genError(3, $line);
                    return false;
                }
                break;
            }
            $this -> userList[substr($line, 0, $pos)] = array('passattr' => substr($line, $pos + 1), 'groups' => array());
        }
        fclose($pfh);
        ksort($this -> userList);
        return ($this -> errMsg == '');
    }
    
    function setApachePassword($user, $pass, $timeoutUsec = 1000000) {
        //
        // Make sure we can open the password file
        //
        $pfh = @fopen($this -> rootDir . $this -> passFile, 'r');
        if (! $pfh) {
            $this -> genError(1, $this -> passFile);
            return false;
        }
        $this -> genError(0);

        $lfh = $this -> _acquireLock($timeoutUsec);
        if (! $lfh) {
            $this -> genError(2);
        } else {
            if (($pwfile = $this -> _readPasswords($pfh)) !== false) {
                //
                // Verify that the user is present
                //
                if (isset($pwfile[$user])) {
                    //
                    // Password includes the {SHA}
                    //
                    $pwfile[$user] = '{SHA}' . base64_encode(pack('H*', sha1($pass)));
                } else {
                    $this -> genError(4, $user);
                }
            }
        }
        if ($this -> errMsg =='') {
            //
            // Close the password file and reopen truncated
            //
            fclose($pfh); 
            $pfh = @fopen($this -> rootDir . $this -> passFile, 'w+');
            ksort($pwfile);
            //
            // Write the updated file
            //
            foreach($pwfile as $u => $p) {
                fwrite($pfh, $u . ':' . $p . chr(10));
            }
        }
        //
        // Close the password file
        //
        fclose($pfh);
        //
        // Close the lock file
        //
        $this -> _releaseLock($lfh);
        return ($this -> errMsg =='');
    }

    function setGroupFile($fname) {
        $this -> groupFile = $fname;
    }

    function setPasswordFile($fname) {
        $this -> passFile = $fname;
    }

    function setPath($path) {
        $this -> rootDir = $path;
    }

}

?>