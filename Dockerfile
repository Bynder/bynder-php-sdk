FROM composer:latest

# Dummy directory
WORKDIR /var/bynder/app

# These are separte from the copy below
# for caching purposes
COPY composer.json ./
RUN composer install

# Copy ALL the things!
COPY . .

CMD ["vendor/bin/phpunit", "tests", "-c", "phpunit.xml.dist", "--whitelist", "src"]
