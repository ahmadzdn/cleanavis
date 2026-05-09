#!/bin/bash
set -e
chown -R webapp:webapp /var/app/current/var
sudo -u webapp php /var/app/current/bin/console cache:clear --env=prod
