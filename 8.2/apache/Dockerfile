FROM php:8.2-apache-bullseye


ENV ACCEPT_EULA=Y

# ------------------------------------------------------------
# Core prerequisites
# ------------------------------------------------------------
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apt-transport-https \
        gnupg2 \
        libpng-dev \
        libzip-dev \
        unzip \
    && rm -rf /var/lib/apt/lists/*


# Install prerequisites for the sqlsrv and pdo_sqlsrv PHP extensions.
# Some packages are pinned with lower priority to prevent build issues due to package conflicts.
# Link: https://github.com/microsoft/linux-package-repositories/issues/39
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && echo "Package: unixodbc\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: unixodbc-dev\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: libodbc1:amd64\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: odbcinst\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && echo "Package: odbcinst1debian2:amd64\nPin: origin \"packages.microsoft.com\"\nPin-Priority: 100\n" >> /etc/apt/preferences.d/microsoft \
    && apt-get update \
    && apt-get install -y msodbcsql18 mssql-tools18 unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# PHP extensions
# ------------------------------------------------------------
# Pull the helper script directly from the official installer image
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions

RUN chmod uga+x /usr/bin/install-php-extensions \
    && sync \
    && install-php-extensions bcmath gd opcache pdo_sqlsrv redis sqlsrv zip curl

# ------------------------------------------------------------
# OpenSSL TLS minimums (if you truly need TLSv1.0 compatibility)
# ------------------------------------------------------------
RUN sed -i "s/\(MinProtocol *= *\).*/\1TLSv1.0 /" "/etc/ssl/openssl.cnf" \
 && sed -i "s/\(CipherString *= *\).*/\1DEFAULT@SECLEVEL=1 /" "/etc/ssl/openssl.cnf"

# ------------------------------------------------------------
# Composer
# ------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# ------------------------------------------------------------
# Elastic APM (PHP Agent)
# ------------------------------------------------------------
RUN curl -fsSL https://github.com/elastic/apm-agent-php/releases/download/v1.15.1/apm-agent-php_1.15.1_amd64.deb -o /tmp/apm-agent-php.deb \
    && dpkg -i /tmp/apm-agent-php.deb \
    && rm -f /tmp/apm-agent-php.deb

# ------------------------------------------------------------
# Workdir
# ------------------------------------------------------------
WORKDIR /var/www

# ---- Apache + PHP tuning ----

RUN set -eux; \
    a2enmod rewrite headers expires deflate remoteip; \
    a2dismod mpm_event mpm_worker || true; \
    a2enmod mpm_prefork

# mpm_prefork tuning
RUN set -eux; \
    cat >/etc/apache2/mods-available/mpm_prefork.conf <<'EOF'
<IfModule mpm_prefork_module>
    StartServers             4
    MinSpareServers          4
    MaxSpareServers          8
    MaxRequestWorkers       40
    ServerLimit             40
    MaxConnectionsPerChild 1000
</IfModule>
EOF

# Core perf + logs
RUN set -eux; \
    mkdir -p /etc/apache2/conf-available; \
    cat >/etc/apache2/conf-available/10-perf.conf <<'EOF'
Timeout 30
KeepAlive On
MaxKeepAliveRequests 200
KeepAliveTimeout 2

ServerTokens Prod
ServerSignature Off
TraceEnable Off

LogFormat "%a %l %u %t \"%r\" %>s %b %D" tiny
CustomLog /proc/self/fd/1 tiny
ErrorLog  /proc/self/fd/2
EOF
RUN a2enconf 10-perf

# Compression + caching + headers
RUN set -eux; \
    cat >/etc/apache2/conf-available/20-webperf.conf <<'EOF'
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE \
      text/html text/plain text/xml text/css text/javascript \
      application/javascript application/json application/xml \
      image/svg+xml
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 7 days"
    ExpiresByType application/javascript "access plus 7 days"
    ExpiresByType image/svg+xml "access plus 30 days"
    ExpiresByType image/* "access plus 30 days"
    ExpiresDefault "access plus 1 hour"
</IfModule>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>
EOF
RUN a2enconf 20-webperf

# RemoteIP
RUN set -eux; \
    cat >/etc/apache2/conf-available/30-remoteip.conf <<'EOF'
<IfModule mod_remoteip.c>
    RemoteIPHeader X-Forwarded-For
    RemoteIPTrustedProxy 10.0.0.0/8
    RemoteIPTrustedProxy 172.16.0.0/12
    RemoteIPTrustedProxy 192.168.0.0/16
    LogFormat "%a %l %u %t \"%r\" %>s %b %D" tiny
</IfModule>
EOF
RUN a2enconf 30-remoteip

# VHost tweaks
RUN set -eux; \
    awk '1; END { print "\n<Directory /var/www/html>\n  AllowOverride All\n  Options -Indexes +FollowSymLinks\n  Require all granted\n</Directory>\n" }' \
        /etc/apache2/sites-available/000-default.conf > /tmp/vh && mv /tmp/vh /etc/apache2/sites-available/000-default.conf

# OPcache/JIT
RUN set -eux; \
    mkdir -p /usr/local/etc/php/conf.d; \
    cat >/usr/local/etc/php/conf.d/zz-opcache.ini <<'EOF'
opcache.enable=1
opcache.enable_cli=0
opcache.validate_timestamps=0
opcache.max_accelerated_files=20000
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
opcache.jit=1255
opcache.jit_buffer_size=64M
realpath_cache_size=4096k
realpath_cache_ttl=600
expose_php=0
EOF
# Optional: disable access log entirely (uncomment if you really want no access logs)
RUN sed -ri 's|^CustomLog .*|CustomLog /dev/null combined|' /etc/apache2/sites-available/000-default.conf

# Optional: raise FD limit before Apache starts
# RUN printf '%s\n' '#!/bin/sh' 'ulimit -n 65536 || true' 'exec apache2-foreground' > /usr/local/bin/start.sh \
#     && chmod +x /usr/local/bin/start.sh
# CMD ["start.sh"]

# ------------------------------------------------------------
# End of Dockerfile
# ------------------------------------------------------------