FROM php:8.0.10-cli-buster

# Download script to install PHP extensions and dependencies
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync

RUN DEBIAN_FRONTEND=noninteractive apt-get update -q \
    && DEBIAN_FRONTEND=noninteractive apt-get install -qq -y \
      curl \
      git \
      zip unzip \
      nano \
      fping \
      netcat \
    && install-php-extensions \
      bcmath \
      bz2 \
      calendar \
      pcntl \
      exif \
      gd \
      intl \
      ldap \
      opcache \
      redis \
      soap \
      xsl \
      zip \
      sockets \
      amqp \
      pdo \
      pdo_pgsql \
      pgsql \
      xdebug

RUN php -m

# Install Composer.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && ln -s $(composer config --global home) /root/composer
ENV PATH=$PATH:/root/composer/vendor/bin COMPOSER_ALLOW_SUPERUSER=1

COPY conf/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY conf/php.ini /usr/local/etc/php/php.ini
COPY scripts/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html
