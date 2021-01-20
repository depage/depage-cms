RM = rm -rf

test:
	mkdir -p output
	../vendor/bin/phpunit --bootstrap bootstrap.php --coverage-html phpunit/ .

clean:
	${RM} phpunit
	${RM} output
