depage-cms
==========

depage-cms a user friendly content management system (cms)
for small and medium sized websites.
At the same time it is an easily extensible php-framework to generate
dynamic web-pages and web-applications.

Features
--------

- autoloader for subclasses (no 'includes' and 'requires' anymore)
- two templating systems
    - easy php-based templating system for dynamically generated pages
    - xslt-based templating system for user editable pages
- mysql-based xml-database
- depage-forms included, for easy and comfortable html5-form-generation
- automatic minimization of javascript- and css-files
- caching class which can use different backends
    - file
    - redis

Prerequisites
-------------

- PHP 7.0 with following modules
    - domxml-support
    - xslt-support
    - gettext
- MySQL
    - MySQL with InnoDB support (for xmldb)
- redis (if you want to use caching with redis)

For more information
--------------------

You can fork us at:
http://github.com/depage/depage-cms/

You find the documentation at:
http://docs.depage.net/depage-cms-manual/en/

For more information about depage-cms go to:
http://www.depagecms.net/

License (dual)
--------------

- GPL2: http://www.gnu.org/licenses/gpl-2.0.html
- MIT: http://www.opensource.org/licenses/mit-license.php


