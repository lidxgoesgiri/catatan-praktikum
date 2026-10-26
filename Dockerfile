FROM php:8.4-apache-bookworm

# Driver PDO untuk menghubungkan PHP dengan MySQL.
RUN docker-php-ext-install pdo_mysql

# Hanya direktori public yang dapat diakses melalui browser.
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/catatan.ini

WORKDIR /var/www/html
COPY app/ ./

EXPOSE 80
CMD ["apache2-foreground"]
