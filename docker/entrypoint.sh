#!/bin/sh
set -eu
case "${APP_URL:-}" in
  https://*) ;;
  *) echo "APP_URL must use HTTPS" >&2; exit 64 ;;
esac
for name in APP_KEY PAYMENT_WEBHOOK_SECRET DB_PASS; do
  eval "value=\${$name:-}"
  if [ "${#value}" -lt 32 ]; then
    echo "$name must contain at least 32 characters" >&2
    exit 64
  fi
done
echo "Waiting for MySQL..."
until php -r '$db=@new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_NAME"),getenv("DB_USER"),getenv("DB_PASS")); exit($db?0:1);'; do
  sleep 2
done
php /var/www/html/tools/migrate.php
chown -R www-data:www-data /var/www/html/.runtime-sessions /var/www/html/assets/images/products/uploads /var/www/html/assets/images/banners/uploads /var/www/html/assets/videos/products/uploads
if [ "${MAIL_ASYNC:-1}" = "1" ]; then
  (
    while true; do
      php /var/www/html/tools/process-email-queue.php 50 || true
      sleep 60
    done
  ) &
fi
exec "$@"
