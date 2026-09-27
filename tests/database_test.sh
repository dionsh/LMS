#!/usr/bin/env bash
# Imports schema.sql + seed.sql into a THROWAWAY database, then runs
# database_constraints.sql: every "must fail" statement has to be rejected
# by MariaDB and every "must succeed" statement accepted. The throwaway
# database is dropped afterwards; the real one is never touched.
#
#   bash tests/database_test.sh
cd "$(dirname "$0")/.." || exit 1
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
DB="kuvendi_lms_constraint_test"
EXPECTED_REJECTIONS=21

"$MYSQL" -u root -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"$MYSQL" -u root --default-character-set=utf8mb4 "$DB" -e "source database/schema.sql; source database/seed.sql;" || exit 1
OUT=$("$MYSQL" -u root --default-character-set=utf8mb4 --force -t "$DB" -e "source tests/database_constraints.sql" 2>&1)
"$MYSQL" -u root -e "DROP DATABASE $DB;"

echo "$OUT" | grep '^ERROR' | sed -E "s/ at line [0-9]+ in file: '[^']+'//"
REJECTED=$(echo "$OUT" | grep -c '^ERROR')
echo
echo "Rejected: $REJECTED (expected $EXPECTED_REJECTIONS)"
[ "$REJECTED" -eq "$EXPECTED_REJECTIONS" ]
