#!/bin/sh
set -e

if [ ! -f composer.json ]; then
  echo "Installing fresh Laravel..."
  composer create-project --prefer-dist laravel/laravel:^11.0 .
  php artisan key:generate
  php artisan config:clear
  echo "Fresh Laravel installed."
fi

# Ensure storage permissions
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rw storage bootstrap/cache || true

# Start php-fpm
php-fpm -F
