FROM php:8.2-apache

# Install database extensions for PHP
# Use pdo_mysql mysqli if keeping MySQL, or pdo_pgsql pgsql if using Supabase (PostgreSQL)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache URL rewrite module
RUN a2enmod rewrite

# Copy project files into the web root
COPY . /var/www/html/

EXPOSE 80