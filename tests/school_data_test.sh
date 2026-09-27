#!/usr/bin/env bash
# Checks the school structure loaded by database/demo/school.php: 37 classes
# (X afternoon; XI-1…7 and XII-1…15 morning, as on the official timetable),
# each with a homeroom teacher and its grade's subjects, 80 staff records
# without credentials, the 22 real homeroom teachers, 10 enrolled students —
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
LABEL="CONCAT(ELT(c.grade_level-9,'X','XI','XII'),'-',c.section)"
HOURS="COALESCE(cs.weekly_hours, gs.weekly_hours)"
CS_HOURS="FROM class_subjects cs JOIN classes c ON c.id = cs.class_id LEFT JOIN grade_subjects gs ON gs.grade_level = c.grade_level AND gs.subject_id = cs.subject_id"

echo "== Structure (after running the loader twice) =="
check "37 classes this year"                         "37" "$(SQL "SELECT COUNT(*) FROM classes WHERE academic_year_id = $YEAR")"
check "X: 15, XI: 7, XII: 15 (official timetable)"   "10:15 11:7 12:15" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',n) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, COUNT(*) n FROM classes GROUP BY grade_level) x")"
check "X afternoon, XI and XII morning"              "10:2 11:1 12:1" "$(SQL "SELECT GROUP_CONCAT(DISTINCT CONCAT(grade_level,':',shift) ORDER BY grade_level SEPARATOR ' ') FROM classes")"
check "every class has a homeroom teacher"           "0"  "$(SQL "SELECT COUNT(*) FROM classes WHERE homeroom_teacher_id IS NULL")"
check "no teacher is homeroom of two classes"        "0"  "$(SQL "SELECT COUNT(*) FROM (SELECT homeroom_teacher_id FROM classes GROUP BY homeroom_teacher_id HAVING COUNT(*) > 1) d")"
check "80 staff records without credentials"         "80" "$(SQL "SELECT COUNT(*) FROM users WHERE role='teacher' AND password_hash IS NULL")"
check "43 of them without a homeroom class"          "43" "$(SQL "SELECT COUNT(*) FROM users u WHERE role='teacher' AND password_hash IS NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.homeroom_teacher_id = u.id)")"
check "XI-1…XI-7 homeroom teachers (official timetable)" \
      "Dardan Aliu|Ylber Ukshini|Arjete Zejnullahu|Genc Hoxha|Bajram Sejdiu|Gentiana Çerkini|Fitore Ramadani" \
      "$(SQL "SELECT GROUP_CONCAT(CONCAT(u.first_name,' ',u.last_name) ORDER BY c.section SEPARATOR '|') FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.grade_level = 11")"
check "XII-1…XII-5 homeroom teachers"                "1 Enver Bajrami|2 Hysnije Mustafa|3 Dhurata Sahiti|4 Behar Krasniqi|5 Avni Hashani" \
      "$(SQL "SELECT GROUP_CONCAT(CONCAT(c.section,' ',u.first_name,' ',u.last_name) ORDER BY c.section SEPARATOR '|') FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.grade_level = 12 AND c.section <= 5")"
check "XII-11…XII-15 homeroom teachers"              "11 Flakë Musliu|12 Burbuqe Bucaliu|13 Enkelejdë Bytyçi|14 Ardiana Ilazi|15 Armir Aliu" \
      "$(SQL "SELECT GROUP_CONCAT(CONCAT(c.section,' ',u.first_name,' ',u.last_name) ORDER BY c.section SEPARATOR '|') FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.grade_level = 12 AND c.section >= 11")"
check "only the 22 real teachers are public-website-visible" "22" "$(SQL "SELECT COUNT(*) FROM teacher_profiles WHERE show_on_website = 1")"
check "students per class"                           "X-13:3 XI-5:3 XII-1:4" "$(SQL "SELECT GROUP_CONCAT(CONCAT($LABEL,':',n) ORDER BY c.grade_level SEPARATOR ' ') FROM (SELECT class_id, COUNT(*) n FROM enrollments GROUP BY class_id) e JOIN classes c ON c.id = e.class_id")"
check "10 demo students with profiles"               "10" "$(SQL "SELECT COUNT(*) FROM student_profiles WHERE date_of_birth IS NOT NULL")"

