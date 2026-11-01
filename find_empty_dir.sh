#!/bin/bash

# Script para encontrar diretórios vazios recursivamente
# Uso: ./script.sh [caminho]

# Define o caminho inicial (usa o diretório atual se nenhum for fornecido)
caminho="${1:-.}"

# Verifica se o caminho existe
if [ ! -d "$caminho" ]; then
    echo "Erro: '$caminho' não é um diretório válido"
    exit 1
fi

echo "Buscando diretórios vazios em: $caminho"
echo "----------------------------------------"

# Encontra todos os diretórios vazios recursivamente
find "$caminho" -type d -empty | while read -r dir; do
  touch "$dir/.gitignore"
  echo "$dir"
done

# Conta quantos diretórios vazios foram encontrados
total=$(find "$caminho" -type d -empty | wc -l)
echo "----------------------------------------"
echo "Total de diretórios vazios: $total"
