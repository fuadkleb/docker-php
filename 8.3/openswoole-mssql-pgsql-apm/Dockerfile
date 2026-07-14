# PHP 8.3 + OpenSwoole + MSSQL + PostgreSQL + Infisical + Elastic APM
# Extends fuadkleb/php-mssql:8.3-openswoole-mssql-pgsql with the Elastic APM PHP agent.
FROM fuadkleb/php-mssql:8.3-openswoole-mssql-pgsql

ARG TARGETARCH
ARG ELASTIC_APM_VERSION=1.17.0

# ------------------------------------------------------------
# Elastic APM PHP Agent
# ------------------------------------------------------------
RUN set -eux; \
    curl -fsSL -o /tmp/apm-agent-php.deb \
      "https://github.com/elastic/apm-agent-php/releases/download/v${ELASTIC_APM_VERSION}/apm-agent-php_${ELASTIC_APM_VERSION}_${TARGETARCH}.deb"; \
    apt-get update; \
    apt-get install -y --no-install-recommends /tmp/apm-agent-php.deb; \
    rm -f /tmp/apm-agent-php.deb; \
    rm -rf /var/lib/apt/lists/*
