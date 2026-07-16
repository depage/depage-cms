vendor/bin/phpunit:
	composer install

test: vendor/bin/phpunit
	vendor/bin/phpunit --configuration Tests/PhpUnit.xml --no-coverage Tests/

clean:
	rm -rf Tests/Report Tests/.phpunit*  vendor/
