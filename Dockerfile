# Start with a base PHP image
FROM php:8.1-apache

# Install PostgreSQL client libraries
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions, including PostgreSQL.
# The `docker-php-ext-install` command should be used correctly.
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Copy your application files into the container
COPY . /var/www/html/

# Expose port 80 to the outside world
EXPOSE 80

# The command to run the application
CMD ["apache2-foreground"]