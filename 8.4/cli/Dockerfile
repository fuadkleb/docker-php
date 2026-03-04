# syntax=docker/dockerfile:1.7
FROM php:8.4-cli-bookworm

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

# -- Add Microsoft repo for Debian 12 (Bookworm) & install ODBC + tools ----------
# This replaces apt-key and pins safely for Debian 12.
RUN set -eux; \
    curl -fsSL -o /tmp/packages-microsoft-prod.deb \
      "https://packages.microsoft.com/config/debian/12/packages-microsoft-prod.deb"; \
    dpkg -i /tmp/packages-microsoft-prod.deb; \
    rm -f /tmp/packages-microsoft-prod.deb; \
    apt-get update; \
    # sqlcmd/bcp tools + runtime and headers
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
      msodbcsql18 mssql-tools18 unixodbc-dev; \
    # make path persistent for all shells
    printf 'export PATH="$PATH:/opt/mssql-tools18/bin"\n' >/etc/profile.d/mssql-tools.sh; \
    rm -rf /var/lib/apt/lists/*

# -- Bring in install-php-extensions ---------------------------------------------
FROM --platform=$BUILDPLATFORM mlocati/php-extension-installer:latest AS php_ext
FROM --platform=$BUILDPLATFORM composer:latest AS composer_bin
FROM php:8.4-cli-bookworm

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
        git \
        libpng-dev libzip-dev \
        wkhtmltopdf \
        ; \
    rm -rf /var/lib/apt/lists/*

# Microsoft repo + ODBC (same as above, in the final stage)
RUN set -eux; \
    curl -fsSL -o /tmp/packages-microsoft-prod.deb \
      "https://packages.microsoft.com/config/debian/12/packages-microsoft-prod.deb"; \
    dpkg -i /tmp/packages-microsoft-prod.deb; \
    rm -f /tmp/packages-microsoft-prod.deb; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
      msodbcsql18 mssql-tools18 unixodbc-dev; \
    printf 'export PATH="$PATH:/opt/mssql-tools18/bin"\n' >/etc/profile.d/mssql-tools.sh; \
    rm -rf /var/lib/apt/lists/*

# install-php-extensions helper
COPY --from=php_ext /usr/bin/install-php-extensions /usr/bin/install-php-extensions

# PHP extensions (installer auto-installs & removes build deps)
RUN set -eux; \
    chmod +x /usr/bin/install-php-extensions; \
    install-php-extensions \
      bcmath exif gd intl \
      pcntl sockets opcache \
      pdo_sqlsrv sqlsrv redis zip pgsql pdo_pgsql curl pdo_mysql; \
    rm -rf /var/lib/apt/lists/*

# Optional: relax OpenSSL policy (only if you really need it)
RUN sed -i 's/^\s*MinProtocol.*/MinProtocol = TLSv1.0/' /etc/ssl/openssl.cnf \
 && sed -i 's/^\s*CipherString.*/CipherString = DEFAULT@SECLEVEL=1/' /etc/ssl/openssl.cnf

# Composer (tiny)
COPY --from=composer_bin /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www