<?php

namespace Depage\Fs\Streams;

use Depage\Fs\Exceptions\FsException;

class FtpCurl
{
    // {{{ variables
    public $context;

    protected $path;
    protected $mode;
    protected $curlOptions;
    protected $opened_path;
    protected $buffer;
    protected $pos;
    protected $dirPos;
    protected $ch;
    protected $username;
    protected $password;
    protected $translation = [
        'dev' => 0,
        'ino' => 1,
        'mode' => 2,
        'nlink' => 3,
        'uid' => 4,
        'gid' => 5,
        'rdev' => 6,
        'size' => 7,
        'atime' => 8,
        'mtime' => 9,
        'ctime' => 10,
        'blksize' => 11,
        'blocks' => 12,
    ];

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
    // {{{ createHandle
    protected function createHandle($path)
    {
        $url = self::parseUrl($path);

        if ($this->ch) {
            $initialPath = (isset($url['path'])) ? $url['path'] : '/';
            $this->url = "{$url['scheme']}://{$url['host']}{$initialPath}";
            $this->curlSet(CURLOPT_URL, $this->url);
        } else {
            $username = $url['user'];
            $password = (isset($url['pass'])) ? $url['pass'] : '';
            $port = (isset($url['port'])) ? $url['port'] : 21;
            $initialPath = (isset($url['path'])) ? $url['path'] : '/';

            $passive_mode = true;
            $this->url = "{$url['scheme']}://{$url['host']}{$initialPath}";

            $this->ch = curl_init($this->url);

            if (!$this->ch) {
                throw new FsException('Could not initialize cURL.');
            }

            $this->curlOptions = [
                CURLOPT_USERPWD        => $username . ':' . $password,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FTP_SSL        => CURLFTPSSL_ALL, // require SSL For both control and data connections
                CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PORT           => $port,
                CURLOPT_TIMEOUT        => 30,
            ];

            if (isset(static::$parameters['caCert'])) {
                $this->curlOptions[CURLOPT_CAINFO] = static::$parameters['caCert'];
            }

            // cURL FTP enables passive mode by default, so disable it by enabling the PORT command and allowing cURL to select the IP address for the data connection
            if (!$passive_mode) {
                $this->curlOptions[CURLOPT_FTPPORT] = '-';
            }

            foreach ($this->curlOptions as $option => $value) {
                $this->curlSet($option, $value);
            }

            $this->pos = 0;
        }
    }
    // }}}
    // {{{ curlSet
    protected function curlSet($option, $value)
    {
        if (!curl_setopt($this->ch, $option, $value)) {
            throw new FsException(sprintf('Could not set cURL option: %s', $option));
        }
    }
    // }}}
    // {{{ execute
    protected function execute()
    {
        return curl_exec($this->ch);
    }
    // }}}

    // {{{ stream_open
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;
        $this->opened_path = $opened_path;
        $this->createHandle($path);

        $this->curlSet(CURLOPT_FOLLOWLOCATION, true);

        if ($this->mode == 'wb') {
            $this->curlSet(CURLOPT_UPLOAD, true);

            $this->buffer = fopen('php://temp' , 'w+b');
            $this->pos = 0;
        } else {
            $this->buffer = $this->execute();
        }

        return true;
    }
    // }}}
    // {{{ stream_close
    public function stream_close()
    {
        curl_close($this->ch);
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
        $size = strlen($data);

        fwrite($this->buffer, $data);
        $this->pos += $size;

        return $size;
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
        $stat = array('size' => strlen($this->buffer));

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

        $result = curl_exec($this->ch);

        if ($result === false) {
            $this->createHandle($path . '/');

            $this->curlSet(CURLOPT_URL, $path . '/');

            $result = curl_exec($this->ch);

            if ($result !== false) {
                $stat = $this->createStat();
                $this->setStat($stat, 'mode', octdec(40644));
            }
        } else {
            $info = curl_getinfo($this->ch);

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
        $this->createHandle($path . '/');

        $this->curlSet(CURLOPT_FTPLISTONLY, true);

        $result = $this->execute();

        $this->files = explode("\n", trim($result));
        $this->dirPos = 0;

        return true;
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
    public function mkdir($path, $mode, $options)
    {
        $this->createHandle($path);

        $url = $this->parseUrl($path);

        $this->curlSet(CURLOPT_URL, $this->addTrailingSlash($path));

        if ($options & STREAM_MKDIR_RECURSIVE) {
            $this->curlSet(CURLOPT_FTP_CREATE_MISSING_DIRS, true);
        }

        return (bool) $this->execute();
    }
    // }}}
    // {{{ unlink
    public function unlink($path)
    {
        $this->createHandle($path);

        $url = $this->parseUrl($path);

        $this->curlSet(CURLOPT_POSTQUOTE, ['DELE ' . $url['path']]);

        return (bool) $this->execute();
    }
    // }}}
    // {{{ rmdir
    public function rmdir($path)
    {
        $this->createHandle($path);

        $url = $this->parseUrl($path);

        $this->curlSet(CURLOPT_QUOTE, ['RMD ' . $url['path']]);

        return (bool) $this->execute();
    }
    // }}}
    // {{{ rename
    public function rename($path_from, $path_to)
    {
        $parsedFrom = $this->parseUrl($path_from);
        $parsedTo = $this->parseUrl($path_to);

        $this->createHandle($path_from);

        $this->curlSet(CURLOPT_URL, $parsedFrom['scheme'] . '://' . $parsedFrom['host'] . '/');
        $this->curlSet(CURLOPT_POSTQUOTE, ['RNFR ' . $parsedFrom['path'], 'RNTO ' . $parsedTo['path']]);

        return (bool) $this->execute();
    }
    // }}}

    // {{{ parseUrl
    public static function parseUrl($url)
    {
        $parsed = parse_url($url);

        // hack, parse_url matches anything after the first question mark as "query"
        $path = (isset($parsed['path'])) ? $parsed['path'] : null;
        $query = (isset($parsed['query'])) ? $parsed['query'] : null;
        if ($query !== null || preg_match('/\?$/', $url)) {
            $parsed['path'] = $path . '?' . $query;
            unset($parsed['query']);
        }

        return $parsed;
    }
    // }}}
    // {{{ createStat
    protected function createStat()
    {
        $stat = [];

        foreach($this->translation as $name => $index) {
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
        $stat[$this->translation[$name]] = $value;
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
