FROM composer:2 AS deps

WORKDIR /app

COPY composer.json composer.lock symfony.lock* ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --ignore-platform-reqs

FROM php:8.1-fpm-alpine AS runtime

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV PORT=8080

RUN apk add --no-cache \
    bash \
    curl \
    gettext \
    icu-dev \
    libzip-dev \
    mysql-client \
    nginx \
    oniguruma-dev \
    supervisor \
    unzip \
    zip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        pdo \
        pdo_mysql \
        zip \
    && rm -rf /var/cache/apk/*

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

RUN { \
    echo '[global]'; \
    echo 'daemonize = no'; \
    echo 'error_log = /proc/self/fd/2'; \
    echo '[www]'; \
    echo 'listen = /var/run/php-fpm.sock'; \
    echo 'listen.owner = nginx'; \
    echo 'listen.group = nginx'; \
    echo 'listen.mode = 0660'; \
    echo 'user = www-data'; \
    echo 'group = www-data'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 10'; \
    echo 'pm.start_servers = 2'; \
    echo 'pm.min_spare_servers = 1'; \
    echo 'pm.max_spare_servers = 5'; \
    echo 'pm.max_requests = 500'; \
    echo 'clear_env = no'; \
} > /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY --from=deps /app/vendor ./vendor
COPY . .

RUN mkdir -p config/ssl var/cache var/log var/sessions /var/run \
    && chown -R www-data:www-data var \
    && chown -R www-data:nginx public /var/run \
    && chmod -R 775 var

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
