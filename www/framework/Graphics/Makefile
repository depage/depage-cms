RM = rm -rf
VERSION = $(shell  git describe --tags --abbrev=0)

all: test doc

doc:
	cd Docs ; git clone https://github.com/depage/depage-docu.git depage-docu || true
	( cat Docs/Doxyfile ; echo "PROJECT_NUMBER=$(VERSION)" ) | doxygen -

test:
	cd Tests; $(MAKE) $(MFLAGS)

clean:
	$(RM) Docs/depage-docu/ Docs/html/ vendor/
	cd Tests; $(MAKE) $(MFLAGS) clean

.PHONY: all
.PHONY: clean
.PHONY: test
.PHONY: doc

