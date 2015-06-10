RM = rm -rf

test:
	mkdir -p cache
	phpunit --bootstrap Bootstrap.php --coverage-html phpunit/ .

clean:
	${RM} phpunit
	${RM} cache
