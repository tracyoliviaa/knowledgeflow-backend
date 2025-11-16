# -------------------------------
# 1️⃣  Base Image
# -------------------------------
FROM php:8.4-apache

# -------------------------------
# 2️⃣  Systemabhängigkeiten
# -------------------------------
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# -------------------------------
# 3️⃣  PHP-Erweiterungen installieren
# -------------------------------
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# -------------------------------
# 4️⃣  Composer aus offiziellem Container kopieren
# -------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -------------------------------
# 5️⃣  Arbeitsverzeichnis setzen
# -------------------------------
WORKDIR /var/www/html

# -------------------------------
# 6️⃣  Anwendung kopieren
# -------------------------------
COPY . .

# -------------------------------
# 🔐  Generate JWT Keys
# -------------------------------
RUN mkdir -p config/jwt && \
    openssl genpkey -out config/jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096 && \
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout && \
    chown -R www-data:www-data config/jwt

# -------------------------------
# 7️⃣  PHP-Konfiguration: Produktionsumgebung festlegen
# -------------------------------
ENV APP_ENV=prod
ENV APP_DEBUG=0

# -------------------------------
# 8️⃣  Minimal .env erstellen (WICHTIG!)
# -------------------------------
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_SECRET=$(openssl rand -hex 32)" >> .env

# -------------------------------
# 9️⃣  Composer Installation
# -------------------------------
RUN composer install --no-interaction --prefer-dist --no-dev

# -------------------------------
# 🔟  Symfony auto-scripts (cache:clear etc.)
# -------------------------------
RUN composer run-script auto-scripts

# -------------------------------
# 1️⃣1️⃣  Cache vorbereiten
# -------------------------------
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup
RUN php bin/console cache:warmup --env=prod --no-debug

# -------------------------------
# 1️⃣2️⃣  Autoloader optimieren
# -------------------------------
RUN composer dump-autoload --optimize --classmap-authoritative

# -------------------------------
# 1️⃣3️⃣  Dateiberechtigungen setzen
# -------------------------------
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/ public/

# -------------------------------
# 1️⃣4️⃣  Apache-Konfiguration aktivieren
# -------------------------------
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN echo '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# -------------------------------
# 📜 Copy entrypoint script
# -------------------------------
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# -------------------------------
# 1️⃣5️⃣  Port öffnen & Server starten
# -------------------------------
EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
