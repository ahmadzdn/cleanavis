#!/bin/bash
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
[ -f "${SCRIPT_DIR}/_load_eb_env.sh" ] && . "${SCRIPT_DIR}/_load_eb_env.sh"

# Vide le cache Symfony en prod ; erreurs non bloquantes pour EB.
APP="${EB_APP_DEPLOY_DIR:-/var/app/current}"
set +e
chown -R webapp:webapp "${APP}/var" 2>/dev/null || true
PHP_CLI=(sudo -E -u webapp env HOME=/home/webapp php)
if [ -x "${APP}/bin/console" ]; then
  "${PHP_CLI[@]}" "${APP}/bin/console" cache:clear --env=prod --no-ansi 2>&1
  echo "04_cache_clear: cache:clear terminé (code=$?)"
  "${PHP_CLI[@]}" "${APP}/bin/console" cache:warmup --env=prod --no-ansi --no-interaction 2>&1
  echo "04_cache_clear: cache:warmup terminé (code=$?)"
else
  echo "04_cache_clear: bin/console absent, ignoré"
fi
exit 0
