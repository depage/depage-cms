.PHONY: build run kill enter clean

build:
	docker build -t depage-fs-test .

run: kill
	docker run -d --name mock-server -p 20:20 -p 21:21 -p 22:22 -p 12020:12020 -p 12021:12021 -p 12022:12022 -p 12023:12023 -p 12024:12024 -p 12025:12025 -v $(CURDIR)/home:/home/testuser depage-fs-test
	-docker cp mock-server:/etc/ssl/private/ca.pem $(CURDIR)/ssl

kill:
	-docker kill mock-server
	-docker rm mock-server

enter:
	docker exec -it mock-server sh -c "export TERM=xterm && bash"

clean:
	docker rm -f mock-server
	docker rmi -f depage-fs-test:latest
