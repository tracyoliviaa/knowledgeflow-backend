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
JWT_PASSPHRASE=${JWT_PASSPHRASE:-}
DEFAULT_URI=${DEFAULT_URI:-http://localhost}
CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-*}
OPENAI_API_KEY=${OPENAI_API_KEY:-}
OPENAI_MODEL=${OPENAI_MODEL:-gpt-4o-mini}
EOF

echo "✅ Environment file created"

# Parse DATABASE_URL to check connection
if [ -n "$DATABASE_URL" ]; then
    echo "📊 Database URL detected, testing connection..."
    
    # Try a simpler connection test using PHP
    MAX_TRIES=15
    COUNTER=0
    
    while [ $COUNTER -lt $MAX_TRIES ]; do
        COUNTER=$((COUNTER+1))
        
        if php -r "
            try {
                \$url = getenv('DATABASE_URL');
                if (preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:\/]+):?(\d+)?\/(.+)/', \$url, \$matches)) {
                    \$host = \$matches[3];
                    \$port = \$matches[4] ?: 5432;
                    \$conn = @fsockopen(\$host, \$port, \$errno, \$errstr, 5);
                    if (\$conn) {
                        fclose(\$conn);
                        exit(0);
                    }
                }
                exit(1);
            } catch (Exception \$e) {
                exit(1);
            }
        " 2>/dev/null; then
            echo "✅ Database port is reachable!"
            break
        else
            if [ $COUNTER -eq $MAX_TRIES ]; then
                echo "⚠️  Database not reachable after $MAX_TRIES attempts"
                echo "⚠️  Starting anyway - migrations will fail but app will run"
                break
            fi
            echo "⏳ Database not ready (attempt $COUNTER/$MAX_TRIES). Sleeping 3s..."
            sleep 3
        fi
    done
    
    # Try to run migrations if DB is available
    if [ $COUNTER -lt $MAX_TRIES ]; then
        echo "🔄 Running database migrations..."
        if timeout 30s php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1; then
            echo "✅ Migrations completed successfully"
        else
            echo "⚠️  Migrations failed - continuing anyway"
        fi
    fi
else
    echo "⚠️  No DATABASE_URL configured"
fi

# Generate JWT keys if they don't exist
if [ ! -f config/jwt/private.pem ]; then
    echo "🔐 Generating JWT keys..."
    mkdir -p config/jwt
    openssl genpkey -out config/jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
    chown -R www-data:www-data config/jwt
    echo "✅ JWT keys generated"
fi

# Clear and warm up cache
echo "🔥 Warming up cache..."
php bin/console cache:clear --no-warmup --env=prod 2>&1 || echo "⚠️  Cache clear failed"
php bin/console cache:warmup --env=prod 2>&1 || echo "⚠️  Cache warmup failed"
echo "✅ Cache operations completed"

# Ensure permissions
chown -R www-data:www-data var/ || true

# Start Apache in foreground
echo "🌐 Starting Apache web server on port ${PORT:-80}..."
echo "📍 Server will be available at: ${DEFAULT_URI:-http://localhost}"

# Update Apache port if PORT is set (Render requirement)
if [ -n "$PORT" ]; then
    echo "Listen $PORT" > /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
    echo "✅ Apache configured to listen on port $PORT"
fi

exec apache2-foreground