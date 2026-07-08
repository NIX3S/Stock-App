FROM php:8.4-apache

# Extensions PHP
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    unzip \
    zip \
    git \
    openssl \
    && docker-php-ext-install \
        pdo_mysql \
        mysqli \
        mbstring \
        xml \
        curl \
        zip \
        gd
 
# Apache modules
RUN a2enmod rewrite headers ssl

# Apache config
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# SSL certs
COPY docker/ssl/apache-selfsigned.crt /etc/ssl/certs/apache-selfsigned.crt
COPY docker/ssl/apache-selfsigned.key /etc/ssl/private/apache-selfsigned.key

WORKDIR /var/www/html

EXPOSE 80 443
