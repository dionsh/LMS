#!/usr/bin/env bash
# Checks the school structure loaded by database/demo/school.php: 45 classes
# (morning: XI-1…7 and XII-1…15 as on the official timetable; afternoon: X-1…15
# and XI-8…15), each with a homeroom teacher and its grade's subjects, the
# school's 72 teachers with their timetable numbers, the real homeroom
# teachers, the curriculum (30 lessons a week per grade), 10 enrolled
# students — and that the admin/student pages show it. Development database only.
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
check "45 classes this year"                         "45" "$(SQL "SELECT COUNT(*) FROM classes WHERE academic_year_id = $YEAR")"
check "15 classes in each grade"                     "10:15 11:15 12:15" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',n) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, COUNT(*) n FROM classes GROUP BY grade_level) x")"
check "morning: XI-1…XI-7 and XII-1…XII-15"          "11:1-7 12:1-15" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',a,'-',b) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, MIN(section) a, MAX(section) b FROM classes WHERE shift = 1 GROUP BY grade_level) x")"
check "afternoon: X-1…X-15 and XI-8…XI-15"           "10:1-15 11:8-15" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',a,'-',b) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, MIN(section) a, MAX(section) b FROM classes WHERE shift = 2 GROUP BY grade_level) x")"
check "every class has a homeroom teacher"           "0"  "$(SQL "SELECT COUNT(*) FROM classes WHERE homeroom_teacher_id IS NULL")"
check "no teacher is homeroom of two classes"        "0"  "$(SQL "SELECT COUNT(*) FROM (SELECT homeroom_teacher_id FROM classes GROUP BY homeroom_teacher_id HAVING COUNT(*) > 1) d")"

echo; echo "== The school's teachers (staff list 2026/2027) =="
check "72 teachers with a timetable number"          "72" "$(SQL "SELECT COUNT(*) FROM teacher_profiles WHERE timetable_number IS NOT NULL")"
check "numbers 1–73, 52 left empty as on the list"   "1 73 0" "$(SQL "SELECT CONCAT(MIN(timetable_number),' ',MAX(timetable_number),' ',SUM(timetable_number = 52)) FROM teacher_profiles")"
check "…e.g. 26 is Enver Bajrami"                    "Enver Bajrami" "$(SQL "SELECT CONCAT(u.first_name,' ',u.last_name) FROM teacher_profiles tp JOIN users u ON u.id=tp.user_id WHERE tp.timetable_number = 26")"
check "…53 is Besarta Ajeti (as on the staff list)"  "Besarta Ajeti" "$(SQL "SELECT CONCAT(u.first_name,' ',u.last_name) FROM teacher_profiles tp JOIN users u ON u.id=tp.user_id WHERE tp.timetable_number = 53")"
check "26 teaches Matematikë and Mësim zgjedhor"     "Mat. Zgjedh." "$(SQL "SELECT GROUP_CONCAT(s.short_name ORDER BY s.sort_order SEPARATOR ' ') FROM teacher_subjects ts JOIN subjects s ON s.id = ts.subject_id JOIN teacher_profiles tp ON tp.user_id = ts.teacher_id WHERE tp.timetable_number = 26")"
check "the school's teachers are on the public site" "72" "$(SQL "SELECT COUNT(*) FROM teacher_profiles WHERE show_on_website = 1")"
check "demo teachers never are, and have no number"  "0" "$(SQL "SELECT COUNT(*) FROM teacher_profiles tp JOIN users u ON u.id=tp.user_id WHERE u.first_name='Demo' AND (tp.show_on_website = 1 OR tp.timetable_number IS NOT NULL)")"
check "no staff record can sign in (except the test account)" "0" "$(SQL "SELECT COUNT(*) FROM users WHERE role='teacher' AND password_hash IS NOT NULL AND username <> 'prove.mesimdhenes'")"
check "everyone's norm is 20 lessons a week"         "20" "$(SQL "SELECT GROUP_CONCAT(DISTINCT weekly_norm) FROM teacher_profiles")"
check "XI-1…XI-7 homeroom teachers (official timetable)" \
      "Dardan Aliu|Ylber Ukshini|Arjete Zejnullahu|Genc Hoxha|Bajram Sejdiu|Gentiana Çerkini|Fitore Ramadani" \
      "$(SQL "SELECT GROUP_CONCAT(CONCAT(u.first_name,' ',u.last_name) ORDER BY c.section SEPARATOR '|') FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.grade_level = 11 AND c.section <= 7")"
check "XII-1…XII-5 homeroom teachers"                "1 Enver Bajrami|2 Hysnije Mustafa|3 Dhurata Sahiti|4 Behar Krasniqi|5 Avni Hashani" \
      "$(SQL "SELECT GROUP_CONCAT(CONCAT(c.section,' ',u.first_name,' ',u.last_name) ORDER BY c.section SEPARATOR '|') FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.grade_level = 12 AND c.section <= 5")"
check "XII-11…XII-15 homeroom teachers"              "11 Flakë Musliu|12 Burbuqe Bucaliu|13 Enkelejdë Bytyçi|14 Ardiana Ilazi|15 Armir Aliu" \
      "$(SQL "SELECT GROUP_CONCAT(CONCAT(c.section,' ',u.first_name,' ',u.last_name) ORDER BY c.section SEPARATOR '|') FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.grade_level = 12 AND c.section >= 11")"
