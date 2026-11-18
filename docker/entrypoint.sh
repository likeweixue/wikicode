#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/repositories /var/www/html/storage/tmp /var/www/html/storage/locks
chown -R www-data:www-data /var/www/html/storage

runuser -u www-data -- php /var/www/html/database/migrate.php

exec "$@"
