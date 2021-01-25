<?php

/**
 * Override graphics class to access protected methods/attributes in
 * tests
 **/
class imgurlTestClass extends \Depage\Graphics\Imgurl
{
    // {{{ analyze()
    /**
     * @brief analyze
     *
     * @param mixed $
     * @return void
     **/
    public function analyze($url)
    {
        return parent::analyze($url);
    }
    // }}}
    // {{{ getActions()
    public function getActions()
    {
        return $this->actions;
    }
    // }}}
    // {{{ getNotFound()
    public function getNotFound()
    {
        return $this->notFound;
    }
    // }}}
    // {{{ getInvalidAction()
    public function InvalidAction()
    {
        return $this->invalidAction;
    }
    // }}}
    // {{{ getSrcImg()
    public function getSrcImg()
    {
        return $this->srcImg;
    }
    // }}}
    // {{{ getOutImg()
    public function getOutImg()
    {
        return $this->outImg;
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
