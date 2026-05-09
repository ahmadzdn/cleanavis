#!/bin/bash
# Vide le cache Symfony en prod. Les erreurs (APP_SECRET, vendor, etc.) ne doivent pas faire échouer EB.
APP="${EB_APP_DEPLOY_DIR:-/var/app/current}"
set +e
chown -R webapp:webapp "${APP}/var" 2>/dev/null || true
if [ -x "${APP}/bin/console" ]; then
  sudo -u webapp php "${APP}/bin/console" cache:clear --env=prod --no-ansi 2>&1
  echo "03_cache_clear: cache:clear terminé (code=$?)"
else
  echo "03_cache_clear: bin/console absent, ignoré"
fi
exit 0
