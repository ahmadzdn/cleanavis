#!/bin/bash
# Installe les dépendances sur l’instance si l’archive ne contient pas vendor
# (recommandé EB). APP_* pour éviter que les scripts Composer ne chargent le mode dev.
set -euo pipefail

STAGING="${EB_APP_STAGING_DIR:-/var/app/staging}"
cd "$STAGING"

if [[ ! -f composer.json ]]; then
  exit 0
fi

export COMPOSER_ALLOW_SUPERUSER=1
export APP_ENV=prod
export APP_DEBUG=0

if [[ -f vendor/autoload.php ]]; then
  exit 0
fi

composer install --no-dev --no-interaction --optimize-autoloader --no-scripts
