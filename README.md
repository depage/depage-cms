depage-graphics
===============

depage-graphics is a helper class to get resized and optimized images
and thumbnails.

[![Latest Stable Version](https://poser.pugx.org/depage/graphics/v/stable.png)](https://packagist.org/packages/depage/graphics) [![Build Status](https://travis-ci.org/depage/depage-graphics.png?branch=master)](https://travis-ci.org/depage/depage-graphics)

Features
--------

- supports gd as basic image resizing method
- support imagemagick and graphicsmagick binaries for better image quality support
- support external tools to optimize images like
    - jpegtran
    - jpegoptim
    - optipng
    - pngcrush
- composer support

You can compare depage-graphics to [SLIR, Smart Lencioni Image Resizer](https://github.com/lencioni/SLIR).
But where SLIR is only gd-based, you can use Imagemagick or Graphicsmagick as conversion tools to achieve better image quality.


Basic Usage as Image Service
----------------------------

You can use the graphics class as an image service to automatically resize,
crop, convert and optimize your images, or to generate thumbnails.

To convert your image you add specific action to your image:

    /path-to/image.jpg.resize-400x400.jpg
    /path-to/image.jpg.r400x400.jpg

    /path-to/image.jpg.thumb-100x75.jpg
    /path-to/image.jpg.t100x75.jpg

    /path-to/image.jpg.crop-200x200-10x10.jpg
    /path-to/image.jpg.c200x200-10x10.jpg

You can also chain actions and parameters:

    /path-to/image.png.resize-400x400.quality-50.background-ffffff.jpg
    /path-to/image.png.r400x400.q50.bg-ffffff.jpg

This would resize your image to 400x400px set the jpeg quality to 50 and
add a white background for transparent pixels.


### Basic image service ###

Basic usage as image service with the gd library and no image optimization:

```php
<?php
    require_once(__DIR__ . "/php/Graphics/Graphics.php");

    $imgurl = new Depage\Graphics\Imgurl();
    $imgurl->render()->display();
```

### Extended Usage with Options ###

Extended usage as image service with the imagemagick as conversion utility
and jpegtran and optipng as optimization tools:

```php
<?php
    require_once(__DIR__ . "/php/Graphics/Graphics.php");

    $imgurl = new Depage\Graphics\Imgurl(array(
        'extension' => "im",
        'executable' => "/usr/bin/convert",
        'optimize' => true,
        'optimizers' => array(
            'jpegtran' => "/usr/bin/jpegtran",
            'optipng' => "/usr/bin/optipng",
        ),
    ));
    $imgurl->render()->display();
```


Usage as Image-Editing/Conversion Tool
--------------------------------------

```php
<?php
    require_once(__DIR__ . "/php/Graphics/Graphics.php");

    $graphics = new Depage\Graphics\Graphics(array(
        'extension' => "im",
        'executable' => "/usr/bin/convert",
        'optimize' => true,
        'optimizers' => array(
            'jpegtran' => "/usr/bin/jpegtran",
            'optipng' => "/usr/bin/optipng",
        ),
    ));
    $graphics->addResize(400, 400);
    $graphics->addBackground("#ffffff");

    $graphics->render("sourceImage.png", "targetImage.jpg");
```


Webserver Configuration
-----------------------

### nginx ###

```
location /basedirectory {
    location ~ (?i)/basedirectory/(.*)\.(jpg|jpeg|gif|png)\.([^/]*)\.(jpg|jpeg|gif|png)$ {
        rewrite (?i)^(/basedirectory)/(?!lib/cache/graphics)(.*)$ $1/lib/cache/graphics/$2 last;

        try_files $uri /basedirectory/lib/global/getimage.php;
    }

    try_files $uri $uri/;
}
```


### Apache ###

@todo

### .htacess ###

```
RewriteEngine       on
RewriteBase         /

RewriteCond         %{REQUEST_FILENAME}   !-s
RewriteRule         ^(?!lib/cache/graphics)(.*)\.(jpg|jpeg|png|gif)\.([^/]*)\.(jpg|jpeg|png|gif)$ /lib/cache/graphics/$0

RewriteCond         %{REQUEST_FILENAME}   !-s
RewriteRule         ^lib/cache/graphics/(.*)\.(jpg|jpeg|png|gif)\.([^/]*)\.(jpg|jpeg)$ /lib/global/getimage.php [T=image/jpeg]

RewriteCond         %{REQUEST_FILENAME}   !-s
RewriteRule         ^lib/cache/graphics/(.*)\.(jpg|jpeg|png|gif)\.([^/]*)\.(png)$ /lib/global/getimage.php [T=image/png]

RewriteCond         %{REQUEST_FILENAME}   !-s
RewriteRule         ^lib/cache/graphics/(.*)\.(jpg|jpeg|png|gif)\.([^/]*)\.(gif)$ /lib/global/getimage.php [T=image/gif]
```


Install Using Composer
----------------------
Get composer at <http://getcomposer.org> and then just add this to your composer.json.

    {
        "require": {
            "depage/graphics": "dev/master"
        }
    }

Now run

    composer install

to install the current version of depage-graphics into your vendor dir.


Prerequisites
-------------

- PHP 5.3 with following modules
- gd support
- external binaries (optional)
    - convert
    - gm
    - jpegtran
    - jpegoptim
    - optipng
    - pngcrush

For more information
--------------------

You can fork us at:
http://github.com/depage/depage-graphics/

For more information about depage-cms go to:
http://www.depagecms.net/

License (dual)
--------------

- GPL2: http://www.gnu.org/licenses/gpl-2.0.html
- MIT: http://www.opensource.org/licenses/mit-license.php

