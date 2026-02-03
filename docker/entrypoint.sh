#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Attendre que la base de données soit prête
echo "⏳ Waiting for database to be ready..."
max_attempts=30
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if php artisan db:show &> /dev/null; then
        echo "✅ Database is ready!"
        break
    fi
    echo "⏳ Attempt $((attempt + 1))/$max_attempts - Database not ready yet..."
    sleep 2
    attempt=$((attempt + 1))
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ Could not connect to database after $max_attempts attempts"
    echo "⚠️  Continuing anyway..."
fi

# Exécuter les migrations
echo "🔄 Running database migrations..."
php artisan migrate --force || echo "⚠️  Migrations failed or already run"

# Clear et cache les configurations
echo "🧹 Clearing and caching configurations..."
php artisan config:clear
php artisan config:cache
php artisan route:cache

echo "✅ Application setup complete!"

# Exécuter la commande passée en argument (php-fpm)
exec "$@"