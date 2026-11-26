#!/usr/bin/env sh
set -eu
umask 077
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then cp .env.example .env; fi
if ! grep -q '^DB_CONNECTION=pgsql' .env; then
    echo 'Используйте Docker-конфигурацию .env.example. Существующий .env оставлен без изменения.' >&2
    exit 1
fi
# Пароли создаются через системный криптографический генератор, без вывода в журнал.
for setting in DB_PASSWORD DEMO_PASSWORD; do
    if grep -Eq "^${setting}=[[:space:]]*$" .env; then
        secret=$(openssl rand -hex 24)
        sed "s/^${setting}=.*/${setting}=${secret}/" .env > .env.setup
        mv .env.setup .env
    fi
done
docker compose config --quiet
docker compose build
if grep -Eq '^APP_KEY=[[:space:]]*$' .env; then
    key=$(docker compose run --rm --no-deps app php artisan key:generate --show)
    case "$key" in base64:*) ;; *) echo 'Не удалось создать APP_KEY' >&2; exit 1;; esac
    sed "s|^APP_KEY=.*|APP_KEY=${key}|" .env > .env.setup
    mv .env.setup .env
fi
docker compose up -d --wait
docker compose exec -T app php artisan migrate --seed --force
echo 'Кабинет: http://localhost:8000 | Почта: http://localhost:8025'
echo 'Пароль демо-аккаунтов находится в DEMO_PASSWORD локального .env.'
