#depage-fs

Transparent, protocol independent, local and remote file system operations.

##Features
####List of operations

pwd, ls, lsDir, lsFiles, exists, fileInfo, cd, mkdir, rm, mv, get, put, getString, putString

####Protocols

local, ftp(s), ssh (authentication by password or key)

####Example

######File transfer, local to remote
```php
$fs = Fs:factory('ftp://user:pass@host/');

$fs->mkdir('new/path');
$fs->cd('new/path');
$fs->put('file.zip');
```
######Connect with ssh key
```php

$params = array(
    'user'              => 'user',
    'pass'              => 'pass',
    'host'              => '192.168.1.42',
    'privateKeyFile'    => '.ssh/testkey',
    'tmp'               => '.'
);

$fs = Fs:factory('ssh://host', $params);

$fs->get('/home/user/file.zip');
```


##License (dual)

- GPL2: <http://www.gnu.org/licenses/gpl-2.0.html>
- MIT: <http://www.opensource.org/licenses/mit-license.php>

