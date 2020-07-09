depage-fs
=========

Transparent, protocol independent, local and remote file system operations.

Abstract virtual-file-system-like behavior across local file systems and
remote protocols. With a syntax analogue to FTP-clients (put, get) depage-fs
makes PHP stream wrappers easily accessible and provides a unified user
interface.

Usage follows a local-remote paradigm where 'local' means the actual current
working directory and 'remote' can be another local directory or a directory on
a remote system.

depage-fs virtually tracks the remote working directory (cd, pwd). Once the
remote path is set, it prevents any operations in parent directories.

Features
========

List of operations
------------------

pwd, ls, lsDir, lsFiles, exists, fileInfo, cd, mkdir, rm, mv, get, put,
getString, putString

Protocols
---------

local, ftp(s), ssh (authentication by password or key)

Example
-------

File transfer, local to remote

```php
$fs = Fs::factory('ftp://user:pass@host/');

$fs->mkdir('new/path');
$fs->cd('new/path');
$fs->put('file.zip');
```

First, get the SSH fingerprint...

```php
$fs = Fs::factory('ssh://host');
$print = $fs->getFingerprint();
```

then connect with SSH key

```php
$params = array(
    'user'              => 'user',
    'pass'              => 'pass',
    'privateKeyFile'    => '.ssh/id_rsa',
    'fingerprint'       => $print,
    'tmp'               => '.'
);

$fs = Fs::factory('ssh://host', $params);
$fs->get('/home/user/file.zip');
```

Notes on usage
--------------

- depage-fs error handler converts any file system operation errors/warnings to
exceptions. The problem causing them may be in a location different to the one
stated in the exception.
- !!! Important in FTPS !!! If the server does not support SSL, then the
connection falls back to regular unencrypted FTP. Currently there is no way to
make sure the connection is encrypted. Susceptible to MITM attacks!
- SSH keys need to be PEM-formatted (base64).
- SSH keys can be strings or files. However, internally php requires key files
(for some strange reason, both public and private). The files are automatically
generated (and subsequently deleted) in a temporary directory specified by the
'tmp'-parameter.

License (dual)
--------------

- GPL2: <http://www.gnu.org/licenses/gpl-2.0.html>
- MIT: <http://www.opensource.org/licenses/mit-license.php>

