FROM php:8.3.36-fpm-bookworm

ARG TARGETARCH

ENV ACCEPT_EULA=Y

# Install prerequisites required for tools and extensions installed later on.
RUN apt-get update \
    && apt-get install -y apt-transport-https gnupg2 libpng-dev libzip-dev unzip \
    && rm -rf /var/lib/apt/lists/*

# Install prerequisites for the sqlsrv and pdo_sqlsrv PHP extensions.
RUN set -eux; \
    curl -fsSL -o /tmp/packages-microsoft-prod.deb \
      "https://packages.microsoft.com/config/debian/12/packages-microsoft-prod.deb"; \
    dpkg -i /tmp/packages-microsoft-prod.deb; \
    rm -f /tmp/packages-microsoft-prod.deb; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
      msodbcsql18 mssql-tools18 unixodbc-dev; \
    rm -rf /var/lib/apt/lists/*

# Retrieve the script used to install PHP extensions from the source container.
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions

# Install required PHP extensions and all their prerequisites available via apt.
RUN chmod uga+x /usr/bin/install-php-extensions \
    && sync \
    && install-php-extensions bcmath ds exif gd intl opcache pcntl pdo_sqlsrv redis sqlsrv zip pgsql pdo_pgsql curl

# Allow TLS 1.0 (legacy SQL Server).
# Debian 12 ships OpenSSL 3 with no MinProtocol/CipherString lines to edit, so add them.
# OpenSSL 3 only allows TLS 1.0/1.1 at SECLEVEL=0 (bullseye's OpenSSL 1.1 allowed it at SECLEVEL=1).
RUN set -eux; \
    sed -i '/^\[openssl_init\]/a ssl_conf = ssl_sect' /etc/ssl/openssl.cnf; \
    printf '\n[ssl_sect]\nsystem_default = system_default_sect\n\n[system_default_sect]\nMinProtocol = TLSv1\nCipherString = DEFAULT@SECLEVEL=0\n' >> /etc/ssl/openssl.cnf

# Set AccessLog to off
RUN sed -i "s/error_log = /;error_log = /" /usr/local/etc/php-fpm.d/docker.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install Elastic APM 
RUN curl -fsSL https://github.com/elastic/apm-agent-php/releases/download/v1.17.0/apm-agent-php_1.17.0_${TARGETARCH}.deb > /tmp/apm-agent-php.deb \
    && dpkg -i /tmp/apm-agent-php.deb

# Setting the work directory.
WORKDIR /var/www
