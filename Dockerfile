FROM php:8.3-apache
RUN a2dismod mpm_event && a2enmod mpm_prefork
RUN docker-php-ext-install pdo pdo_mysql mysqli
COPY . /var/www/html/
