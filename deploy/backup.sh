#!/usr/bin/env bash
# Ticket P3 — backup mysqldump hang ngay (cron 2h sang VN), giu 14 ngay, day off-server.
set -euo pipefail

DB_NAME="${DB_DATABASE:-hoctoanonline}"
DB_USER="${DB_USERNAME:-hoctoan}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/hoctoan}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
STAMP="$(date +%Y%m%d_%H%M%S)"
FILE="$BACKUP_DIR/hoctoan_${STAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "==> Dump $DB_NAME -> $FILE"
mysqldump --single-transaction --quick --user="$DB_USER" --password="${DB_PASSWORD:-}" "$DB_NAME" | gzip > "$FILE"

echo "==> Day off-server (S3/rsync — thay bang dich that)"
# aws s3 cp "$FILE" "s3://your-bucket/hoctoan/" || echo "CANH BAO: day off-server that bai"

echo "==> Xoa backup cu hon $RETENTION_DAYS ngay"
find "$BACKUP_DIR" -name 'hoctoan_*.sql.gz' -mtime +"$RETENTION_DAYS" -delete

echo "==> Backup xong: $FILE"
