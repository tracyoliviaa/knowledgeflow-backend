#!/usr/bin/env bash
set -euo pipefail

# If APP_SECRET not set, generate temporary one (not for production!)
: "${APP_SECRET:=}"
if [ -z "$APP_SECRET" ]; then
  export APP_SECRET="$(php -r "echo bin2hex(random_bytes(16));")"
  echo "No APP_SECRET provided — using ephemeral secret (override in Render envs)."
fi

# Ensure config/jwt exists and keys exist; generate only at runtime if missing.
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
  echo "JWT keys missing — generating RSA 4096 keys..."
  mkdir -p config/jwt
  openssl genpkey -out config/jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096
  openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
  chown -R www-data:www-data config/jwt
fi

# Wait for database to be reachable (optional, simple loop)
if [ -n "${DATABASE_URL:-}" ]; then
  echo "Waiting for database..."
  # try to use php to open PDO connection (simple check) — retry loop
  tries=0
  until php -r "try { new PDO(getenv('DATABASE_URL')); exit(0);} catch (Throwable \$e) { exit(1); }"; do
    tries=$((tries+1))
    echo "DB not ready (attempt $tries). Sleeping 2s..."
    sleep 2
    if [ "$tries" -ge 60 ]; then
      echo "Database not ready after multiple attempts. Continuing startup (you may handle migrations externally)."
      break
    fi
  done
fi

# Run migrations safely: attempt, but DO NOT block startup on failure.
if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
  echo "Running migrations (if any)..."
  set +e
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
  rc=$?
  set -e
  if [ $rc -ne 0 ]; then
    echo "Migrations returned code $rc — continuing startup. Consider running migrations in a controlled deploy hook."
  fi
fi

# Clear & warm cache in prod if needed (non-blocking)
if [ "${APP_ENV:-prod}" = "prod" ]; then
  php bin/console cache:clear --no-warmup --env=prod --no-debug || true
  php bin/console cache:warmup --env=prod --no-debug || true
  chown -R www-data:www-data var
fi

# Finally exec the container command (Apache)
exec "$@"
