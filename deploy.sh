#!/usr/bin/env bash
# Update Tamin Call on the server: back the database up, pull main, then clear the caches.
# Run it from the app folder:  cd /usr/share/nginx/html/tamincall && ./deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

BACKUP_DIR=/root/tamincall-backup
BACKUP_FILE="$BACKUP_DIR/database-latest.sqlite"
mkdir -p "$BACKUP_DIR"

# One fixed file, replaced every time, so backups never pile up on the server.
# VACUUM INTO writes a complete, consistent copy even while the app is in use: the database runs
# in WAL mode, where a plain cp of the main file can miss the latest changes.
if [ -f database/database.sqlite ]; then
    rm -f "$BACKUP_FILE"
    php -r '$db = new PDO("sqlite:database/database.sqlite"); $db->exec("VACUUM INTO " . $db->quote($argv[1]));' "$BACKUP_FILE"
    echo "Database backed up to $BACKUP_FILE"
fi

git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan view:clear

# everything the app writes belongs to the web server, including files root just created
chown -R www-data:www-data storage bootstrap/cache database
echo "Done."
