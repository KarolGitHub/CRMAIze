# Use official PHP image
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
  libpq-dev \
  && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first (for better caching)
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application files
COPY . .

# Run composer scripts now that all files are present
RUN composer run-script post-install-cmd

# Create necessary directories and set permissions
RUN mkdir -p data cache cache/twig public/assets/icons templates/emails \
  && chmod 755 data cache \
  && chmod +x build.sh || true

# Run build script
RUN ./build.sh || true

# Run installation script
RUN php scripts/install.php || echo "Install script completed with warnings"

# Expose port (Render provides this via $PORT)
EXPOSE 10000

# Start PHP built-in server
CMD php -S 0.0.0.0:${PORT:-10000} -t public