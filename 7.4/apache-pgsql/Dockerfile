# PHP 7.3 + Apache on Debian 11 (Bullseye)
FROM php:7.4-apache-bullseye

# Non-interactive EULA for Microsoft packages and make sqlcmd/bcp easy to call
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="/opt/mssql-tools18/bin:${PATH}"

# Base tools and headers used by PHP extensions
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg2 \
        unzip; \
        rm -rf /var/lib/apt/lists/*

# PHP extension installer (v2 supports PHP 7.x nicely)
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

# Build & enable PHP extensions
# install-php-extensions will apt-get any needed lib*-dev automatically
RUN set -eux; \
    chmod +x /usr/local/bin/install-php-extensions; \
    install-php-extensions \
        bcmath \
        gd \
        pgsql \
        pdo_pgsql \
        zip \
        curl 

# Apache: enable useful modules
RUN a2enmod rewrite headers expires

# Adjust Apache DocumentRoot to /var/www/web
# Create a vhost that points to /var/www/web and allows .htaccess overrides
RUN set -eux; \
    mkdir -p /var/www/web; \
    sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/web#' /etc/apache2/sites-available/000-default.conf; \
    awk '1; /DocumentRoot/ {print "    <Directory /var/www/web>\n        AllowOverride All\n        Require all granted\n    </Directory>"}' \
        /etc/apache2/sites-available/000-default.conf > /tmp/000-default.conf && mv /tmp/000-default.conf /etc/apache2/sites-available/000-default.conf

