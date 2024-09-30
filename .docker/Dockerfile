ARG WP_VERSION
ARG PHP_VERSION

FROM wordpress:${WP_VERSION:-6.6}-php${PHP_VERSION:-8.2}

# Needed to specify the build args again after the FROM command.
ARG WP_VERSION
ARG PHP_VERSION

# Save the build args for use by the runtime environment
ENV WP_VERSION=${WP_VERSION}
ENV PHP_VERSION=${PHP_VERSION}

LABEL author=rtcamp

SHELL [ "/bin/bash", "-c" ]

# Install required packages
RUN apt-get update && \
    apt-get -y install \
    git \
    tar \
    gzip \
    mariadb-client \
    net-tools

# Needed for Codeception WPDB test integration.
RUN docker-php-ext-install pdo pdo_mysql

# Install XDebug 3
RUN if [[ $PHP_VERSION == 7* ]]; then pecl install xdebug-3.1.5; else pecl install xdebug; fi \
    && mkdir -p /usr/local/etc/php/conf.d/disabled \
    && echo "zend_extension=xdebug" > /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=develop,debug,coverage" >> /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini \
    && echo "xdebug.max_nesting_level=512" >> /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini \
    ;

# Set xdebug configuration off by default. Set USING_XDEBUG=1 in the runtime environment to enable it.
ENV USING_XDEBUG=0

# Install Dockerize
ENV DOCKERIZE_VERSION v0.7.0
RUN curl -L -O https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer


# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Setup the container for testing
COPY init-docker.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/init-docker.sh