check "afternoon homerooms are demo teachers (not known yet)" "23" "$(SQL "SELECT COUNT(*) FROM classes c JOIN users u ON u.id = c.homeroom_teacher_id WHERE c.shift = 2 AND u.first_name = 'Demo'")"
check "students per class"                           "X-13:3 XI-5:3 XII-1:4" "$(SQL "SELECT GROUP_CONCAT(CONCAT($LABEL,':',n) ORDER BY c.grade_level SEPARATOR ' ') FROM (SELECT class_id, COUNT(*) n FROM enrollments GROUP BY class_id) e JOIN classes c ON c.id = e.class_id")"
check "10 demo students with profiles"               "10" "$(SQL "SELECT COUNT(*) FROM student_profiles WHERE date_of_birth IS NOT NULL")"

echo; echo "== Subjects and teachers =="
check "17 subjects"                                  "17" "$(SQL "SELECT COUNT(*) FROM subjects")"
check "curriculum: X 14, XI 13, XII 12 subjects"     "10:14 11:13 12:12" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',n) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, COUNT(*) n FROM grade_subjects GROUP BY grade_level) x")"
check "every grade: 30 lessons a week (6 × 5 days)"  "10:30 11:30 12:30" "$(SQL "SELECT GROUP_CONCAT(CONCAT(grade_level,':',h) ORDER BY grade_level SEPARATOR ' ') FROM (SELECT grade_level, SUM(weekly_hours) h FROM grade_subjects GROUP BY grade_level) x")"
check "grade-specific subjects"                      "10:Art figurativ,Histori,Muzikë 11:Filozofi,Psikologji 12:Astronomi" \
      "$(SQL "SELECT GROUP_CONCAT(x.v ORDER BY x.grade_level SEPARATOR ' ') FROM (SELECT gs.grade_level, CONCAT(gs.grade_level,':',GROUP_CONCAT(s.name ORDER BY s.name)) v FROM grade_subjects gs JOIN subjects s ON s.id = gs.subject_id WHERE s.sort_order > 11 GROUP BY gs.grade_level) x")"
check "hours the school gave, e.g. XII: Shqip 4, Gjerm 1, Astronomi 2" "4 1 2" "$(SQL "SELECT CONCAT((SELECT weekly_hours FROM grade_subjects gs JOIN subjects s ON s.id=gs.subject_id WHERE gs.grade_level=12 AND s.short_name='Shqip'),' ',(SELECT weekly_hours FROM grade_subjects gs JOIN subjects s ON s.id=gs.subject_id WHERE gs.grade_level=12 AND s.short_name='Gjerm.'),' ',(SELECT weekly_hours FROM grade_subjects gs JOIN subjects s ON s.id=gs.subject_id WHERE gs.grade_level=12 AND s.short_name='Astr.'))")"
check "…XI: TIK 1, X: Art figurativ 1"               "1 1" "$(SQL "SELECT CONCAT((SELECT weekly_hours FROM grade_subjects gs JOIN subjects s ON s.id=gs.subject_id WHERE gs.grade_level=11 AND s.short_name='TIK'),' ',(SELECT weekly_hours FROM grade_subjects gs JOIN subjects s ON s.id=gs.subject_id WHERE gs.grade_level=10 AND s.short_name='Art'))")"
check "every class has its grade's subjects"         "0"  "$(SQL "SELECT COUNT(*) FROM classes c JOIN grade_subjects gs ON gs.grade_level = c.grade_level LEFT JOIN class_subjects cs ON cs.class_id = c.id AND cs.subject_id = gs.subject_id WHERE cs.id IS NULL")"
check "…and a teacher for every subject"            "0"  "$(SQL "SELECT COUNT(*) FROM class_subjects WHERE teacher_id IS NULL")"
check "every class plans 30 lessons (6 × 5 days)"    "30" "$(SQL "SELECT GROUP_CONCAT(DISTINCT h) FROM (SELECT cs.class_id, SUM($HOURS) h $CS_HOURS GROUP BY cs.class_id) x")"
check "teachers teach only their subjects"           "0"  "$(SQL "SELECT COUNT(*) FROM class_subjects cs LEFT JOIN teacher_subjects ts ON ts.teacher_id = cs.teacher_id AND ts.subject_id = cs.subject_id WHERE ts.teacher_id IS NULL")"
check "no teacher above the 20-lesson norm"          "0"  "$(SQL "SELECT COUNT(*) FROM (SELECT cs.teacher_id, SUM($HOURS) h $CS_HOURS GROUP BY cs.teacher_id HAVING h > 20) x")"
check "the test teacher: Matematikë in 3 classes"    "X-13 XI-5 XII-1" "$(SQL "SELECT GROUP_CONCAT($LABEL ORDER BY c.grade_level SEPARATOR ' ') FROM class_subjects cs JOIN classes c ON c.id = cs.class_id JOIN users u ON u.id = cs.teacher_id WHERE u.username = 'prove.mesimdhenes'")"

echo; echo "== School details (letterhead) =="
check "principal"                                     "Bajram Rexhepi" "$(SQL "SELECT setting_value FROM settings WHERE setting_key = 'principal_name'")"
check "phone and e-mail"                              "+383 49 923 828 imersion7@gmail.com" "$(SQL "SELECT CONCAT((SELECT setting_value FROM settings WHERE setting_key = 'school_phone'),' ',(SELECT setting_value FROM settings WHERE setting_key = 'school_email'))")"
check "the public home page"                          "200" "$(code "$T/guest" /)"
contains "…address in the footer"                     "$T/body" "Rruga “Fatmir Hasani” nr. 14, 70000 Ferizaj"
contains "…phone to call"                             "$T/body" 'href="tel:+38349923828"'
contains "…e-mail to write to"                        "$T/body" 'href="mailto:imersion7@gmail.com"'

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
contains "…homeroom step complete"                    "$T/body" "45 nga 45 klasa"

echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
