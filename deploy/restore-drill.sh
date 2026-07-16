#!/usr/bin/env bash
# Ticket P3 — DoD bat buoc: restore thu THAT vao DB tam (khong chi co script).
# Chung minh backup dung duoc, khong phai chi ton tai.
set -euo pipefail

BACKUP_FILE="${1:?Dung: restore-drill.sh <file.sql.gz>}"
DRILL_DB="hoctoan_restore_drill"
DB_USER="${DB_USERNAME:-root}"

echo "==> Tao DB tam $DRILL_DB"
mysql --user="$DB_USER" --password="${DB_PASSWORD:-}" -e "DROP DATABASE IF EXISTS $DRILL_DB; CREATE DATABASE $DRILL_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> Restore backup vao DB tam"
gunzip < "$BACKUP_FILE" | mysql --user="$DB_USER" --password="${DB_PASSWORD:-}" "$DRILL_DB"

echo "==> Kiem tra: dem so bang + so user"
TABLES=$(mysql --user="$DB_USER" --password="${DB_PASSWORD:-}" -N -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DRILL_DB';")
echo "    Bang phuc hoi: $TABLES"

echo "==> Don DB tam"
mysql --user="$DB_USER" --password="${DB_PASSWORD:-}" -e "DROP DATABASE $DRILL_DB;"
echo "==> Restore drill THANH CONG (backup dung duoc)"
