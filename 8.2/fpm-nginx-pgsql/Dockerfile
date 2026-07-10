# syntax=docker/dockerfile:1.7
FROM --platform=$BUILDPLATFORM mlocati/php-extension-installer:latest AS php_ext
FROM --platform=$BUILDPLATFORM composer:latest AS composer_bin
FROM php:8.2-fpm-bookworm

ARG TARGETARCH
ENV ACCEPT_EULA=Y \
    DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1

# Base OS deps + nginx + supervisor
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg2 unzip \
        libpng-dev libzip-dev \
        nginx \
        supervisor \
        ; \
    rm -rf /var/lib/apt/lists/*

# install-php-extensions helper
COPY --from=php_ext /usr/bin/install-php-extensions /usr/bin/install-php-extensions

# PHP extensions
RUN set -eux; \
    chmod +x /usr/bin/install-php-extensions; \
    install-php-extensions \
      bcmath ds exif gd intl opcache pcntl sockets \
      redis zip pgsql pdo_pgsql curl; \
    rm -rf /var/lib/apt/lists/*

# Relax OpenSSL policy
RUN sed -i 's/^\s*MinProtocol.*/MinProtocol = TLSv1.0/' /etc/ssl/openssl.cnf \
 && sed -i 's/^\s*CipherString.*/CipherString = DEFAULT@SECLEVEL=1/' /etc/ssl/openssl.cnf

# Quiet FPM access log
RUN sed -i 's/error_log = /;error_log = /' /usr/local/etc/php-fpm.d/docker.conf || true

# Composer
COPY --from=composer_bin /usr/bin/composer /usr/local/bin/composer

# Nginx + supervisord config
COPY nginx.conf /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

WORKDIR /var/www

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
