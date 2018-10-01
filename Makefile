install:
	composer install

lint:
	composer run-script phpcs -- --standard=PSR2 src bin

lint-fix:
	composer run-script phpcbf -- --standard=PSR2 src bin

test:
	composer run-script phpunit tests