# Start with a base PHP image
FROM php:8.1-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your application files into the container
COPY . /var/www/html/

# Expose port 80 to the outside world
EXPOSE 80