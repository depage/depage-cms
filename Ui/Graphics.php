<?php
/**
 * @file    graphics_ui.php
 * @brief   Interface for accessing graphics via URI
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Depage\Graphics\Ui;
/**
 * @brief Interface for accessing graphics via URI
 *
 * Translates request to graphics actions.
 **/
class Graphics extends \Depage\Depage\Ui\Base
{
    /**
     * @brief Default options array for graphics factory
     **/
    public $defaults = array(
        'extension'     => 'gd',
        'executable'    => '',
        'background'    => 'transparent',
    );

    // }}}
    // {{{ notfound()
    public function notfound($function = "")
    {
        $imgurl = new \Depage\Graphics\Imgurl($this->options);
        $imgurl->render()->display();
    }
    // }}}
    // {{{ send_time()
    /**
     * @brief Override depage_ui method
     *
     * @param       $time
     * @return void
     **/
    protected function send_time($time) {}
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
