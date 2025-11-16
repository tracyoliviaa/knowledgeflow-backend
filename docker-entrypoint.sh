#!/bin/bash
set -e

echo "🚀 Starting KnowledgeFlow Backend..."

# Create .env file from environment variables
cat > .env << EOF
APP_ENV=${APP_ENV:-prod}
APP_SECRET=${APP_SECRET}
DATABASE_URL=${DATABASE_URL}
JWT_SECRET_KEY=${JWT_SECRET_KEY:-config/jwt/private.pem}
JWT_PUBLIC_KEY=${JWT_PUBLIC_KEY:-config/jwt/public.pem}
JWT_PASSPHRASE=${JWT_PASSPHRASE}
DEFAULT_URI=${DEFAULT_URI:-http://localhost}
CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-*}
EOF

echo "✅ Environment file created"

# Wait for database to be ready (with improved connection check)
echo "⏳ Waiting for database connection..."
MAX_TRIES=30
COUNTER=0

until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1 || [ $COUNTER -eq $MAX_TRIES ]; do
  COUNTER=$((COUNTER+1))
  echo "DB not ready (attempt $COUNTER/$MAX_TRIES). Sleeping 2s..."
  sleep 2
done

if [ $COUNTER -eq $MAX_TRIES ]; then
  echo "⚠️  Database not ready after $MAX_TRIES attempts. Continuing anyway..."
  echo "   You may need to run migrations manually."
else
  echo "✅ Database connection successful!"
  
  # Run migrations
  echo "🔄 Running database migrations..."
  if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
    echo "✅ Migrations completed successfully"
  else
    echo "⚠️  Migrations failed or no migrations to run"
  fi
fi

# Clear and warm up cache
echo "🔥 Warming up cache..."
php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod

echo "✅ Cache warmed up successfully"

# Start Apache
echo "🌐 Starting Apache web server..."
exec apache2-foreground