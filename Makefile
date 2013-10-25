RM = rm -rf

all: test doc

doc:
	cd documentation; $(MAKE) $(MFLAGS)

test:
	cd tests; $(MAKE) $(MFLAGS)

clean:
	cd documentation; $(MAKE) $(MFLAGS) clean
	cd tests; $(MAKE) $(MFLAGS) clean

.PHONY: all
.PHONY: clean
.PHONY: test
.PHONY: doc

