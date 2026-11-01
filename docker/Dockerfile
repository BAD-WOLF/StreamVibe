# NOTA (out/2026): imagem de produção (compose.prod.yaml). Antes ela:
# - não buildava: "docker-php-ext-install intl" falha sem libicu-dev, e o
#   composer.json exige ext-gd, que não era instalada;
# - instalava as dependências de dev: o build-arg COMPOSER_NO_DEV do
#   compose.prod.yaml não tinha ARG aqui e era ignorado;
# - rodava os scripts do Composer antes de copiar o código (bin/console
#   ainda não existia) e o cghooks, que é dependência de dev;
# - subia o servidor de desenvolvimento do Symfony CLI na porta 8000, mas o
#   nginx (docker/nginx/default.conf) manda o PHP por FastCGI para
#   app:9000 — nenhuma requisição chegava ao Symfony.
# Agora é PHP-FPM na 9000, como o nginx espera, sem dependências de dev.
FROM php:8.4-fpm AS app

WORKDIR /app

# Install system dependencies and PHP extensions (intl and gd are required
# by composer.json; libfcgi-bin brings cgi-fcgi, used by the healthcheck)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfcgi-bin \
    dos2unix \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql intl gd opcache \
    && rm -rf /var/lib/apt/lists/*

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Production image: no dev dependencies (Composer reads COMPOSER_NO_DEV),
# and console commands run in the prod environment even without compose
ARG COMPOSER_NO_DEV=1
ENV APP_ENV=prod \
    APP_DEBUG=0

# Copy patches to be applied by cweagans/composer-patches
COPY patches/ patches/

# Copy composer files first for better caching
COPY composer.json composer.lock symfony.lock ./

# Install dependencies (scripts need bin/console and dev tools, which are
# not here yet; the autoloader is generated after the code is copied)
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Optimized autoloader with the application classes + script permissions
RUN composer dump-autoload --optimize \
    && chmod +x /app/docker/scripts/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/app/docker/scripts/entrypoint.sh"]
CMD ["php-fpm"]
