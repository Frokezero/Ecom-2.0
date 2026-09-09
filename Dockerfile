FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql mbstring mysqli && a2enmod rewrite headers expires deflate
WORKDIR /var/www/html
COPY . /var/www/html/
COPY docker/entrypoint.sh /usr/local/bin/kitchenmart-entrypoint
RUN chmod +x /usr/local/bin/kitchenmart-entrypoint && mkdir -p /var/www/html/.runtime-sessions /var/www/html/assets/images/products/uploads /var/www/html/assets/images/banners/uploads && chown -R www-data:www-data /var/www/html/.runtime-sessions /var/www/html/assets/images/products/uploads /var/www/html/assets/images/banners/uploads
EXPOSE 80
ENTRYPOINT ["kitchenmart-entrypoint"]
CMD ["apache2-foreground"]
