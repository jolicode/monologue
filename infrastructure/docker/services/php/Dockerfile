FROM debian:11.7-slim as php-base

LABEL org.opencontainers.image.source https://github.com/jolicode/monologue

RUN apt-get update \
    && apt install -y --no-install-recommends \
        curl \
        ca-certificates \
        gnupg \
    && curl -s https://packages.sury.org/php/apt.gpg | gpg --dearmor > /usr/share/keyrings/deb.sury.org-php.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php bullseye main" > /etc/apt/sources.list.d/sury.list \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        procps \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

ARG PHP_VERSION

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        php${PHP_VERSION}-apcu \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-uuid \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Fake user to maps with the one on the host
COPY entrypoint /
ARG USER_ID
RUN addgroup --gid $USER_ID app && \
    adduser --system --uid $USER_ID --home /home/app --shell /bin/bash app && \
    curl -Ls https://github.com/tianon/gosu/releases/download/1.16/gosu-amd64 | \
        install /dev/stdin /usr/local/bin/gosu && \
    sed "s/{{ application_user }}/app/g" -i /entrypoint

# Configuration
COPY base/php-configuration /etc/php/${PHP_VERSION}

WORKDIR /home/app/application
ENTRYPOINT [ "/entrypoint" ]

FROM php-base as frontend

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        php${PHP_VERSION}-fpm \
        runit \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && rm -r /etc/php/${PHP_VERSION}/fpm/pool.d/

RUN useradd -s /bin/false nginx

COPY frontend/php-configuration /etc/php/${PHP_VERSION}
COPY frontend/etc/nginx/. /etc/nginx/
COPY frontend/etc/service/. /etc/service/

RUN phpenmod app-default \
    && phpenmod app-fpm

EXPOSE 80

CMD ["runsvdir", "-P", "/etc/service"]

FROM php-base as builder

# Default toys
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        make \
        sudo \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Config
COPY builder/etc/. /etc/
COPY builder/php-configuration /etc/php/${PHP_VERSION}
RUN adduser app sudo \
    && mkdir /var/log/php \
    && chmod 777 /var/log/php \
    && phpenmod app-default \
    && phpenmod app-builder

# Composer
COPY --from=composer/composer:2.5.5 /usr/bin/composer /usr/bin/composer
RUN mkdir -p "/home/app/.composer/cache" \
    && chown app: /home/app/.composer -R

ENV PATH="$PATH:/home/app/application/tools/php-cs-fixer/vendor/bin"
ENV PATH="$PATH:/home/app/application/tools/phpstan/vendor/bin"
