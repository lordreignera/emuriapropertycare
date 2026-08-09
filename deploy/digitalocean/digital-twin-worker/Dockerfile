FROM php:8.3-cli-bookworm

ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV MAMBA_ROOT_PREFIX=/opt/micromamba
ENV PATH="${MAMBA_ROOT_PREFIX}/bin:${PATH}"

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        blender \
        bzip2 \
        ca-certificates \
        curl \
        default-mysql-client \
        git \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install bcmath curl intl mbstring pcntl pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN curl -Ls https://micro.mamba.pm/api/micromamba/linux-64/latest \
        | tar -xvj -C /usr/local/bin --strip-components=1 bin/micromamba \
    && micromamba install -y -n base -c conda-forge pdal=2.10.2 \
    && micromamba clean -a -y

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
COPY deploy/digitalocean/digital-twin-worker/entrypoint.sh /usr/local/bin/etogo-digital-twin-worker

RUN chmod +x /usr/local/bin/etogo-digital-twin-worker \
    && composer dump-autoload --optimize \
    && mkdir -p storage/app/private/tmp/digital-twin storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && php artisan package:discover --ansi

CMD ["etogo-digital-twin-worker"]
