# Agriculture Information System: container for a hosting service such as Render.
FROM php:8.3-apache

# PostgreSQL driver for PDO (the app talks to Supabase through it)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# PHP's production settings: errors are never shown to visitors
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Listen on the port the host assigns (Render sets PORT; 10000 is the fallback)
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Do not reveal the server version, and refuse web requests to the internal folders
RUN printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/hardening.conf \
    && printf '<DirectoryMatch "^/var/www/html/(includes|config)(/|$)">\n    Require all denied\n</DirectoryMatch>\n' > /etc/apache2/conf-available/block-internal.conf \
    && a2enconf hardening block-internal

COPY . /var/www/html/