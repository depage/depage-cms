<?php

namespace depage\graphics;

require_once('graphics.php');
require_once('graphics_gd.php');
require_once('graphics_imagemagick.php');
require_once('graphics_graphicsmagick.php');
require_once('graphicsException.php');

$command    = explode('-', $_GET['command']);
$action     = $command[0];
$size       = explode('x', $command[1]);
$root       = $_SERVER['DOCUMENT_ROOT'] . '/depage-cms/';
$file       = $_GET['file'];
$extension  = $_GET['ext'];

$cachedFile = ("{$root}cache/graphics/{$file}.{$action}-{$size[0]}x{$size[1]}.{$extension}");

$img = graphics::factory(array('extension'=>'imagemagick', 'background'=>'transparent'));
$img->{"add$action"}($size[0], $size[1])->render($root . $file, $cachedFile);

if ($extension === 'jpg' || $extension === 'jpeg') {
    header("Content-type: image/jpeg");
    imagejpeg(imagecreatefromjpeg($cachedFile));
} else if ($extension === 'png') {
    header("Content-type: image/png");
    imagejpeg(imagecreatefrompng($cachedFile));
}
