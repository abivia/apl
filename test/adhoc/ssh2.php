<?php
$conn = ssh2_connect('host');
print_r(ssh2_auth_none($conn, 'user'));
if (! ssh2_auth_password($conn, 'user', 'zzz')) {
    echo 'login fail';
    exit;
}
$stream = ssh2_exec($conn, 'uname');
echo stream_get_contents($stream);
