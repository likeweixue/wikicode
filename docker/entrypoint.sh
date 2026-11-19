#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/repositories /var/www/html/storage/tmp /var/www/html/storage/locks /var/www/html/storage/releases
chown -R www-data:www-data /var/www/html/storage

runuser -u www-data -- php /var/www/html/database/migrate.php

exec "$@"
