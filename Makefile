#!/usr/bin/make

.PHONY: php-cs-fixer
php-cs-fixer:
	./vendor/bin/php-cs-fixer fix --allow-risky=yes --verbose

.PHONY: psalm
psalm:
	./vendor/bin/psalm --diff