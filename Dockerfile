FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends curl libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring mysqli gd opcache \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . /var/www/html/
COPY docker/entrypoint.sh /usr/local/bin/kitchenmart-entrypoint
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/production.ini /usr/local/etc/php/conf.d/production.ini
RUN chmod +x /usr/local/bin/kitchenmart-entrypoint && mkdir -p /var/www/html/.runtime-sessions /var/www/html/assets/images/products/uploads /var/www/html/assets/images/banners/uploads /var/www/html/assets/videos/products/uploads && chown -R www-data:www-data /var/www/html/.runtime-sessions /var/www/html/assets/images/products/uploads /var/www/html/assets/images/banners/uploads /var/www/html/assets/videos/products/uploads
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 CMD curl -fsS http://127.0.0.1/healthz.php || exit 1
ENTRYPOINT ["kitchenmart-entrypoint"]
CMD ["apache2-foreground"]
