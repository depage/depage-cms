<?php
/**
 * @file    graphics_ui.php
 * @brief   User interface script to access controller
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/

namespace depage\graphics;

require_once('../depage/depage.php');

$controller = new graphics_controller();
$controller->convert();
