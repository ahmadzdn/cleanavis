#!/bin/bash
# Charge les variables définies dans la console EB pour les processus lancés avec sudo.
# Sans cela, « sudo -u webapp php bin/console » utilisait souvent le DATABASE_URL SQLite du .env
# au lieu du RDS — schéma/seed sur la mauvaise base, 500 sur / en prod.
set -a
for f in /opt/elasticbeanstalk/deployment/env /opt/elasticbeanstalk/deployment/custom_env_var; do
  if [ -r "$f" ]; then
    # shellcheck disable=SC1090
    . "$f"
    break
  fi
done
set +a
