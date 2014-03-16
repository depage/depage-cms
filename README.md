depage-graphics
==========

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

Basic Usage
-----------
    
Basic usage as image service with the gd library and no image optimization:

    <?php
    require_once(__DIR__ . "/php/Graphics/Graphics.php");

    $imgurl = new Depage\Graphics\Imgurl();
    $imgurl->render()->display();

Extended usage as image service with the imagemagick as conversion utility
and jpegtran and optipng as optimization tools:

    <?php
    require_once(__DIR__ . "/php/Graphics/Graphics.php");

    $options = array(
        'extension' => "im",
        'executable' => "/usr/bin/convert",
        'optimize' => true,
        'optimizers' => array(
            'jpegtran' => "/usr/bin/jpegtran",
            'optipng' => "/usr/bin/optipng",
        ),
    );

    $imgurl = new Depage\Graphics\Imgurl($options);
    $imgurl->render()->display();

nginx configuration
-------------------

    location /basedirectory {
        location ~ (?i)/basedirectory/(.*)\.(jpg|jpeg|gif|png)\.([^/]*)\.(jpg|jpeg|gif|png)$ {
            rewrite (?i)^(/basedirectory)/(?!lib/cache/graphics)(.*)$ $1/lib/cache/graphics/$2 last;                                                       

            try_files $uri /basedirectory/lib/global/getimage.php;
        }

        try_files $uri $uri/;
    }


Apache configuration
--------------------


Install Using Composer
----------------------
Get composer at <http://getcomposer.org> and then just add this to your composer.json.

    {
        "require": {
            "depage/graphics": "*"
        }
    }

Now run

    composer install

to install the current version of depage-htmlform into your vendor dir.


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



