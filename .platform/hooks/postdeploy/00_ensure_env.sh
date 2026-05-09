#!/bin/bash
# Si le zip arrive sans .env (déploiement manuel), Dotenv échoue — copie depuis .env.example.
APP="${EB_APP_DEPLOY_DIR:-/var/app/current}"
cd "$APP" || exit 0
set +e
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
  sed -i 's/^APP_ENV=.*/APP_ENV=prod/' .env
  if grep -q '^APP_DEBUG=' .env; then sed -i 's/^APP_DEBUG=.*/APP_DEBUG=0/' .env; else printf '\nAPP_DEBUG=0\n' >> .env; fi
  echo "00_ensure_env: .env créé depuis .env.example"
fi
exit 0
