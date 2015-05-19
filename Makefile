RM = rm -rf

all: test

test:
	cd Tests; $(MAKE) $(MFLAGS)

clean:
	cd Tests; $(MAKE) $(MFLAGS) clean
