#!/bin/bash
# RDS PostgreSQL : schéma depuis les entités + seed packs/FAQ + versions migrations enregistrées.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
[ -f "${SCRIPT_DIR}/_load_eb_env.sh" ] && . "${SCRIPT_DIR}/_load_eb_env.sh"

APP="${EB_APP_DEPLOY_DIR:-/var/app/current}"
CONSOLE="${APP}/bin/console"

set +e

if [ ! -x "${CONSOLE}" ]; then
  echo "02_database_provision: bin/console absent"
  exit 0
fi

chown -R webapp:webapp "${APP}/var" 2>/dev/null || true

PHP_CLI=(sudo -E -u webapp env HOME=/home/webapp php)

echo "02_database_provision: DATABASE_URL (aperçu)"
(DATABASE_URL_SAFE="${DATABASE_URL%%@*}@***"; echo "${DATABASE_URL_SAFE}")

echo "02_database_provision: doctrine:migrations:sync-metadata-storage"
"${PHP_CLI[@]}" "${CONSOLE}" doctrine:migrations:sync-metadata-storage --env=prod --no-ansi --no-interaction 2>&1

echo "02_database_provision: doctrine:schema:create"
"${PHP_CLI[@]}" "${CONSOLE}" doctrine:schema:create --env=prod --no-ansi --no-interaction 2>&1
echo "02_database_provision: schema:create code=$? (≠0 si tables déjà créées : attendu)"

echo "02_database_provision: app:seed-reference-data"
"${PHP_CLI[@]}" "${CONSOLE}" app:seed-reference-data --env=prod --no-ansi --no-interaction 2>&1

echo "02_database_provision: doctrine:migrations:version --add --all"
"${PHP_CLI[@]}" "${CONSOLE}" doctrine:migrations:version --add --all --no-interaction --env=prod --no-ansi 2>&1

echo "02_database_provision: contrôle pack_offer / faq_item"
"${PHP_CLI[@]}" "${CONSOLE}" doctrine:query:sql "SELECT COUNT(*) AS packs FROM pack_offer" --env=prod --no-ansi 2>&1
"${PHP_CLI[@]}" "${CONSOLE}" doctrine:query:sql "SELECT COUNT(*) AS faqs FROM faq_item" --env=prod --no-ansi 2>&1

echo "02_database_provision: fin"
exit 0
