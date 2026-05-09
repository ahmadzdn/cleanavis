#!/bin/bash
# RDS PostgreSQL vide : schéma Doctrine depuis les entités (compatible PG), puis seed packs/FAQ,
# puis enregistrement des migrations (les fichiers migrations/ sont en SQL SQLite — migrate échouerait sur RDS).
APP="${EB_APP_DEPLOY_DIR:-/var/app/current}"
CONSOLE="${APP}/bin/console"

set +e

if [ ! -x "${CONSOLE}" ]; then
  echo "02_database_provision: bin/console absent"
  exit 0
fi

chown -R webapp:webapp "${APP}/var" 2>/dev/null || true

echo "02_database_provision: doctrine:migrations:sync-metadata-storage"
sudo -u webapp php "${CONSOLE}" doctrine:migrations:sync-metadata-storage --env=prod --no-ansi --no-interaction 2>&1

echo "02_database_provision: doctrine:schema:create"
sudo -u webapp php "${CONSOLE}" doctrine:schema:create --env=prod --no-ansi --no-interaction 2>&1
echo "02_database_provision: schema:create code=$? (≠0 si tables déjà créées : attendu)"

echo "02_database_provision: app:seed-reference-data"
sudo -u webapp php "${CONSOLE}" app:seed-reference-data --env=prod --no-ansi --no-interaction 2>&1

echo "02_database_provision: doctrine:migrations:version --add --all"
sudo -u webapp php "${CONSOLE}" doctrine:migrations:version --add --all --no-interaction --env=prod --no-ansi 2>&1

echo "02_database_provision: fin"
exit 0
