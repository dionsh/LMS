#!/usr/bin/env bash
# Checks the school structure loaded by database/demo/school.php:
# 45 classes each with a homeroom teacher, 80 staff records without
# credentials, the five real homeroom teachers, 10 enrolled students —
# and that the admin/student pages show it. Development database only.
#
#   bash tests/school_data_test.sh
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1"; }
"$PHP" database/demo/test-accounts.php > /dev/null || exit 1
"$PHP" database/demo/school.php > /dev/null || exit 1
"$PHP" database/demo/school.php > /dev/null || exit 1    # twice: must not create duplicates

T=$(mktemp -d); pass=0; fail=0
check() { if [ "$2" = "$3" ]; then pass=$((pass+1)); printf "PASS  %-60s %s\n" "$1" "$3";
          else fail=$((fail+1)); printf "FAIL  %-60s got '%s' expected '%s'\n" "$1" "$3" "$2"; fi; }
contains() { if grep -q "$3" "$2"; then pass=$((pass+1)); printf "PASS  %-60s contains \"%s\"\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-60s missing \"%s\"\n" "$1" "$3"; fi; }
token() { curl -s -b "$1" -c "$1" "$B$2" | grep -oE 'name="_token" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)"/\1/'; }
login() { local t; t=$(token "$1" /hyr); curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" \
            --data-urlencode "_token=$t" --data-urlencode "login=$2" --data-urlencode "password=$3" "$B/hyr" | sed "s#$B##"; }
code()  { curl -s -o "$T/body" -w "%{http_code}" -b "$1" -c "$1" "$B$2"; }
YEAR="(SELECT id FROM academic_years WHERE is_current = 1)"

echo "== Structure (after running the loader twice) =="
check "45 classes this year"                         "45" "$(SQL "SELECT COUNT(*) FROM classes WHERE academic_year_id = $YEAR")"
check "15 classes per grade"                         "10:15 11:15 12:15" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',n) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, COUNT(*) n FROM classes GROUP BY grade_level) x")"
check "every class has a homeroom teacher"           "0"  "$(SQL "SELECT COUNT(*) FROM classes WHERE homeroom_teacher_id IS NULL")"
check "no teacher is homeroom of two classes"        "0"  "$(SQL "SELECT COUNT(*) FROM (SELECT homeroom_teacher_id FROM classes GROUP BY homeroom_teacher_id HAVING COUNT(*) > 1) d")"
check "80 staff records without credentials"         "80" "$(SQL "SELECT COUNT(*) FROM users WHERE role='teacher' AND password_hash IS NULL")"
check "35 teachers without a homeroom class"         "35" "$(SQL "SELECT COUNT(*) FROM users u WHERE role='teacher' AND password_hash IS NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.homeroom_teacher_id = u.id)")"
check "XII/1–XII/5 homeroom teachers (from the school)" \
      "1 Enver Bajrami|2 Hysnije Mustafa|3 Dhurata Sahiti|4 Behar Krasniqi|5 Avni Hashani" \
      "$(SQL "SELECT GROUP_CONCAT(CONCAT(c.section,' ',u.first_name,' ',u.last_name) ORDER BY c.section SEPARATOR '|') FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.grade_level = 12 AND c.section <= 5")"
check "only the 5 real teachers are public-website-visible" "5" "$(SQL "SELECT COUNT(*) FROM teacher_profiles WHERE show_on_website = 1")"
check "students per class"                           "X/13:3 XI/5:3 XII/1:4" "$(SQL "SELECT GROUP_CONCAT(CONCAT(ELT(c.grade_level-9,'X','XI','XII'),'/',c.section,':',n) ORDER BY c.grade_level SEPARATOR ' ') FROM (SELECT class_id, COUNT(*) n FROM enrollments GROUP BY class_id) e JOIN classes c ON c.id = e.class_id")"
check "10 demo students with profiles"               "10" "$(SQL "SELECT COUNT(*) FROM student_profiles WHERE date_of_birth IS NOT NULL")"

echo; echo "== Signing in =="
X="$T/x"
check "a staff record without credentials cannot sign in" "422 " "$(login $X enver.bajrami anything-at-all-1)"
S="$T/s"
check "demo student signs in"                         "302 /nxenesi" "$(login $S ariana.gashi Nxenes-Demo-2026)"
check "student dashboard"                             "200" "$(code $S /nxenesi)"
contains "dashboard shows class and homeroom teacher" "$T/body" "Klasa XII/1"
contains "…with the homeroom teacher's name"          "$T/body" "Prof. Enver Bajrami"
check "student cannot open the admin class list"      "403" "$(code $S /admin/klasat)"
U="$T/u"; login $U prove.nxenes Prove-Nxenes-2026 >/dev/null; code $U /nxenesi >/dev/null
contains "a student without a class is told so"        "$T/body" "Ende nuk jeni regjistruar"

echo; echo "== Admin pages =="
A="$T/a"; login $A prove.admin Prove-Admin-2026 >/dev/null
check "Klasat"                                        "200" "$(code $A /admin/klasat)"
contains "…lists XII/4 with Behar Krasniqi"            "$T/body" "Prof. Behar Krasniqi"
contains "…shows the grade sections"                  "$T/body" "Klasa XII"
check "Mësimdhënësit"                                 "200" "$(code $A /admin/mesimdhenesit)"
contains "…marks staff without credentials"           "$T/body" "Pa fletë hyrjeje"
check "Nxënësit"                                      "200" "$(code $A /admin/nxenesit)"
contains "…lists demo students with their class"      "$T/body" "diellza.morina"
check "admin dashboard"                               "200" "$(code $A /admin)"
contains "…homeroom step complete"                    "$T/body" "45 nga 45 klasa"

echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
