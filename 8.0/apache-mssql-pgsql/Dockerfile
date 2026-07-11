# PHP 8.0 + Apache + MSSQL + PostgreSQL base image (Debian 11 Bullseye)
# Intended as a pre-built base so downstream projects (e.g. sync-backend)
# skip the slow dependency/extension build step.
FROM php:8.0.30-apache-bullseye

ARG TARGETARCH

ENV ACCEPT_EULA=Y

# ------------------------------------------------------------
# System packages
# ------------------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        gnupg \
        unzip; \
    rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# Microsoft ODBC 18 (for sqlsrv / pdo_sqlsrv)
# ------------------------------------------------------------
RUN set -eux; \
    curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft.gpg; \
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] \
https://packages.microsoft.com/debian/11/prod bullseye main" \
        > /etc/apt/sources.list.d/microsoft-prod.list; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 \
        unixodbc-dev; \
    rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# PHP extensions via install-php-extensions
# Handles all required -dev headers automatically.
# Extensions: bcmath exif gd(freetype+jpeg) intl mbstring opcache
#             pdo pdo_pgsql pgsql zip redis sqlsrv pdo_sqlsrv
# ------------------------------------------------------------
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions

RUN chmod uga+x /usr/bin/install-php-extensions && sync \
    && install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        redis \
        sqlsrv \
        pdo_sqlsrv \
    && rm -rf /tmp/* /var/tmp/*

# ------------------------------------------------------------
# Apache modules
# ------------------------------------------------------------
RUN a2enmod rewrite headers

# ------------------------------------------------------------
# Composer
# ------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
