# Use the official PHP image with Apache
FROM php:8.2-apache

# Install required PHP extensions for MariaDB/MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (useful if you use URL routing)
RUN a2enmod rewrite

# Copy your local project files into the container's web directory
COPY . /var/www/html/

# CRITICAL FOR RAILWAY: Update Apache to use the dynamic $PORT environment variable
# Railway will fail the deployment if the app forces port 80.
RUN sed -i "s/Listen 80/Listen \${PORT}/g" /etc/apache2/ports.conf
RUN sed -i "s/:80/:\${PORT}/g" /etc/apache2/sites-available/000-default.conf
