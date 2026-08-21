<?php

namespace Depage\Transformer;

class History extends Preview
{
    protected $previewType = "history";
    protected $profiling = false;

    // {{{ display()
    /**
     * @brief display
     *
     * @param mixed $urlPath, $lang
     * @return void
     **/
    public function display($urlPath, $lang)
    {
        try {
            return parent::display($urlPath, $lang);
        } catch (\Exception $e) {
            throw new \Exception("Could not display old version\n" . $e->getMessage());
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
