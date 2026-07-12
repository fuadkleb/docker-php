# PHP 8.5 + OpenSwoole + MSSQL + PostgreSQL + Infisical CLI (Debian 12 Bookworm)
FROM php:8.5-cli-bookworm

ARG TARGETARCH

ENV ACCEPT_EULA=Y \
    DEBIAN_FRONTEND=noninteractive

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
# Infisical CLI (official apt repository via artifacts-cli)
# ------------------------------------------------------------
RUN set -eux; \
    curl -1sLf 'https://artifacts-cli.infisical.com/setup.deb.sh' | bash; \
    apt-get update; \
    apt-get install -y --no-install-recommends infisical; \
    rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# Microsoft ODBC 18 (for sqlsrv / pdo_sqlsrv)
# ------------------------------------------------------------
RUN set -eux; \
    curl -fsSL -o /tmp/packages-microsoft-prod.deb \
      "https://packages.microsoft.com/config/debian/12/packages-microsoft-prod.deb"; \
    dpkg -i /tmp/packages-microsoft-prod.deb; \
    rm -f /tmp/packages-microsoft-prod.deb; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 \
        unixodbc-dev; \
    rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# PHP extensions via install-php-extensions
# Extensions: bcmath intl mbstring opcache openswoole pdo
#             pdo_pgsql pgsql redis sqlsrv pdo_sqlsrv zip
# ------------------------------------------------------------
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions

RUN chmod uga+x /usr/bin/install-php-extensions && sync \
    && install-php-extensions \
        bcmath \
        intl \
        mbstring \
        opcache \
        openswoole \
        pdo \
        pdo_pgsql \
        pgsql \
        redis \
        sqlsrv \
        pdo_sqlsrv \
        zip \
    && rm -rf /tmp/* /var/tmp/*

# ------------------------------------------------------------
# Composer
# ------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
