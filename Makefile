run-php-sdk-docker:
	docker-compose up -d

stop-php-sdk-docker:
	docker-compose down

# make execute-php-sdk-sample sample-file-name=BrandsSample.php
# executes command in running container
execute-php-sdk-sample:
	docker-compose exec bynder-php-sdk php /app/sample/$(sample-file-name)
