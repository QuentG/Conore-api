# L'image de laquelle on part
FROM php:7.4-apache

# Installation des dépendances nécessaires pour les extensions PHP et wget
RUN apt-get update && apt-get install --no-install-recommends -yq ${BUILD_PACKAGES} \
        build-essential \
        ssh \
        vim \
        git \
        wget \
        unzip \
        libmcrypt-dev \
        libicu-dev \
        libzip-dev \
    && apt-get clean

# Definition d'une variable d'environnement PHP_EXTENSIONS
ENV PHP_EXTENSIONS opcache pdo_mysql pcntl intl zip
# Installation des différents extensions => {PHP_EXTENSIONS}
RUN docker-php-ext-install ${PHP_EXTENSIONS}

# Installation de composer
ENV COMPOSER_ALLOW_SUPERUSER=1
# Téléchargement de composer + alias commande composer
RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin

# Activation d'Apache mod_rewrite
RUN a2enmod rewrite

# On confiure un vhost pour ne pas avoir de /public dans l'url
# Copy du vhost dans 000-default.conf
COPY docker/vhost.conf /etc/apache2/sites-enabled/000-default.conf