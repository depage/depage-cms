<?php

namespace Depage\Fs;

class PublicSshKey extends SshKey
{
    // {{{ details
    protected function details($path)
    {
        // @todo do proper check
        return file_get_contents($path);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
