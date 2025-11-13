#!/bin/bash
set -e

echo "🚀 Starting application..."

# Run migrations
echo "📦 Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod || echo "⚠️  Migrations failed or no migrations to run"

# Start Apache
echo "🌐 Starting Apache..."
exec apache2-foreground