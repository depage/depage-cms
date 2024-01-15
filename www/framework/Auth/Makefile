RM = rm -rf

all: doc

doc:
	cd Docs ; git clone https://github.com/depage/depage-docu.git depage-docu || true
	doxygen Docs/Doxyfile
	cp -r Docs/depage-docu/www/lib Docs/html/

clean:
	$(RM) Docs/depage-docu/ Docs/html/

.PHONY: all
.PHONY: clean
.PHONY: test
.PHONY: doc


