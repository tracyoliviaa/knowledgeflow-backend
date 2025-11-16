# -------------------------------
# Stage 1: build (composer + assets if any)
# -------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --optimize-autoloader

# -------------------------------
# Stage 2: PHP runtime
# -------------------------------
FROM php:8.4-apache

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Copy composer from vendor stage
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=vendor /app/composer.lock /var/www/html/composer.lock
COPY --from=vendor /app/composer.json /var/www/html/composer.json

WORKDIR /var/www/html

# Copy app sources (after vendor to leverage docker cache)
COPY . .

# Ensure proper user/group for runtime files
RUN mkdir -p var/cache var/log config/jwt public && \
    chown -R www-data:www-data var config/jwt public

# Set production env defaults (can be overridden at runtime)
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV SYMFONY_PHPUNIT_REMOVE=1

# Create a minimal .env so composer/symfony scripts run during build.
# **Do not** store real secrets here — override at runtime (Render envs).
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_SECRET=temporary_build_secret_change_at_runtime" >> .env

# Run composer auto-scripts now that .env exists (cache clear/warmup may run later)
RUN composer run-script auto-scripts || true

# Optimize autoloader (ensure vendors already copied)
RUN composer dump-autoload --optimize --classmap-authoritative || true

# Generate ephemeral jwt keys if none exist at build time (will be regenerated at runtime if missing)
RUN if [ ! -f config/jwt/private.pem ]; then \
      mkdir -p config/jwt && \
      openssl genpkey -out config/jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096 && \
      openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout; \
    fi && chown -R www-data:www-data config/jwt

# Apache config: use public as document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Add symfony apache conf
RUN printf '%s\n' '<Directory /var/www/html/public>' \
  '    Options -Indexes +FollowSymLinks' \
  '    AllowOverride All' \
  '    Require all granted' \
  '</Directory>' > /etc/apache2/conf-available/symfony.conf \
  && a2enconf symfony

# Copy entrypoint (will handle runtime tasks)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
