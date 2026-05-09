#!/bin/bash
# Diagnostic temporaire : présence de vendor/ après déploiement (voir eb-hooks.log).
set +e
echo "99_check_vendor: ls -la /var/app/current/vendor"
ls -la /var/app/current/vendor 2>&1
echo "99_check_vendor: fin"
exit 0
