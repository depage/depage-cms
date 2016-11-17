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

        curl_setopt($this->ch, CURLOPT_URL, $path);
        curl_setopt($this->ch, CURLOPT_UPLOAD, false);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

        $this->buffer = curl_exec($this->ch);

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
        if (strlen($this->buffer) == 0) {
            return false;
        }

        return true;
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
    /**
     * Stat the url, return only the size of the buffer
     *
     * @return array stat information
     */
    public function url_stat($path, $flags)
    {
        $this->createHandle($path);

        $stat = [
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

        if (!curl_setopt($this->ch, CURLOPT_URL, $this->url)) {
            throw new FsException ("Could not set cURL directory: $this->url");
        }

        curl_setopt($this->ch, CURLOPT_NOBODY, true );
        curl_setopt($this->ch, CURLOPT_HEADER, true );
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true );
        //curl_setopt( $curl, CURLOPT_USERAGENT, get_user_agent_string() );

        //$info = curl_getinfo($this->ch);

        //$stat['mtime'] = $info['filetime'];
        //$stat = array('size' => strlen($this->buffer));

        return $stat;
    }
    // }}}
    // {{{ dir_opendir
    public function dir_opendir($path, $options)
    {
        $this->createHandle($path);

        if (!curl_setopt($this->ch, CURLOPT_URL, $this->url)) {
            throw new FsException ("Could not set cURL directory: $this->url");
        }

        curl_setopt($this->ch, CURLOPT_UPLOAD, false);
        curl_setopt($this->ch, CURLOPT_FTPLISTONLY, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($this->ch);

        $error = curl_error($this->ch);
        if (!empty($error)) {
            throw new FsException($error);
        }

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

    // {{{ createHandle
    protected function createHandle($path)
    {
        $url = self::parseUrl($path);

        if (!$this->ch) {
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

            $error = curl_error($this->ch);
            if (!empty($error)) {
                throw new FsException($error);
            }

            $options = array(
                CURLOPT_USERPWD        => $username . ':' . $password,
                CURLOPT_SSL_VERIFYPEER => false, // don't verify SSL
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FTP_SSL        => CURLFTPSSL_ALL, // require SSL For both control and data connections
                CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_DEFAULT, // let cURL choose the FTP authentication method (either SSL or TLS)
                CURLOPT_UPLOAD         => true,
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

            // check for successful connection
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
}
