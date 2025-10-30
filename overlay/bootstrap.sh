#!/bin/sh
set -e

cd /var/www/html

if [ ! -f composer.json ]; then
  echo "Installing fresh Laravel..."
  composer create-project --prefer-dist laravel/laravel:^11.0 .
  php artisan key:generate
  php artisan config:clear
  echo "Fresh Laravel installed."
fi

# Overlay project files (controllers, services, migrations, routes, etc.)
if [ -d /app_overlay/src ]; then
  echo "Applying overlay..."
  mkdir -p app/Enums app/Services app/Http/Controllers/Api app/Http/Requests app/Http/Middleware database/migrations database/seeders routes tests/Feature
  cp -r /app_overlay/src/* .
fi

# Ensure storage permissions
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rw storage bootstrap/cache || true

# Start php-fpm
php-fpm -F
