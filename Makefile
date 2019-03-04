RM = rm -rf
I18N = ~/Dev/depage-cms/www/framework/i18n.sh
JSMIN = ~/Dev/depage-cms/www/framework/JsMin/minimize
PHP = $(shell which php)

SASSDIR = www/framework/Cms/sass/
CSSDIR = www/framework/Cms/css/
JSDIR = www/framework/Cms/js/

WWWPATH = /var/www/depage-cms/

.PHONY: all min minjs locale locale-php sass sassc push pushdev pushlive doc clean

all: locale min

min: sassc

locale:
	cd www/framework/ ; $(I18N)
	php www/framework/Cms/js/locale.php
	sudo port reload php73-fpm

tags:  $(wildcard www/framework/**/*.php)
	phpctags -R -C tags-cache

doc: Docs/html/de/index.html Docs/html/en/index.html

Docs/html/en/index.html: Docs/en/Doxyfile Docs/en/*.md Docs/en/DoxygenLayout.xml
	cd Docs ; git clone https://github.com/depage/depage-docu.git depage-docu || true
	mkdir -p Docs/html/en/
	doxygen Docs/en/Doxyfile
	cp -r Docs/depage-docu/www/lib Docs/html/en/
	cp -r Docs/de/images Docs/html/en/

Docs/html/de/index.html: Docs/de/Doxyfile Docs/de/*.md Docs/de/DoxygenLayout.xml
	cd Docs ; git clone https://github.com/depage/depage-docu.git depage-docu || true
	mkdir -p Docs/html/de/
	doxygen Docs/de/Doxyfile
	cp -r Docs/depage-docu/www/lib Docs/html/de/
	cp -r Docs/de/images Docs/html/de/

clean:
	$(RM) Docs/depage-docu/ Docs/html/

$(CSSDIR)%.css: $(SASSDIR)%.scss $(SASSDIR)modules/*.scss www/framework/HtmlForm/lib/sass/*.scss
	sassc --style compressed $< $@

sassc: $(patsubst %.scss,$(CSSDIR)%.css, $(notdir $(wildcard $(SASSDIR)*.scss)))

run-taskrunner:
	cd $(WWWPATH) ; sudo -u nobody $(PHP) -f framework/Tasks/TaskRunner.php -- --dp-path $(WWWPATH) --conf-url https://localhost/depage-cms/ --watch

run-socketserver:
	cd $(WWWPATH) ; sudo -u nobody $(PHP) -f framework/WebSocket/Server.php -- --dp-path $(WWWPATH) --conf-url https://localhost/depage-cms/

run-scheduler:
	cd /var/www/depage-cms/ ; sudo -u nobody $(PHP) -f framework/Cms/Scheduler.php  -- --dp-path $(WWWPATH) --conf-url https://localhost/depage-cms/

push: pushlive

pushlive: all
	rsync \
	    -k -r -v -c \
	    --delete \
	    --exclude '.DS_Store' \
	    --exclude '.git' \
	    --exclude 'cache/' \
	    www/framework www/conf www/index.php jonas@depage.net:/var/www/depagecms/net.depage.edit/

pushdev: all
	rsync \
	    -k -r -v -c \
	    --delete \
	    --exclude '.DS_Store' \
	    --exclude '.git' \
	    --exclude 'cache/' \
	    www/framework www/conf www/index.php jonas@depage.net:/var/www/depagecms/net.depage.editbeta/

pushtwins: all
	rsync \
	    -k -r -v -c \
	    --delete \
	    --exclude '.DS_Store' \
	    --exclude '.git' \
	    --exclude 'cache/' \
	 \   www/framework www/conf www/index.php jonas@twins:/var/www/depagecms/net.depage.edit/
