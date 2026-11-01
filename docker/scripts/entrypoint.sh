#!/bin/bash
set -e

echo "🚀 Starting StreamVibe..."

# NOTA (out/2026): as chaves JWT não vêm mais dentro da imagem
# (.dockerignore). Em dev e teste elas chegam pela montagem de ./config; em
# produção ficam no volume jwt_keys (compose.prod.yaml). Com
# --skip-if-exists o comando não mexe num par que já existe: só gera chaves
# na primeira subida de produção ou num clone novo sem config/jwt/*.pem
# (com a JWT_PASSPHRASE do ambiente).
echo "🔑 Checking JWT keys..."
php bin/console lexik:jwt:generate-keypair --skip-if-exists

echo "🔄 Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "🧹 Clearing and warming cache..."
php bin/console cache:clear

# NOTA (out/2026): o PHP-FPM da imagem de produção roda os workers como
# www-data, e o cache acima foi gerado como root. Sem isto os workers não
# conseguem escrever em var/ (cache pools, logs). O servidor do Symfony CLI
# (dev e teste) roda como root e não precisa.
if [ "$1" = "php-fpm" ]; then
    chown -R www-data:www-data var
fi

echo "🎉 Starting $1..."
exec "$@"
