RM = rm -rf

all: test doc

doc:
	cd documentation; $(MAKE) $(MFLAGS)

test:
	cd Tests; $(MAKE) $(MFLAGS)

clean:
	cd documentation; $(MAKE) $(MFLAGS) clean
	cd Tests; $(MAKE) $(MFLAGS) clean
	${RM} release

.PHONY: all
.PHONY: clean
.PHONY: test
.PHONY: doc

