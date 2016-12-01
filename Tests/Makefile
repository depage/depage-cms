RM = rm -rf

PHONY: test server

test: server
	phpunit --configuration PHPUnit.xml .

server:
	$(MAKE) -C docker
	$(MAKE) run -C docker

clean:
	${RM} Report Temp docker/ssl/ca.pem
	docker rm -f mock-server
	docker rmi -f depage-fs-test:latest
