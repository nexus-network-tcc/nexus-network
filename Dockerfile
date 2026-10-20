FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

WORKDIR /var/www/html
COPY . /var/www/html

# O Apache roda como www-data e precisa gravar em logs, lembretes e fotos enviadas.
RUN mkdir -p storage/logs public/uploads/fotos \
    && chown -R www-data:www-data storage public/uploads

# A Render informa a porta pela variavel PORT (padrao 10000).
CMD ["sh", "-c", "sed -ri \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -ri \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
