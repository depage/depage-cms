RM = rm -rf

test:
	mkdir -p output
	XDEBUG_MODE=coverage phpunit --bootstrap bootstrap.php --coverage-html phpunit/ .

clean:
	${RM} phpunit
	${RM} output
