FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql exif

COPY ./public/ /var/www/html/

EXPOSE 80
