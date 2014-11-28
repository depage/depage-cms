RM = rm -rf

test:
	mkdir -p output
	phpunit --bootstrap bootstrap.php --coverage-html phpunit/ .

clean:
	${RM} phpunit
	${RM} output
