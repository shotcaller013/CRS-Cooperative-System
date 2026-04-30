FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libzip-dev unzip git zlib1g-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN printf '<VirtualHost *:80>\n    DocumentRoot /var/www/html/public\n    <Directory /var/www/html/public>\n        AllowOverride All\n        Require all granted\n    </Directory>\n</VirtualHost>\n' \
    > /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

WORKDIR /var/www/html

RUN composer create-project --prefer-dist laravel/laravel . --no-interaction

COPY app/ app/
COPY database/ database/
COPY 2026_*.php database/migrations/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
