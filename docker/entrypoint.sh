#!/usr/bin/env sh
symfony console doctrine:migrations:migrate --no-interaction
exec "$@"