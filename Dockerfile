FROM composer:2 AS composer

FROM php:8.1-fpm-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
        libicu-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        pdo \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY entrypoint.sh /usr/local/bin/order-entrypoint

RUN chmod +x /usr/local/bin/order-entrypoint \
    && mkdir -p /workspace/order /tmp/composer-cache

WORKDIR /workspace/order

ENTRYPOINT ["order-entrypoint"]
CMD ["php-fpm"]
