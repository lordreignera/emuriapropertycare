#!/usr/bin/env bash
set -euo pipefail

cd /app

required_env=(
    APP_KEY
    DB_HOST
    DB_DATABASE
    DB_USERNAME
    DB_PASSWORD
    AWS_ACCESS_KEY_ID
    AWS_SECRET_ACCESS_KEY
    AWS_DEFAULT_REGION
    AWS_BUCKET
    AWS_ENDPOINT
    DIGITAL_TWIN_DISK
)

missing_env=()
for key in "${required_env[@]}"; do
    value="${!key-}"
    if [[ -z "${value}" ]]; then
        missing_env+=("${key}")
    fi
done

if (( ${#missing_env[@]} > 0 )); then
    echo "Missing required worker environment values: ${missing_env[*]}" >&2
    exit 1
fi

php artisan config:clear --no-interaction
php artisan config:cache --no-interaction

echo "ETOGO digital twin worker starting"
php --version | head -n 1
blender --version | head -n 1
pdal --version || true

exec php artisan queue:work database \
    --queue="${DIGITAL_TWIN_PROCESSING_QUEUE:-digital-twin}" \
    --sleep="${DIGITAL_TWIN_WORKER_SLEEP_SECONDS:-3}" \
    --timeout="${DIGITAL_TWIN_CONVERSION_TIMEOUT:-3600}" \
    --tries="${DIGITAL_TWIN_WORKER_TRIES:-1}" \
    --memory="${DIGITAL_TWIN_WORKER_MEMORY_MB:-7168}"
