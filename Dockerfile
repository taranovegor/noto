FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json ./
RUN npm install

COPY vite.config.ts tsconfig.json ./
COPY assets ./assets
RUN npm run build

FROM php:8.4-cli-trixie AS base

ARG UID=1000
ARG GID=1000
ENV USERNAME=appuser

RUN apt-get update && apt-get install -y \
    git \
    curl \
    ffmpeg \
    postgresql-client \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    librabbitmq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure intl \
    && docker-php-ext-configure pdo_pgsql \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    intl \
    sockets \
    zip

RUN pecl install amqp && docker-php-ext-enable amqp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=ghcr.io/roadrunner-server/roadrunner:2025 /usr/bin/rr /usr/local/bin/rr

WORKDIR /app

RUN groupadd -g ${GID} ${USERNAME} && \
    useradd -m -u ${UID} -g ${GID} ${USERNAME} && \
    chown -R ${UID}:${GID} /app

EXPOSE 8080

ENTRYPOINT ["rr"]

FROM base AS development

RUN apt-get update && apt-get install -y \
    gdb \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

USER ${USERNAME}

CMD ["serve", "-c", ".rr.dev.yaml"]

FROM base AS production

ARG UID=1000
ARG GID=1000
ARG VERSION=unknown
ENV VERSION=${VERSION}

COPY --from=frontend --chown=${UID}:${GID} /app/public/build ./public/build

COPY --chown=${UID}:${GID} . .

RUN composer install --no-dev --optimize-autoloader --no-scripts

USER ${USERNAME}

CMD ["serve", "-c", ".rr.yaml"]
