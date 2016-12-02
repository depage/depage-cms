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
    protected $handle;
    protected $translation = ['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks'];

    static protected $parameters;
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
    protected function createHandle($path)
    {
        $url = Fs::parseUrl($path);
        $initialPath = (isset($url['path'])) ? $url['path'] : '/';
        $this->url = "{$url['scheme']}://{$url['host']}{$initialPath}";

        if ($this->handle) {
            $this->curlSet(CURLOPT_URL, $this->url);
        } else {
            $this->handle = curl_init($this->url);
            if (!$this->handle) {
                trigger_error('Could not initialize cURL.', E_USER_ERROR);
            }

            $username = $url['user'];
            $password = (isset($url['pass'])) ? $url['pass'] : '';

            $options = [
                CURLOPT_USERPWD        => $username . ':' . $password,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FTP_SSL        => CURLFTPSSL_ALL, // require SSL For both control and data connections
                CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PORT           => (isset($url['port'])) ? $url['port'] : 21,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
            ];

            if ($this->getParameter('caCert')) {
                $options[CURLOPT_CAINFO] = $this->getParameter('caCert');
            }

            // cURL FTP enables passive mode by default, so disable it by enabling the PORT command and allowing cURL to select the IP address for the data connection
            if (!$this->getParameter('passive') === false) {
                $options[CURLOPT_FTPPORT] = '-';
            }

            foreach ($options as $option => $value) {
                $this->curlSet($option, $value);
            }
        }
    }
    // }}}
    // {{{ curlSet
    protected function curlSet($option, $value)
    {
        if (!curl_setopt($this->handle, $option, $value)) {
            trigger_error(sprintf('Could not set cURL option: %s', $option), E_USER_ERROR);
        }
    }
    // }}}
    // {{{ execute
    protected function execute()
    {
        $result = curl_exec($this->handle);

        if (
            $result === false
            && $this->getParameter('ssl') === false
        ) {
            $this->curlSet(CURLOPT_SSL_VERIFYPEER, false);
            $this->curlSet(CURLOPT_SSL_VERIFYHOST, false);

            $result = curl_exec($this->handle);
        }

        if (
            $result === false
            && curl_errno($this->handle) !== 9
            && curl_errno($this->handle) !== 21
        ) {
            trigger_error(curl_error($this->handle), E_USER_ERROR);
        }

        return $result;
    }
    // }}}

    // {{{ stream_open
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->createHandle($path);
        $this->pos = 0;

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
        curl_close($this->handle);
    }
    // }}}
    // {{{ stream_read
    public function stream_read($count)
    {
        if (strlen($this->buffer) == 0) {
            return false;
        }

        $read = substr($this->buffer, $this->pos, $count);
        $this->pos += $count;

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
        if ($this->pos >= strlen($this->buffer)) {
            return true;
        }

        return false;
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

            $result = (bool) $this->execute();
        }

        $this->buffer = null;
        $this->pos = null;

        return $result;
    }
    // }}}
    // {{{ stream_stat
    public function stream_stat()
    {
        $this->createHandle($this->path);

        $stat = $this->createStat();
        $this->setStat($stat, 'size', strlen($this->buffer));

        return $stat;
    }
    // }}}
    // {{{ url_stat
    public function url_stat($path, $flags)
    {
        $stat = false;

        $this->createHandle($path);

        $this->curlSet(CURLOPT_NOBODY, true);
        $this->curlSet(CURLOPT_HEADER, true);
        $this->curlSet(CURLOPT_FILETIME, true);

        $result = curl_exec($this->handle);

        if ($result === false) {
            $this->curlSet(CURLOPT_URL, $this->addTrailingSlash($path));

            $result = $this->execute();

            if ($result !== false) {
                $stat = $this->createStat();
                $this->setStat($stat, 'mode', octdec(40644));
            }
        } else {
            $info = curl_getinfo($this->handle);

            $stat = $this->createStat();
            $this->setStat($stat, 'mtime', (int) $info['filetime']);
            $this->setStat($stat, 'atime', -1);
            $this->setStat($stat, 'ctime', -1);
            $this->setStat($stat, 'size', (int) $info['download_content_length']);
            $this->setStat($stat, 'mode', octdec(100644));
        }

        return $stat;
    }
    // }}}
    // {{{ dir_opendir
    public function dir_opendir($path, $options)
    {
        $this->dirPos = 0;

        $this->createHandle($this->addTrailingSlash($path));

        $this->curlSet(CURLOPT_FTPLISTONLY, true);

        $result = $this->execute();
        if ($result) {
            $this->files = explode("\n", trim($result));
        }

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
    // {{{ executeFtpCommand
    protected function executeFtpCommand($command, $path)
    {
        $parsed = Fs::parseUrl($path);
        $path = preg_replace('#' . preg_quote($parsed['path']) . '(/)?$#', '', $path);

        $this->createHandle($path);
        $this->curlSet(CURLOPT_QUOTE, [$command . ' ' . $parsed['path']]);

        return (bool) $this->execute();
    }
    // }}}
    // {{{ mkdir
    public function mkdir($path, $mode, $options)
    {
        if ($options & STREAM_MKDIR_RECURSIVE) {
            $this->createHandle($this->addTrailingSlash($path));
            $this->curlSet(CURLOPT_FTP_CREATE_MISSING_DIRS, true);

            $result = (bool) $this->execute();
        } else {
            $result = $this->executeFtpCommand('MKD', $path);
        }

        return $result;
    }
    // }}}
    // {{{ unlink
    public function unlink($path)
    {
        return $this->executeFtpCommand('DELE', $path);
    }
    // }}}
    // {{{ rmdir
    public function rmdir($path)
    {
        return $this->executeFtpCommand('RMD', $path);
    }
    // }}}
    // {{{ rename
    public function rename($path_from, $path_to)
    {
        $parsedFrom = Fs::parseUrl($path_from);
        $parsedTo = Fs::parseUrl($path_to);

        $path = preg_replace('#' . preg_quote($parsedFrom['path']) . '(/)?$#', '', $path_from);
        $this->createHandle($path);

        $this->curlSet(CURLOPT_QUOTE, ['RNFR ' . $parsedFrom['path'], 'RNTO ' . $parsedTo['path']]);

        return (bool) $this->execute();
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
    // {{{ addTrailingSlash
    protected function addTrailingSlash($string)
    {
        if (substr($string, -1) !== '/') {
            $string .= '/';
        }

        return $string;
    }
    // }}}
}
