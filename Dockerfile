FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git ca-certificates libonig-dev \
    && docker-php-ext-install pdo_mysql opcache mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# 安装 Docker CLI（用于 workflow 隔离执行，通过 mount 的 /var/run/docker.sock 调用宿主机 Docker）
COPY --from=docker:27-cli /usr/local/bin/docker /usr/local/bin/docker

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/wikicode.ini
COPY docker/entrypoint.sh /usr/local/bin/wikicode-entrypoint
RUN chmod +x /usr/local/bin/wikicode-entrypoint

WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage/repositories storage/tmp storage/locks \
    && chown -R www-data:www-data storage

ENTRYPOINT ["wikicode-entrypoint"]
CMD ["apache2-foreground"]
