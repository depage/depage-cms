<?php

require_once('../depage/depage.php');

class graphics_ui {
    public $defaults = array(
        'extension'     => 'gd',
        'background'    => 'transparent',
    );

    public function __construct($options = NULL) {
        $conf = new config($options);
        $this->options = $conf->getDefaultsFromClass($this);
    }

    public function convert() {
        $command    = explode('-', $_GET['command']);
        $action     = $command[0];
        $size       = explode('x', $command[1]);
        $root       = $_SERVER['DOCUMENT_ROOT'] . '/depage-cms/';
        $file       = $_GET['file'];
        $extension  = $_GET['ext'];

        $cachedFile = ("{$root}cache/graphics/{$file}.{$action}-{$size[0]}x{$size[1]}.{$extension}");

        $img = graphics::factory(
            array(
                'extension'     => $this->defaults['extension'],
                'background'    => $this->defaults['background'],
            )
        );

        $cachePath = dirname($cachedFile);
        mkdir($cachePath, 0755, true);

        $img->{"add$action"}($size[0], $size[1])->render($root . $file, $cachedFile);

        if ($extension === 'jpg' || $extension === 'jpeg') {
            header("Content-type: image/jpeg");
            imagejpeg(imagecreatefromjpeg($cachedFile));
        } else if ($extension === 'png') {
            header("Content-type: image/png");
            imagejpeg(imagecreatefrompng($cachedFile));
        }
    }
}

$graphics_ui = new graphics_ui();
$graphics_ui->convert();
