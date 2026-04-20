#!/usr/bin/env bash
set -euo pipefail

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

error_exit() {
    log "ERROR: $1"
    exit 1
}

: "${APP_ENV:?APP_ENV non impostata}"
: "${DATABASE_URL:?DATABASE_URL non impostata}"
: "${APP_SECRET:?APP_SECRET non impostata}"
: "${PORT:=8080}"

cd /var/www/html

log "=== ContiApp bootstrap ==="
log "APP_ENV=${APP_ENV}"
log "PORT=${PORT}"

if [ ! -f "vendor/autoload.php" ]; then
    error_exit "vendor/autoload.php non trovato nell'immagine"
fi

if [ -n "${MYSQL_SSL_CA_BASE64:-}" ]; then
    log "Decodifica certificato CA MySQL da variabile base64"
    mkdir -p config/ssl
    echo "${MYSQL_SSL_CA_BASE64}" | base64 -d > "${MYSQL_SSL_CA:-/var/www/html/config/ssl/aiven-ca.pem}"
    chmod 644 "${MYSQL_SSL_CA:-/var/www/html/config/ssl/aiven-ca.pem}"
fi

mkdir -p var/cache var/log var/sessions
chown -R www-data:www-data var || true

log "Pulizia cache Symfony"
php bin/console cache:clear --env="${APP_ENV}" --no-debug --no-interaction \
    || error_exit "cache:clear fallito"

log "Warmup cache Symfony"
php bin/console cache:warmup --env="${APP_ENV}" --no-debug --no-interaction \
    || error_exit "cache:warmup fallito"

MAX_RETRIES=30
RETRY_DELAY=2
RETRY_COUNT=0

log "Attendo la disponibilita' del database"
until php bin/console doctrine:query:sql "SELECT 1" --env="${APP_ENV}" --no-interaction > /dev/null 2>&1; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ "${RETRY_COUNT}" -ge "${MAX_RETRIES}" ]; then
        error_exit "Database non raggiungibile dopo ${MAX_RETRIES} tentativi"
    fi

    log "Database non raggiungibile, attendo ${RETRY_DELAY}s (${RETRY_COUNT}/${MAX_RETRIES})"
    sleep "${RETRY_DELAY}"
done

log "Database raggiungibile, eseguo le migration"
php bin/console doctrine:migrations:migrate \
    --env="${APP_ENV}" \
    --no-interaction \
    --allow-no-migration \
    || error_exit "Migrazioni fallite"

envsubst '${PORT}' \
    < /etc/nginx/http.d/default.conf.bak \
    > /etc/nginx/http.d/default.conf

nginx -t || error_exit "Configurazione Nginx non valida"

log "Contenuto default.conf generato:"
cat /etc/nginx/http.d/default.conf

log "Avvio supervisor"
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf