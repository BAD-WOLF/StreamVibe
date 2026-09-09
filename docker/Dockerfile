FROM php:8.4-cli AS app

WORKDIR /app

# Install system dependencies in separate steps to avoid timeouts
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    dos2unix \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql intl

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get update && apt-get install -y --no-install-recommends symfony-cli \
    && rm -rf /var/lib/apt/lists/*

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy patches to be aplay
COPY patches/ patches/

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --optimize-autoloader --no-interaction

# Copy application code
COPY . .

# Fix script permissions
RUN chmod +x /app/docker/scripts/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/app/docker/scripts/entrypoint.sh"]
CMD ["symfony", "serve:start", "--allow-all-ip", "--port=8000"]
