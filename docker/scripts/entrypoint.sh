#!/bin/bash
set -e

echo "🚀 Starting StreamVibe..."

echo "🔄 Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "🧹 Clearing and warming cache..."
php bin/console cache:clear

echo "🎉 Starting Symfony server..."
exec "$@"