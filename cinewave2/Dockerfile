FROM php:8.2-apache

# Install PostgreSQL libraries and PHP PDO extensions for Supabase
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache URL rewrite module
RUN a2enmod rewrite

# Copy project files into the web root
COPY . /var/www/html/

EXPOSE 80
