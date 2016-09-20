RM = rm -rf

PHONY: test server clean

test: server
	phpunit --configuration PHPUnit.xml .

server:
	$(MAKE) -C docker
	$(MAKE) run -C docker

clean:
	${RM} Report Temp
