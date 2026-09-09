FROM php:8.2-apache

# curl (TMDB API calls) + mbstring (used by title-similarity matching) + opcache (perf)
# Note: the *-dev packages are intentionally left installed (not purged) -
# apt can't see that the compiled curl/mbstring .so files link against their
# runtime libs, so autoremoving the dev packages here can drag the runtime
# libs out with them and break the extensions at boot.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
    && docker-php-ext-install curl mbstring opcache \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=64'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN a2enmod headers

WORKDIR /var/www/html

COPY . /var/www/html

# .env is bind-mounted at runtime by docker-compose.yml, never baked into the image
RUN rm -f /var/www/html/.env \
    && mkdir -p /var/www/html/data/cache \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

EXPOSE 80
