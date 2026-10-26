#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be set in the deployment environment." >&2
    exit 1
fi

if [ -z "${DB_URL:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
    export DB_URL="${DATABASE_URL}"
fi

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
