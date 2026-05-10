#!/bin/bash
# Composer sur EB utilise --no-scripts : importmap:install ne tourne pas au déploiement.
# Télécharge @hotwired/stimulus etc. dans assets/vendor/ (EasyAdmin / Asset Mapper).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
[ -f "${SCRIPT_DIR}/_load_eb_env.sh" ] && . "${SCRIPT_DIR}/_load_eb_env.sh"

APP="${EB_APP_DEPLOY_DIR:-/var/app/current}"
set +e
mkdir -p "${APP}/assets/vendor"
chown -R webapp:webapp "${APP}/assets" "${APP}/var" 2>/dev/null || true

PHP_CLI=(sudo -E -u webapp env HOME=/home/webapp php)
if [ -x "${APP}/bin/console" ]; then
  "${PHP_CLI[@]}" "${APP}/bin/console" importmap:install --env=prod --no-ansi --no-interaction 2>&1
  echo "03_importmap_install: importmap:install terminé (code=$?)"
else
  echo "03_importmap_install: bin/console absent, ignoré"
fi
exit 0
