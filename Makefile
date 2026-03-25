PHP_VERSIONS := 7.4 8.0 8.1 8.2 8.3 8.4

.PHONY: test $(addprefix test-php,$(PHP_VERSIONS))

test: $(addprefix test-php,$(PHP_VERSIONS))

$(addprefix test-php,$(PHP_VERSIONS)): test-php%:
	@echo "==> Building image for PHP $*"
	docker build -q --build-arg PHP_VERSION=$* -t text-lang-correct-php:$* docker/
	@echo "==> Running tests on PHP $*"
	docker run --rm \
		-v "$(CURDIR):/app" \
		-w /app \
		text-lang-correct-php:$* \
		sh -c "composer install --no-interaction --no-progress --quiet && vendor/bin/phpstan analyse && vendor/bin/phpunit"
