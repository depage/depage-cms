<?php

namespace Depage\Fs\Streams;

use Depage\Fs\Fs;

class FtpCurl
{
    // {{{ variables
    public $context;

    protected $path;
    protected $mode;
    protected $buffer;
    protected $pos;
    protected $dirPos;
    protected $files;
    protected $url;
    protected $translation = ['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks'];

    static protected $parameters;
    static protected $handle;
    // }}}
    // {{{ registerStream
    public static function registerStream($protocol, array $parameters = [])
    {
        $class = get_called_class();
        static::$parameters = $parameters;

        if (in_array($protocol, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }
        stream_wrapper_register($protocol, $class);
    }
    // }}}

    // {{{ getParameter
    protected function getParameter($parameter)
    {
        return isset(static::$parameters[$parameter]) ? static::$parameters[$parameter] : null;
    }
    // }}}
    // {{{ createHandle
    protected function createHandle($url, $hostOnly = false)
    {
        $parsed = Fs::parseUrl($url);
        $parsed['path'] = str_replace(" ", "%20", $parsed['path']);
        $host = preg_replace('#' . preg_quote($parsed['path']) . '(/)?$#', '', $url);
        $path = (isset($parsed['path'])) ? $parsed['path'] : '/';
        $url = str_replace(" ", "%20", $url);

        if (static::$handle) {
            curl_reset(static::$handle);
            $this->curlSet(CURLOPT_URL, ($hostOnly) ? $host : $url);
        } else {
            static::$handle = ($hostOnly) ? curl_init($host) : curl_init($url);
        }

        if (!static::$handle) {
            trigger_error('Could not initialize cURL.', E_USER_ERROR);
        }

        $username = $parsed['user'];
        $password = (isset($parsed['pass'])) ? $parsed['pass'] : '';

        $options = [
            CURLOPT_USERPWD        => $username . ':' . $password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PORT           => (isset($parsed['port'])) ? $parsed['port'] : 21,
            CURLOPT_FOLLOWLOCATION => true,
            //CURLOPT_VERBOSE        => true,
        ];

        if ($parsed['scheme'] == "ftps") {
            $options += [
                CURLOPT_FTP_SSL        => CURLFTPSSL_TRY, // require SSL For both control and data connections
                CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
            ];
        }

        if ($this->getParameter('timeout')) {
            $options[CURLOPT_TIMEOUT] = $this->getParameter('timeout');
        }

        if ($this->getParameter('caCert')) {
            $options += [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => $this->getParameter('caCert'),
            ];
        }

        // cURL FTP enables passive mode by default, so disable it by enabling the PORT command and allowing cURL to select the IP address for the data connection
        if (!$this->getParameter('passive') === false) {
            $options[CURLOPT_FTPPORT] = '-';
        }

        foreach ($options as $option => $value) {
            $this->curlSet($option, $value);
        }

        return $path;
    }
    // }}}
    // {{{ disconnect
    public static function disconnect()
    {
        static::$handle = null;
    }
    // }}}
    // {{{ curlSet
    protected function curlSet($option, $value)
    {
        if (!curl_setopt(static::$handle, $option, $value)) {
            trigger_error(sprintf('Could not set cURL option: %s', $option), E_USER_ERROR);
        }
    }
    // }}}
    // {{{ execute
    protected function execute()
    {
        $result = curl_exec(static::$handle);

        if (
            $result === false
            && $this->getParameter('ssl') === false
        ) {
            $this->curlSet(CURLOPT_SSL_VERIFYPEER, false);
            $this->curlSet(CURLOPT_SSL_VERIFYHOST, false);

            $result = curl_exec(static::$handle);
        }

        if (
            $result === false
            && curl_errno(static::$handle) !== 9
            && curl_errno(static::$handle) !== 21
        ) {
            trigger_error(curl_error(static::$handle), E_USER_ERROR);
        }

        return $result;
    }
    // }}}
    // {{{ executeFtpCommand
    protected function executeFtpCommand($command, $url)
    {
        $path = $this->createHandle($url, true);
        $this->curlSet(CURLOPT_QUOTE, [$command . ' ' . $path]);
        $this->curlSet(CURLOPT_NOBODY, true);

        $result = $this->execute();

        return ($result === false) ? false : true;
    }
    // }}}

    // {{{ isDir
    protected function isDir($url)
    {
        return $this->executeFtpCommand('CWD', $url);
    }
    // }}}
    // {{{ mkdirRecursive
    protected function mkdirRecursive($url)
    {
        $result = true;

        if (!$this->isDir($url)) {
            $path = explode('/', $url);
            array_pop($path);
            $prev = implode('/', $path);
            if (!$this->isDir($prev)) {
                $result = $this->mkdirRecursive($prev);
            }
            $result = $result && $result = $this->executeFtpCommand('MKD', $url);
        }

        return $result;
    }
    // }}}

    // {{{ stream_open
    public function stream_open($url, $mode, $options, &$openedPath)
    {
        $this->url = $url;
        $this->mode = $mode;
        $this->pos = 0;

        $this->createHandle($url);

        if ($this->mode == 'wb') {
            $this->curlSet(CURLOPT_UPLOAD, true);
            $this->buffer = fopen('php://temp' , 'w+b');
        } else {
            $this->buffer = $this->execute();
        }

        return true;
    }
    // }}}
    // {{{ stream_close
    public function stream_close()
    {
    }
    // }}}
    // {{{ stream_read
    public function stream_read($count)
    {
        $read = false;

        if (strlen($this->buffer) > 0) {
            $read = substr($this->buffer, $this->pos, $count);
            $this->pos += $count;
        }

        return $read;
    }
    // }}}
    // {{{ stream_write
    public function stream_write($data)
    {
        $bytesWritten = fwrite($this->buffer, $data);
        $this->pos += $bytesWritten;

        return $bytesWritten;
    }
    // }}}
    // {{{ stream_eof
    public function stream_eof()
    {
        $eof = false;

        if ($this->pos >= strlen($this->buffer)) {
            $eof = true;
        }

        return $eof;
    }
    // }}}
    // {{{ stream_tell
    public function stream_tell()
    {
        return $this->pos;
    }
    // }}}
    // {{{ stream_flush
    public function stream_flush()
    {
        $result = true;

        if ($this->mode == 'wb') {
            rewind($this->buffer);

            $this->curlSet(CURLOPT_INFILE, $this->buffer);
            $this->curlSet(CURLOPT_INFILESIZE, $this->pos);
            $this->curlSet(CURLOPT_BINARYTRANSFER, true);

            $result = ($this->execute() === false) ? false : true;
        }

        $this->buffer = null;
        $this->pos = null;

        return $result;
    }
    // }}}
    // {{{ stream_stat
    public function stream_stat()
    {
        $this->createHandle($this->url);

        $stat = $this->createStat();
        $this->setStat($stat, 'size', strlen($this->buffer));

        return $stat;
    }
    // }}}
    // {{{ url_stat
    public function url_stat($url, $flags)
    {
        $urlArray = explode('/', $url);
        $nodeName = array_pop($urlArray);
        $url = implode('/', $urlArray) . '/';

        $stat = false;
        $this->createHandle($url, false);
        $this->curlSet(CURLOPT_CUSTOMREQUEST, 'LIST -a');
        $result = $this->execute();

        if ($result) {
            $nodes = $this->parseLs($result);

            if (isset($nodes[$nodeName])) {
                $stat = $this->createStat();
                $node = $nodes[$nodeName];

                $this->setStat(
                    $stat,
                    'mode',
                    octdec($this->translateFileType($node['type']) . $this->translatePermissions($node['permissions']))
                );
            }
        }

        return $stat;
    }
    // }}}
    // {{{ dir_opendir
    public function dir_opendir($url, $options)
    {
        $this->dirPos = 0;

        $path = $this->createHandle($url, true);
        $this->curlSet(CURLOPT_CUSTOMREQUEST, 'LIST -a ' . $path);
        $result = $this->execute();

        $this->files = ($result) ? array_keys($this->parseLs($result)) : [];

        return (bool) $result;
    }
    // }}}
    // {{{ dir_readdir
    public function dir_readdir()
    {
        $result = false;

        if (isset($this->files[$this->dirPos])) {
            $result = $this->files[$this->dirPos];
            $this->dirPos++;
        }

        return $result;
    }
    // }}}
    // {{{ mkdir
    public function mkdir($url, $mode, $options)
    {
        if ($options & STREAM_MKDIR_RECURSIVE) {
            $result = $this->mkdirRecursive($url);
        } else {
            $result = $this->executeFtpCommand('MKD', $url);
        }

        return $result;
    }
    // }}}
    // {{{ unlink
    public function unlink($url)
    {
        return $this->executeFtpCommand('DELE', $url);
    }
    // }}}
    // {{{ rmdir
    public function rmdir($url)
    {
        return $this->executeFtpCommand('RMD', $url);
    }
    // }}}
    // {{{ rename
    public function rename($urlFrom, $urlTo)
    {
        $pathFrom = $this->createHandle($urlFrom, true);
        $pathTo = $this->createHandle($urlTo, true);

        $this->curlSet(CURLOPT_QUOTE, ['RNFR ' . $pathFrom, 'RNTO ' . $pathTo]);

        return ($this->execute() === false) ? false : true;
    }
    // }}}

    // {{{ createStat
    protected function createStat()
    {
        $stat = [];

        foreach($this->translation as $index => $name) {
            $stat[$index] = 0;
            $stat[$name] = 0;
        }

        return $stat;
    }
    // }}}
    // {{{ setStat
    protected function setStat(&$stat, $name, $value)
    {
        $stat[$name] = $value;
        $stat[array_search($name, $this->translation)] = $value;
    }
    // }}}
    // {{{ parseLs
    protected function parseLs($ls)
    {
        $list = explode(PHP_EOL, trim($ls));

        $nodes = [];
        foreach ($list as $line) {
            if ($line) {
                $split = preg_split('/\s+/', $line);

                $info['type'] = $split[0][0];
                $info['permissions'] = substr(array_shift($split),1);
                $info['hardlinks'] = array_shift($split);
                $info['user'] = array_shift($split);
                $info['group'] = array_shift($split);
                $info['size'] = array_shift($split);
                $name = implode(' ', array_splice($split, 3));
                $info['mtime'] = strtotime(implode(' ', $split));

                $nodes[$name] = $info;
            }
        }

        return $nodes;
    }
    // }}}

    // {{{ translateFileType
    protected function translateFileType($char)
    {
        $type = false;

        switch ($char) {
            case '-': $type = 100; break;
            case 'd': $type = 40; break;
        }

        return $type;
    }
    // }}}
    // {{{ translatePermissions
    protected function translatePermissions($permissions)
    {
        $result = '';

        foreach (str_split($permissions, 3) as $operator) {
            $numerical = 0;

            for ($i = 0; $i < 2; $i++) {
                if ($operator[$i] !== '-') {
                    $numerical += pow(2, (2 - $i));
                }
            }

            $result .= $numerical;
        }

        return $result;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker :
