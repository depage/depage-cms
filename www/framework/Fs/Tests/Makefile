RM = rm -rf

PHONY: test server clean

test: server
	../vendor/bin/phpunit --configuration PHPUnit.xml .

server:
	$(MAKE) -C docker
	$(MAKE) run -C docker

clean:
	${RM} Report Temp docker/ssl/ca.pem
	$(MAKE) clean -C docker
