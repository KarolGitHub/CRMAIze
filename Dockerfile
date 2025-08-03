# Use official PHP image with Apache
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
  git \
  curl \
  libpng-dev \
  libonig-dev \
  libxml2-dev \
  zip \
  unzip \
  libpq-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create necessary directories and set permissions
RUN mkdir -p data cache cache/twig public/assets/icons templates/emails \
  && chmod 755 data cache \
  && chmod +x build.sh

# Run installation script
RUN php scripts/install.php

# Expose port
EXPOSE $PORT

# Start PHP built-in server
CMD php -S 0.0.0.0:$PORT -t public