# syntax=docker/dockerfile:1.7
FROM php:8.4-fpm-bookworm

ARG TARGETPLATFORM
ARG TARGETARCH
ARG BUILDPLATFORM
ENV ACCEPT_EULA=Y \
    DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="$PATH:/opt/mssql-tools18/bin"

# -- Base OS deps (no recommends) -------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg2 unzip \
        libpng-dev libzip-dev \
        ; \
    rm -rf /var/lib/apt/lists/*

# -- Bring in install-php-extensions ---------------------------------------------
FROM --platform=$BUILDPLATFORM mlocati/php-extension-installer:latest AS php_ext
FROM --platform=$BUILDPLATFORM composer:latest AS composer_bin
FROM php:8.4-fpm-bookworm

ARG TARGETARCH
ENV ACCEPT_EULA=Y \
    DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="$PATH:/opt/mssql-tools18/bin"

# Re-install minimal base deps (final stage)
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg2 unzip \
        libpng-dev libzip-dev; \
    rm -rf /var/lib/apt/lists/*

# install-php-extensions helper
COPY --from=php_ext /usr/bin/install-php-extensions /usr/bin/install-php-extensions

# PHP extensions (installer auto-installs & removes build deps)
RUN set -eux; \
    chmod +x /usr/bin/install-php-extensions; \
    install-php-extensions \
      bcmath ds exif gd intl opcache pcntl sockets \
      redis zip pgsql pdo_pgsql curl; \
    rm -rf /var/lib/apt/lists/*

# Optional: relax OpenSSL policy (only if you really need it)
RUN sed -i 's/^\s*MinProtocol.*/MinProtocol = TLSv1.0/' /etc/ssl/openssl.cnf \
 && sed -i 's/^\s*CipherString.*/CipherString = DEFAULT@SECLEVEL=1/' /etc/ssl/openssl.cnf

# Quiet FPM access log as in your original
RUN set -eux; \
    sed -i 's/error_log = /;error_log = /' /usr/local/etc/php-fpm.d/docker.conf || true

# Composer (tiny)
COPY --from=composer_bin /usr/bin/composer /usr/local/bin/composer

# --- Elastic APM agent (optional, arch-aware) -----------------------------------
# If Elastic publishes arm64 .deb, set the file name accordingly. Otherwise skip on arm64.
# Example keeps amd64 only to avoid failing on arm64.
RUN set -eux; \
    curl -fsSL -o /tmp/apm-agent-php.deb \
    "https://github.com/elastic/apm-agent-php/releases/download/v1.15.1/apm-agent-php_1.15.1_${TARGETARCH}.deb"; \
    apt-get update; \
    apt-get install -y --no-install-recommends /tmp/apm-agent-php.deb; \
    rm -f /tmp/apm-agent-php.deb; \
    rm -rf /var/lib/apt/lists/*; 

WORKDIR /var/www