RM = rm -rf
I18N = ~/Dev/depage-cms/www/framework/i18n.sh
JSMIN = ~/Dev/depage-cms/www/framework/JsMin/minimize

SASSDIR = www/framework/Cms/sass/
CSSDIR = www/framework/Cms/css/
JSDIR = www/framework/Cms/js/

.PHONY: all min minjs locale locale-php sass sassc push pushdev pushlive doc clean

all: locale min

min: sassc

locale:
	cd www/framework/ ; $(I18N)
	php www/framework/Cms/js/locale.php

tags:  $(wildcard www/framework/**/*.php)
	phpctags -R -C tags-cache

doc:
	cd Docs ; git clone https://github.com/depage/depage-docu.git depage-docu || true
	mkdir -p Docs/html/
	#doxygen Docs/Doxyfile
	doxygen Docs/de/Doxyfile
	cp -r Docs/depage-docu/www/lib Docs/html/de/
	cp -r Docs/de/images/ Docs/html/de/images/

clean:
	$(RM) Docs/depage-docu/ Docs/html/

$(CSSDIR)%.css: $(SASSDIR)%.scss $(SASSDIR)modules/*.scss www/framework/HtmlForm/lib/sass/*.scss
	sassc --style compressed $< $@

sassc: $(patsubst %.scss,$(CSSDIR)%.css, $(notdir $(wildcard $(SASSDIR)*.scss)))

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
	    www/framework www/conf www/index.php jonas@twins:/var/www/depagecms/net.depage.edit/
