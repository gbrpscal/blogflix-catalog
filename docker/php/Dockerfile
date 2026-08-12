FROM composer:2.8.12 AS vendor-production
WORKDIR /app
COPY apps/backend/composer.json apps/backend/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist --optimize-autoloader

FROM composer:2.8.12 AS vendor-testing
WORKDIR /app
COPY apps/backend/composer.json apps/backend/composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --prefer-dist

FROM php:8.4.13-fpm-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfcgi-bin \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring opcache pcntl pdo_pgsql zip \
    && pecl install redis-6.2.0 \
    && docker-php-ext-enable redis \
    && apt-get purge -y libicu-dev libonig-dev libpq-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

WORKDIR /var/www/backend

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-blogflix.ini
COPY docker/php/fpm-health.conf /usr/local/etc/php-fpm.d/zz-health.conf
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/php/init-app.sh /usr/local/bin/init-app

RUN chmod 0755 /usr/local/bin/app-entrypoint /usr/local/bin/init-app

FROM php-base AS runtime
COPY --from=vendor-production /app/vendor ./vendor
COPY apps/backend .
RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && if [ ! -e public/storage ]; then ln -s /var/www/backend/storage/app/public public/storage; fi \
    && chown -R www-data:www-data storage bootstrap/cache public
USER www-data
ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD ["php-fpm", "-F"]

FROM php-base AS testing
COPY --from=vendor-testing /usr/bin/composer /usr/local/bin/composer
COPY --from=vendor-testing /app/vendor ./vendor
COPY apps/backend .
RUN touch .env \
    && rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && if [ ! -e public/storage ]; then ln -s /var/www/backend/storage/app/public public/storage; fi \
    && chown -R www-data:www-data storage bootstrap/cache vendor public
USER www-data
ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD ["php", "artisan", "test"]

FROM runtime AS production
