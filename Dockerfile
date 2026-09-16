FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev zip unzip dos2unix \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Raise PHP upload limits: the php:8.2-cli defaults (upload_max_filesize=2M,
# post_max_size=8M) reject typical phone photos. When post_max_size overflows,
# PHP drops the whole POST including the CSRF token, which surfaces as a
# "419 Page Expired" error on profile/product picture updates.
RUN echo "upload_max_filesize = 40M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 40M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN dos2unix docker-entrypoint.sh 2>/dev/null || true \
    && chmod +x docker-entrypoint.sh \
    && composer dump-autoload --optimize \
    && mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R 777 storage \
    && chmod -R 777 bootstrap/cache

EXPOSE 8000

CMD ["sh", "-c", "sh docker-entrypoint.sh && exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
