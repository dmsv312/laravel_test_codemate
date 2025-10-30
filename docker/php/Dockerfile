FROM php:8.3-fpm-alpine

RUN apk add --no-cache git curl libzip-dev oniguruma-dev postgresql-dev

RUN docker-php-ext-install pdo pdo_pgsql mbstring zip

# Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Workdir
WORKDIR /var/www/html

# Bootstrap Laravel if src is empty (first run)
COPY bootstrap.sh /usr/local/bin/bootstrap.sh
RUN chmod +x /usr/local/bin/bootstrap.sh

CMD ["/usr/local/bin/bootstrap.sh"]
