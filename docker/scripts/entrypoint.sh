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

# NOTA (nov/2026): o Symfony CLI guarda o PID do servidor num arquivo
# (<pasta do CLI>/var/<sha1 da pasta do projeto>.pid) e, antes de subir,
# pergunta ao sistema se aquele PID está vivo. Quando o PC desliga sem
# parar o contêiner, o arquivo fica no disco do contêiner; na subida
# seguinte os PIDs recomeçam do 1, o PID antigo aponta para um processo
# vivo do contêiner novo (em geral o próprio PID 1, este script) e o CLI
# responde "The local web server is already running" e sai com código 0 —
# o contêiner para em seguida.
# Aqui, no começo da vida do contêiner, nenhum servidor do CLI está
# rodando, então o estado guardado é sempre velho: apagar os arquivos
# resolve, sem procurar e matar PIDs (o PID do arquivo pode ser justamente
# o deste script). São as duas pastas que o CLI usa: a nova
# (${XDG_CONFIG_HOME:-~/.config}/symfony-cli, desde a 5.17) e a antiga
# (~/.symfony5, que ele continua usando quando existe). Só a subpasta var/
# (PIDs dos servidores e dos workers); configuração e logs ficam.
if [ "$1" = "symfony" ]; then
    echo "🧽 Removing stale Symfony CLI server state..."
    rm -rf "${HOME}/.symfony5/var" "${XDG_CONFIG_HOME:-${HOME}/.config}/symfony-cli/var"
fi

echo "🎉 Starting $1..."
exec "$@"
