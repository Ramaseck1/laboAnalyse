#!/bin/sh

# Attendre que MySQL soit prêt
echo "Attente de la base de données..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    sleep 2
done

# Lancer migrations Laravel
php artisan migrate --force

# Lancer PHP-FPM
exec "$@"
