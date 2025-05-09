# BUILDER TO BACK-END
FROM composer:latest AS back-builder

WORKDIR /back

COPY composer.* ./

RUN composer install -q --no-ansi --no-scripts --no-interaction --no-progress --prefer-dist

# BUILDER TO FRONT-END
FROM node:22 AS front-builder

WORKDIR /front

COPY package*.json webpack.config.ts tailwind.config.js postcss.config.js tsconfig.json ./

COPY assets/ ./assets/

COPY public/ ./public/

RUN npm i

COPY --from=back-builder /back/vendor ./vendor

RUN npm run dev

# APP
FROM php:8.4.4-cli AS app

WORKDIR /app

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && apt-get install symfony-cli -y

RUN apt-get update && apt-get install -y libpq-dev dos2unix nmap neovim \
    && docker-php-ext-install pdo pdo_pgsql

COPY . .

COPY --from=back-builder /back .

COPY --from=front-builder /front .

RUN chmod +x ./docker/scripts/*

RUN dos2unix ./docker/scripts/entrypoint.sh && echo "secrets executed!!" || echo "nao execultou as secrets"

ENTRYPOINT ["./docker/scripts/entrypoint.sh"]

CMD ["symfony", "serve:start", "--allow-all-ip"]

