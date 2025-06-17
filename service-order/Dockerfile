FROM php:8.2-apache

# Install ekstensi PHP yang dibutuhkan Laravel & GraphQL
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    libpq-dev postgresql-server-dev-all \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install sockets pdo pdo_pgsql

# Aktifkan modul Apache
RUN a2enmod rewrite

# Set document root ke folder 'public' di dalam /var/www
ENV APACHE_DOCUMENT_ROOT=/var/www/public

# Ubah konfigurasi virtual host agar sesuai dengan document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Copy source code Laravel ke dalam container
COPY . /var/www

# Ubah permission untuk Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install dependency Laravel
WORKDIR /var/www
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Expose Apache port
EXPOSE 80