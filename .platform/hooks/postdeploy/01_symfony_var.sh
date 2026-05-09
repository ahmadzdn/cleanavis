#!/bin/bash
# Droits sur var/ après déploiement (cache Symfony, logs).
set -e
APP="${EB_APP_DEPLOY_DIR:-/var/app/current}"
cd "$APP" || exit 0
mkdir -p var/cache var/log var/share
chmod -R ug+rwx var 2>/dev/null || chmod -R 775 var || true
exit 0
