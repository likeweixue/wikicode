FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git ca-certificates libonig-dev \
    && docker-php-ext-install pdo_mysql opcache mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

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
