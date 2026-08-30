FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application
COPY . .

# Setup
RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# Safe production defaults
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    SESSION_DRIVER=file

EXPOSE 8000

# Fresh DB, migrate, clear caches, serve
CMD ["sh", "-c", "\
    rm -f database/database.sqlite; \
    touch database/database.sqlite; \
    php artisan migrate --force; \
    php artisan config:clear 2>/dev/null; \
    php artisan route:clear 2>/dev/null; \
    php artisan view:clear 2>/dev/null; \
    php -S 0.0.0.0:${PORT:-8000} -t public"]
