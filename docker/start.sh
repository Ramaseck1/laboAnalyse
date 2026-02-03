#!/bin/bash
set -e

echo "DB_HOST=$DB_HOST"

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

php-fpm -F
