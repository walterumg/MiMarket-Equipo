FROM php:8.4-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
      /etc/apache2/sites-available/*.conf \
      /etc/apache2/apache2.conf \
      /etc/apache2/conf-available/*.conf \
    && chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 10000

CMD ["sh", "-c", "sed -ri 's!Listen 80!Listen '${PORT:-10000}'!g' /etc/apache2/ports.conf && sed -ri 's!<VirtualHost \\*:80>!<VirtualHost *:'${PORT:-10000}'>!g' /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
