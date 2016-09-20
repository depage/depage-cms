.PHONY: build run kill enter

build:
	docker build --rm -t depage-fs-test .

run: kill
	docker run -d --name mock-server --net host -v $(CURDIR)/home:/home/testuser depage-fs-test

kill:
	-docker kill mock-server
	-docker rm mock-server

enter:
	docker exec -it mock-server sh -c "export TERM=xterm && bash"
