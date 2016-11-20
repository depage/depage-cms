<?php

namespace Depage\Fs\Streams;

use Depage\Fs\Exceptions\FsException;

class FtpCurl
{
    // {{{ variables
    protected $path;
    protected $mode;
    protected $options;
    protected $opened_path;
    protected $buffer;
    protected $pos;
    protected $dirPos;
    protected $ch;
    protected $username;
    protected $password;

    public $context;
    // }}}

    // {{{ stream_open
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;
        $this->opened_path = $opened_path;
        $this->createHandle($path);

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

        if ($this->mode == 'wb') {
            curl_setopt($this->ch, CURLOPT_UPLOAD, true);
        }

        $this->buffer = $this->execute();

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
    /**
     * Read the stream
     *
     * @param int $count number of bytes to read
     * @return content from pos to count
     */
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
    /**
     * write the stream
     *
     * @param int $count number of bytes to write
     * @return content from pos to count
     */
    public function stream_write($data)
    {
        $stream = fopen('data://text/plain,' . $data, 'r');
        $size = strlen($data);

        curl_setopt($this->ch, CURLOPT_INFILE, $stream);
        curl_setopt($this->ch, CURLOPT_INFILESIZE, $size);

        $result = $this->execute();

        if ($result === false) {
            return 0;
        } else {
            return $size;
        }
    }
    // }}}
    // {{{ stream_eof
    /**
     * @return true if eof else false
     */
    public function stream_eof()
    {
        if ($this->pos >= strlen($this->buffer)) {
            return true;
        }

        return false;
    }
    // }}}
    // {{{ stream_tell
    /**
     * @return int the position of the current read pointer
     */
    public function stream_tell()
    {
        return $this->pos;
    }
    // }}}
    // {{{ stream_flush
    public function stream_flush()
    {
        $this->buffer = null;
        $this->pos = null;
    }
    // }}}
    // {{{ stream_stat
    /**
     * Stat the file, return only the size of the buffer
     *
     * @return array stat information
     */
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

        curl_setopt($this->ch, CURLOPT_NOBODY, true);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_FILETIME, true);

        $result = curl_exec($this->ch);

        if ($result === false) {
            $this->createHandle($path . '/');

            curl_setopt($this->ch, CURLOPT_URL, $path . '/');

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

        curl_setopt($this->ch, CURLOPT_FTPLISTONLY, true);

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

        curl_setopt($this->ch, CURLOPT_URL, $this->addTrailingSlash($path));

        if ($options & STREAM_MKDIR_RECURSIVE) {
            curl_setopt($this->ch, CURLOPT_FTP_CREATE_MISSING_DIRS, true);
        }

        return (bool) $this->execute();
    }
    // }}}
    // {{{ unlink
    public function unlink($path)
    {
        $this->createHandle($path);

        $url = $this->parseUrl($path);

        curl_setopt($this->ch, CURLOPT_POSTQUOTE, ['DELE ' . $url['path']]);

        return (bool) $this->execute();
    }
    // }}}
    // {{{ rmdir
    public function rmdir($path)
    {
        $this->createHandle($path);

        $url = $this->parseUrl($path);

        curl_setopt($this->ch, CURLOPT_QUOTE, ['RMD ' . $url['path']]);

        return (bool) $this->execute();
    }
    // }}}
    // {{{ rename
    public function rename($path_from, $path_to)
    {
        $parsedFrom = $this->parseUrl($path_from);
        $parsedTo = $this->parseUrl($path_to);

        $this->createHandle($path_from);

        curl_setopt($this->ch, CURLOPT_URL, $parsedFrom['scheme'] . '://' . $parsedFrom['host'] . '/');
        curl_setopt($this->ch, CURLOPT_POSTQUOTE, ['RNFR ' . $parsedFrom['path'], 'RNTO ' . $parsedTo['path']]);

        return (bool) $this->execute();
    }
    // }}}

    // {{{ createHandle
    protected function createHandle($path)
    {
        $url = self::parseUrl($path);

        if ($this->ch) {
            $initialPath = (isset($url['path'])) ? $url['path'] : '/';
            $this->url = "{$url['scheme']}://{$url['host']}{$initialPath}";
            curl_setopt($this->ch, CURLOPT_URL, $this->url);
        } else {
            $options = [];
            //$options = stream_context_get_options($this->context);

            if (
                !empty($options['ftp']['curl_options'])
                && is_array($options['ftp']['curl_options'])
            ) {
                $curl_options = $options['ftp']['curl_options'];
            } else {
                $curl_options = array();
            }

            $username = $url['user'];
            $password = (isset($url['pass'])) ? $url['pass'] : '';
            $port = (isset($url['port'])) ? $url['port'] : 21;
            $initialPath = (isset($url['path'])) ? $url['path'] : '/';

            $passive_mode = true;
            $this->url = "{$url['scheme']}://{$url['host']}{$initialPath}";

            $this->ch = curl_init($this->url);

            $options = array(
                CURLOPT_USERPWD        => $username . ':' . $password,
                CURLOPT_SSL_VERIFYPEER => false, // @todo
                CURLOPT_SSL_VERIFYHOST => false, // @todo
                CURLOPT_FTP_SSL        => CURLFTPSSL_ALL, // require SSL For both control and data connections
                CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PORT           => $port,
                CURLOPT_TIMEOUT        => 30,
            );

            // cURL FTP enables passive mode by default, so disable it by enabling the PORT command and allowing cURL to select the IP address for the data connection
            if (!$passive_mode) {
                $options[ CURLOPT_FTPPORT ] = '-';
            }

            /*
            if (defined('USE_PROXY') && USE_PROXY) {
                $curl_options[CURLOPT_HTTPPROXYTUNNEL] = true;
                $curl_options[CURLOPT_PROXY] = USE_PROXY;
            }
            */

            if (!$this->ch) {
                throw new FsException('Could not initialize cURL.');
            }

            // set connection options, use foreach so useful errors can be caught instead of a generic "cannot set options" error with curl_setopt_array()
            foreach ($options as $option_name => $option_value) {
                if (!curl_setopt($this->ch, $option_name, $option_value)) {
                    throw new FsException(sprintf('Could not set cURL option: %s', $option_name));
                }
            }

            $this->pos = 0;
        }
    }
    // }}}
    // {{{ execute
    protected function execute()
    {
        return curl_exec($this->ch);
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
        $stat = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
            12 => 0,

            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0,
        ];

        return $stat;
    }
    // }}}
    // {{{ setStat
    protected function setStat(&$stat, $name, $value)
    {
        $translation = [
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

        $stat[$name] = $value;
        $stat[$translation[$name]] = $value;
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
