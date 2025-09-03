# PHP 8.4 + FrankenPHP (Debian 12)
FROM dunglas/frankenphp:php8.4

# # Microsoft ODBC 18 + runtime ODBC deps
RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends gnupg2 curl ca-certificates apt-transport-https; \
  curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/msprod.gpg; \
  echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/msprod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
    > /etc/apt/sources.list.d/msprod.list; \
  apt-get update; \
  ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
    msodbcsql18 mssql-tools18 \
    unixodbc unixodbc-dev odbcinst1debian2 libodbc1 \
    libicu70 libpng16-16 libzip5 unzip; \
  rm -rf /var/lib/apt/lists/*

# Ekstensi PHP
RUN install-php-extensions \
    bcmath \
    zip \
    pgsql \
    pdo_pgsql \
    curl

# PECL
RUN pecl install redis sqlsrv pdo_sqlsrv \
 && printf "extension=redis\n"        > /usr/local/etc/php/conf.d/30-redis.ini \
 && printf "extension=sqlsrv\n"       > /usr/local/etc/php/conf.d/30-sqlsrv.ini \
 && printf "extension=pdo_sqlsrv\n"   > /usr/local/etc/php/conf.d/31-pdo_sqlsrv.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# App
WORKDIR /app
