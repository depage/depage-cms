<?php

require_once __DIR__ . '/../Graphics.php';

require_once __DIR__ . '/graphicsTestClass.php';
require_once __DIR__ . '/graphics_imagemagickTestClass.php';
require_once __DIR__ . '/graphics_graphicsmagickTestClass.php';
require_once __DIR__ . '/graphics_procTestClass.php';

if (!is_dir(__DIR__ . '/output')) {
    mkdir(__DIR__ . '/output');
}
