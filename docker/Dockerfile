FROM php:8.4-cli AS app

WORKDIR /app

# Instala dependências de SO básicas
RUN apt-get update && apt-get install -y \
      libpq-dev dos2unix nmap neovim unzip fish \
  && docker-php-ext-install pdo pdo_pgsql \
  && rm -rf /var/lib/apt/lists/*

# Instala symfony-cli (pode invalidar cache se confiar pouco na URL; mas normalmente OK)
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
  && apt-get update && apt-get install -y symfony-cli \
  && rm -rf /var/lib/apt/lists/*

# Trazer o Composer para dentro da imagem
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia all código
COPY . .

RUN composer install

# Ajustes de scripts
RUN dos2unix /app/docker/scripts/entrypoint.sh
RUN chmod 751 /app/docker/scripts/entrypoint.sh

ENTRYPOINT ["/app/docker/scripts/entrypoint.sh"]
CMD ["symfony", "serve:start", "--allow-all-ip"]
