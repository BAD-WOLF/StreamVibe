#!/usr/bin/env bash

# Função para atualizar variável de ambiente
update_env_var() {
    VAR_NAME=$1
    NEW_HOST=$2

    VAR_VALUE=$(symfony console dotenv:show | grep "^${VAR_NAME}=" | cut -d '=' -f2-)

    if [ -n "$VAR_VALUE" ]; then
        if [[ "$VAR_VALUE" == *"localhost:"* || "$VAR_VALUE" == *"127.0.0.1:"* ]]; then
            NEW_VAR_VALUE=$(echo "$VAR_VALUE" | sed -E "s/(localhost|127\.0\.0\.1)/$NEW_HOST/")

            sed -i "/^export ${VAR_NAME}=/d" ~/.bashrc
            echo "export ${VAR_NAME}=\"$NEW_VAR_VALUE\"" >> ~/.bashrc
            source ~/.bashrc

            echo "${VAR_NAME} atualizado para:"
            echo "$NEW_VAR_VALUE"
        fi
    else
        echo "Variável ${VAR_NAME} não encontrada."
    fi
}

# Define variáveis e hosts
VARIAVEIS=(
  "DATABASE_URL:database"
  "MAILER_DSN:mailer"
  # Adicione mais aqui
)

# Processa todas
for item in "${VARIAVEIS[@]}"; do
    VAR_NAME=$(echo "$item" | cut -d':' -f1)
    NEW_HOST=$(echo "$item" | cut -d':' -f2)
    update_env_var "$VAR_NAME" "$NEW_HOST"
done

symfony console secrets:decrypt-to-local --force

symfony console doctrine:migrations:migrate --no-interaction

exec "$@"