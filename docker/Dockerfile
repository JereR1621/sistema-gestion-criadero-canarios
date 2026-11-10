# Imagen del entorno local de TuCriadero: PHP 8.3 con Apache (guía en docs/base-de-datos.md §3).
FROM php:8.3-apache

# Extensiones: pdo_mysql (MySQL con PDO), gd y mbstring (Dompdf, ajuste del cliente AC-04) y zip (Composer).
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql gd zip \
 && rm -rf /var/lib/apt/lists/*

# Composer, para instalar PHPUnit y Dompdf (composer.json lo crea la tarea de estructura base).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: el sitio se sirve desde public/, toda ruta pasa por public/index.php y frontend/css/ se publica en /css/.
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
