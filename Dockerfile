# -------------------------------
# 1️⃣  Base Image – PHP 8.4 Apache
# -------------------------------
FROM php:8.4-apache

# -------------------------------
# 2️⃣  Systemabhängigkeiten installieren
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
# 7️⃣  Produktionsumgebung konfigurieren
# -------------------------------
ENV APP_ENV=prod
ENV APP_DEBUG=0

# -------------------------------
# 8️⃣  Abhängigkeiten installieren (ohne dev)
# -------------------------------
RUN composer install --no-interaction --prefer-dist --no-scripts --no-dev

# -------------------------------
# 9️⃣  Symfony-Autoskripte ausführen (cache:clear etc.)
# -------------------------------
RUN composer run-script auto-scripts

# -------------------------------
# 🔄  Clear and warm cache for production
# -------------------------------
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup
RUN php bin/console cache:warmup --env=prod --no-debug

# -------------------------------
# 🔟  Autoloader optimieren
# -------------------------------
RUN composer dump-autoload --optimize --classmap-authoritative
# -------------------------------
# 1️⃣1️⃣  Apache-Konfiguration
# -------------------------------
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Symfony: .htaccess aktivieren
RUN echo '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/symfony.conf \
 && a2enconf symfony

# -------------------------------
# 1️⃣2️⃣  Port öffnen & Server starten
# -------------------------------
EXPOSE 80
CMD ["apache2-foreground"]
