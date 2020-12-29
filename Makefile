RM = rm -rf

all: test doc

doc:
	cd Docs ; git clone https://github.com/depage/depage-docu.git depage-docu || true
	doxygen Docs/Doxyfile
	cp -r Docs/depage-docu/www/lib Docs/html/

test:
	cd Tests; $(MAKE) $(MFLAGS)

clean:
	$(RM) Docs/depage-docu/ Docs/html/
	cd Tests; $(MAKE) $(MFLAGS) clean

.PHONY: all
.PHONY: clean
.PHONY: test
.PHONY: doc