echo; echo "== Subjects and teachers =="
check "16 subjects"                                  "16" "$(SQL "SELECT COUNT(*) FROM subjects")"
check "curriculum: X 14, XI 12, XII 12 subjects"     "10:14 11:12 12:12" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',n) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, COUNT(*) n FROM grade_subjects GROUP BY grade_level) x")"
check "grade-specific subjects"                      "10:Art figurativ,Histori,Muzikë 11:Filozofi dhe psikologji 12:Astronomi" \
      "$(SQL "SELECT GROUP_CONCAT(x.v ORDER BY x.grade_level SEPARATOR ' ') FROM (SELECT gs.grade_level, CONCAT(gs.grade_level,':',GROUP_CONCAT(s.name ORDER BY s.name)) v FROM grade_subjects gs JOIN subjects s ON s.id = gs.subject_id WHERE s.sort_order > 11 GROUP BY gs.grade_level) x")"
check "every class has its grade's subjects"         "0"  "$(SQL "SELECT COUNT(*) FROM classes c JOIN grade_subjects gs ON gs.grade_level = c.grade_level LEFT JOIN class_subjects cs ON cs.class_id = c.id AND cs.subject_id = gs.subject_id WHERE cs.id IS NULL")"
check "…and a teacher for every subject"            "0"  "$(SQL "SELECT COUNT(*) FROM class_subjects WHERE teacher_id IS NULL")"
check "every class plans 30 lessons (6 × 5 days)"    "30" "$(SQL "SELECT GROUP_CONCAT(DISTINCT h) FROM (SELECT cs.class_id, SUM($HOURS) h $CS_HOURS GROUP BY cs.class_id) x")"
check "teachers teach only their subjects"           "0"  "$(SQL "SELECT COUNT(*) FROM class_subjects cs LEFT JOIN teacher_subjects ts ON ts.teacher_id = cs.teacher_id AND ts.subject_id = cs.subject_id WHERE ts.teacher_id IS NULL")"
check "no teacher above 25 lessons a week"           "0"  "$(SQL "SELECT COUNT(*) FROM (SELECT cs.teacher_id, SUM($HOURS) h $CS_HOURS GROUP BY cs.teacher_id HAVING h > 25) x")"
check "the test teacher: Matematikë in 3 classes"    "X-13 XI-5 XII-1" "$(SQL "SELECT GROUP_CONCAT($LABEL ORDER BY c.grade_level SEPARATOR ' ') FROM class_subjects cs JOIN classes c ON c.id = cs.class_id JOIN users u ON u.id = cs.teacher_id WHERE u.username = 'prove.mesimdhenes'")"

echo; echo "== Signing in =="
X="$T/x"
check "a staff record without credentials cannot sign in" "422 " "$(login $X enver.bajrami anything-at-all-1)"
S="$T/s"
check "demo student signs in"                         "302 /nxenesi" "$(login $S ariana.gashi Nxenes-Demo-2026)"
check "student dashboard"                             "200" "$(code $S /nxenesi)"
contains "dashboard shows class and homeroom teacher" "$T/body" "Klasa XII-1"
contains "…with the homeroom teacher's name"          "$T/body" "Prof. Enver Bajrami"
check "student cannot open the admin class list"      "403" "$(code $S /admin/klasat)"
U="$T/u"; login $U prove.nxenes Prove-Nxenes-2026 >/dev/null; code $U /nxenesi >/dev/null
contains "a student without a class is told so"        "$T/body" "Ende nuk jeni regjistruar"

echo; echo "== Admin pages =="
A="$T/a"; login $A prove.admin Prove-Admin-2026 >/dev/null
check "Klasat"                                        "200" "$(code $A /admin/klasat)"
contains "…lists XII-4 with Behar Krasniqi"            "$T/body" "Prof. Behar Krasniqi"
contains "…shows the grade sections"                  "$T/body" "Klasa XII"
contains "…shows XI-7"                                "$T/body" ">XI-7<"
check "Mësimdhënësit"                                 "200" "$(code $A /admin/mesimdhenesit)"
contains "…marks staff without credentials"           "$T/body" "Pa fletë hyrjeje"
check "Nxënësit"                                      "200" "$(code $A /admin/nxenesit)"
contains "…lists demo students with their class"      "$T/body" "diellza.morina"
check "admin dashboard"                               "200" "$(code $A /admin)"
contains "…homeroom step complete"                    "$T/body" "37 nga 37 klasa"

echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
