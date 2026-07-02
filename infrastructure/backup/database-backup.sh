#!/bin/bash

# Configuration
DB_NAME=${DB_DATABASE:-laravel}
DB_USER=${DB_USERNAME:-root}
DB_PASS=${DB_PASSWORD:-password}
DB_HOST=${DB_HOST:-127.0.0.1}
BACKUP_DIR="/var/www/html/storage/backups"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_FILE="$BACKUP_DIR/backup_$DATE.sql.gz"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Perform backup using mysqldump
echo "Starting backup of $DB_NAME at $DATE..."
mysqldump --complete-insert -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "Backup successful: $BACKUP_FILE"
    
    # Prune old backups (keep last 7 days)
    find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +7 -delete
    echo "Old backups pruned."
else
    echo "Backup failed!"
    exit 1
fi
