.PHONY: build run kill enter clean

build:
	docker build -t depage-fs-test .

run: kill
	docker run -d --name mock-server --net host -v $(CURDIR)/home:/home/testuser depage-fs-test
	-docker cp mock-server:/etc/ssl/private/ca.pem $(CURDIR)/ssl

kill:
	-docker kill mock-server
	-docker rm mock-server

enter:
	docker exec -it mock-server sh -c "export TERM=xterm && bash"

clean:
	docker rm -f mock-server
	docker rmi -f depage-fs-test:latest
