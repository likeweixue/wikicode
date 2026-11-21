#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/repositories /var/www/html/storage/tmp /var/www/html/storage/locks /var/www/html/storage/releases
chown -R www-data:www-data /var/www/html/storage

# 让 www-data 能访问 docker.sock（用于 workflow 隔离执行）
# 把 www-data 加入 socket 对应的组；如果 socket 不存在则跳过
if [ -S /var/run/docker.sock ]; then
    SOCK_GID=$(stat -c '%g' /var/run/docker.sock)
    if ! getent group "$SOCK_GID" >/dev/null 2>&1; then
        groupadd -g "$SOCK_GID" dockerhost 2>/dev/null || true
    fi
    usermod -aG "$SOCK_GID" www-data 2>/dev/null || true
fi

runuser -u www-data -- php /var/www/html/database/migrate.php

exec "$@"
