#!/usr/bin/env bash
# Update Tamin Call on the server: back the database up, pull main, then clear the caches.
# Run it from the app folder:  cd /usr/share/nginx/html/tamincall && ./deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

BACKUP_DIR=/root/tamincall-backup
mkdir -p "$BACKUP_DIR"
# one fixed file, overwritten every time, so backups never pile up on the server
cp database/database.sqlite "$BACKUP_DIR/database-latest.sqlite"
echo "Database backed up to $BACKUP_DIR/database-latest.sqlite"

git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan view:clear

chown -R www-data:www-data storage bootstrap/cache database
echo "Done."
