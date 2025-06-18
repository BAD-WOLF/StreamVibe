# APP
FROM php:8.4.4-cli AS app

WORKDIR /app

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && apt-get install symfony-cli -y

RUN apt-get update && apt-get install -y libpq-dev dos2unix nmap neovim unzip
RUN docker-php-ext-install pdo pdo_pgsql

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install

RUN chmod +x ./docker/scripts/*

RUN dos2unix ./docker/scripts/entrypoint.sh && echo "secrets executed!!" || echo "nao execultou as secrets"

ENTRYPOINT ["./docker/scripts/entrypoint.sh"]

CMD ["symfony", "serve:start", "--allow-all-ip"]

