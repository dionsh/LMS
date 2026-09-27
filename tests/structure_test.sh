#!/usr/bin/env bash
# End-to-end tests for the academic structure (T06): the curriculum per grade,
# subjects, classes and who teaches what in them, rooms, teachers' subjects
# and timetable numbers, school years. Development database only; everything
# it changes is restored at the end.
#
#   bash tests/structure_test.sh
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1"; }
YEAR="(SELECT id FROM academic_years WHERE is_current = 1)"

restore() {
  SQL "UPDATE academic_years SET is_current = (name = '2026/2027');
       DELETE FROM academic_years WHERE name LIKE '20__/20__' AND name <> '2026/2027';
       DELETE FROM classes WHERE grade_level = 11 AND section >= 16;
       DELETE FROM subjects WHERE name LIKE 'Test%';
       DELETE FROM rooms WHERE name LIKE 'Test%';
       DELETE FROM grade_levels WHERE level = 13;
       DELETE gs FROM grade_subjects gs JOIN subjects s ON s.id = gs.subject_id WHERE gs.grade_level = 12 AND s.name = 'Filozofi';
       UPDATE grade_levels SET shift = IF(level = 10, 2, 1);
       INSERT IGNORE INTO grade_subjects (grade_level, subject_id) SELECT g.level, s.id FROM grade_levels g JOIN subjects s ON s.sort_order BETWEEN 1 AND 11;
       UPDATE teacher_profiles SET timetable_number = NULL WHERE timetable_number >= 90;
       UPDATE teacher_profiles SET weekly_norm = 20;
       DELETE ts FROM teacher_subjects ts JOIN users u ON u.id = ts.teacher_id WHERE u.username IN ('prove.mesimdhenes', 'demo.gjuheshqipe1');
       INSERT INTO teacher_subjects (teacher_id, subject_id) SELECT u.id, s.id FROM users u JOIN subjects s ON s.sort_order = 1 WHERE u.username = 'demo.gjuheshqipe1';"
  "$PHP" database/demo/test-accounts.php > /dev/null
  "$PHP" database/demo/school.php > /dev/null
}
restore

T=$(mktemp -d); pass=0; fail=0
check()    { if [ "$2" = "$3" ]; then pass=$((pass+1)); printf "PASS  %-62s %s\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-62s got '%s' expected '%s'\n" "$1" "$3" "$2"; fi; }
contains() { if grep -q "$3" "$2"; then pass=$((pass+1)); printf "PASS  %-62s contains \"%s\"\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-62s missing \"%s\"\n" "$1" "$3"; fi; }
token()  { curl -s -b "$1" -c "$1" "$B$2" | grep -oE 'name="_token" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)"/\1/'; }
go()     { curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" "$B$2" | sed "s#$B##"; }
code()   { curl -s -o "$T/body" -w "%{http_code}" -b "$1" -c "$1" "$B$2"; }
login()  { local t; t=$(token "$1" /hyr); curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" \
             --data-urlencode "_token=$t" --data-urlencode "login=$2" --data-urlencode "password=$3" "$B/hyr" | sed "s#$B##"; }
post()   { local jar=$1 path=$2 page=$3; shift 3; local t; t=$(token "$jar" "$page")
           curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$jar" -c "$jar" --data-urlencode "_token=$t" "$@" "$B$path" | sed "s#$B##"; }
flash()  { code "$1" "$2" > /dev/null; }   # open a page so its flash message lands in $T/body

A="$T/admin"; login $A prove.admin Prove-Admin-2026 >/dev/null
LOG_START=$(SQL "SELECT COALESCE(MAX(id), 0) FROM activity_log")
XII1=$(SQL "SELECT id FROM classes WHERE grade_level=12 AND section=1 AND academic_year_id=$YEAR")
XI7=$(SQL "SELECT id FROM classes WHERE grade_level=11 AND section=7 AND academic_year_id=$YEAR")
X1=$(SQL "SELECT id FROM classes WHERE grade_level=10 AND section=1 AND academic_year_id=$YEAR")
MAT=$(SQL "SELECT id FROM subjects WHERE name LIKE 'Matematik%'")
FP=$(SQL "SELECT id FROM subjects WHERE name LIKE 'Filozofi%'")
ASTR=$(SQL "SELECT id FROM subjects WHERE name = 'Astronomi'")
ENVER=$(SQL "SELECT id FROM users WHERE username='enver.bajrami'")
TEST_T=$(SQL "SELECT id FROM users WHERE username='prove.mesimdhenes'")
DEMO_T=$(SQL "SELECT id FROM users WHERE username='demo.gjuheshqipe1'")
STUDENT=$(SQL "SELECT id FROM users WHERE username='ariana.gashi'")

echo "== Who may change the structure =="
S="$T/student"; login $S prove.nxenes Prove-Nxenes-2026 >/dev/null
M="$T/teacher"; login $M prove.mesimdhenes Prove-Mesimdhenes-2026 >/dev/null
check "student → /admin/klasat/shto is forbidden"           "403" "$(code $S /admin/klasat/shto)"
check "teacher → /admin/plani-mesimor is forbidden"         "403" "$(code $M /admin/plani-mesimor)"
check "teacher cannot add a subject"                        "403 " "$(post $M /admin/lendet/shto /mesimdhenesi --data-urlencode name=Test)"
check "guest → sign-in page"                                "302 /hyr" "$(go $T/guest /admin/lendet)"
check "adding a class without CSRF token is refused"        "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $A --data 'grade_level=11&section=16&shift=1' $B/admin/klasat/shto)"

echo; echo "== Curriculum (plani mësimor) =="
check "curriculum page"                                     "200" "$(code $A /admin/plani-mesimor)"
contains "…shows grade XII"                                 "$T/body" "Klasa XII"
contains "…with Astronomi"                                  "$T/body" "Astronomi"
contains "…and the week adding up to the timetable"         "$T/body" "30 orë në javë · sa vendet e orarit"
check "unknown grade → 404"                                 "404" "$(code $A /admin/plani-mesimor/9)"
check "grade XII page"                                      "200" "$(code $A /admin/plani-mesimor/12)"
XII_FORM=()
while read -r sid hours; do XII_FORM+=(--data-urlencode "subjects[]=$sid" --data-urlencode "hours[$sid]=$hours"); done \
  < <(SQL "SELECT subject_id, COALESCE(weekly_hours,'') FROM grade_subjects WHERE grade_level = 12")
check "13 hours a week → 422"                              "422 " "$(post $A /admin/plani-mesimor/12 /admin/plani-mesimor/12 --data-urlencode shift=1 "${XII_FORM[@]}" --data-urlencode "subjects[]=$FP" --data-urlencode "hours[$FP]=13")"
contains "…explains the range"                              "$T/body" "numër nga 1 deri në 12"
check "add Filozofi to XII (1 hour)            "         "302 /admin/plani-mesimor" "$(post $A /admin/plani-mesimor/12 /admin/plani-mesimor/12 --data-urlencode shift=1 "${XII_FORM[@]}" --data-urlencode "subjects[]=$FP" --data-urlencode "hours[$FP]=1")"
check "…all 15 XII classes got the subject"                 "15" "$(SQL "SELECT COUNT(*) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id WHERE c.grade_level=12 AND cs.subject_id=$FP")"
flash $A /admin/plani-mesimor
contains "…and the admin is told"                           "$T/body" "15 lëndë iu shtuan paraleleve"
check "take it out of XII again"                            "302 /admin/plani-mesimor" "$(post $A /admin/plani-mesimor/12 /admin/plani-mesimor/12 --data-urlencode shift=1 "${XII_FORM[@]}")"
check "…it leaves the classes (no teacher yet)"             "0" "$(SQL "SELECT COUNT(*) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id WHERE c.grade_level=12 AND cs.subject_id=$FP")"
check "a subject in use stays in its class"                 "12" "$(post $A /admin/plani-mesimor/12 /admin/plani-mesimor/12 --data-urlencode shift=1 $(SQL "SELECT CONCAT('--data-urlencode subjects[]=', subject_id) FROM grade_subjects WHERE grade_level=12 AND subject_id <> $MAT") >/dev/null; SQL "SELECT COUNT(*) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id WHERE c.grade_level=12 AND c.section=1")"
post $A /admin/plani-mesimor/12 /admin/plani-mesimor/12 --data-urlencode shift=1 "${XII_FORM[@]}" >/dev/null
check "…and Matematikë is back in the plan"                "4" "$(SQL "SELECT weekly_hours FROM grade_subjects WHERE grade_level=12 AND subject_id=$MAT")"
check "add grade XIII"                                      "302 /admin/plani-mesimor/13" "$(post $A /admin/plani-mesimor/shto /admin/plani-mesimor --data-urlencode level=13 --data-urlencode shift=1)"
check "grade XII again → 422"                               "422 " "$(post $A /admin/plani-mesimor/shto /admin/plani-mesimor --data-urlencode level=12 --data-urlencode shift=1)"
contains "…already in the plan"                             "$T/body" "Klasa XII është tashmë në planin mësimor."
check "remove grade XIII (no classes)"                      "302 /admin/plani-mesimor" "$(post $A /admin/plani-mesimor/13/fshij /admin/plani-mesimor/13)"
check "grade XII cannot be removed (has classes)"           "302 /admin/plani-mesimor/12" "$(post $A /admin/plani-mesimor/12/fshij /admin/plani-mesimor/12)"
check "…still there"                                        "1" "$(SQL "SELECT COUNT(*) FROM grade_levels WHERE level=12")"

echo; echo "== Subjects =="
check "subjects page"                                       "200" "$(code $A /admin/lendet)"
contains "…lists Psikologji"                                "$T/body" "Psikologji"
check "empty name → 422"                                    "422 " "$(post $A /admin/lendet/shto /admin/lendet/shto --data-urlencode name=)"
contains "…asks for the name"                               "$T/body" "Shkruani emrin e lëndës."
check "duplicate subject → 422"                             "422 " "$(post $A /admin/lendet/shto /admin/lendet/shto --data-urlencode name=Kimi)"
contains "…says it exists"                                  "$T/body" "Kjo lëndë ekziston tashmë."
check "add Test Informatikë for XI, 2 hours"                "302 /admin/lendet" "$(post $A /admin/lendet/shto /admin/lendet/shto --data "name=Test%20Informatik%C3%AB" --data-urlencode "grades[]=11" --data-urlencode "hours[11]=2" --data-urlencode is_active=1)"
TEST_SUBJECT=$(SQL "SELECT id FROM subjects WHERE name LIKE 'Test Informatik%'")
check "…short name taken from the name"                     "Test Informa" "$(SQL "SELECT short_name FROM subjects WHERE id=$TEST_SUBJECT")"
check "…every XI class got it"                              "15" "$(SQL "SELECT COUNT(*) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id WHERE cs.subject_id=$TEST_SUBJECT")"
check "move it to XII only"                                 "302 /admin/lendet/$TEST_SUBJECT/ndrysho" "$(post $A /admin/lendet/$TEST_SUBJECT/ndrysho /admin/lendet/$TEST_SUBJECT/ndrysho --data "name=Test%20Informatik%C3%AB" --data-urlencode "grades[]=12" --data-urlencode "hours[12]=1" --data-urlencode is_active=1)"
check "…now in the 15 XII classes, none of XI"              "12:15" "$(SQL "SELECT GROUP_CONCAT(CONCAT(c.grade_level,':',n)) FROM (SELECT c.grade_level, COUNT(*) n FROM class_subjects cs JOIN classes c ON c.id=cs.class_id WHERE cs.subject_id=$TEST_SUBJECT GROUP BY c.grade_level) c")"
check "a taught subject cannot be deleted"                  "302 /admin/lendet/$MAT/ndrysho" "$(post $A /admin/lendet/$MAT/fshij /admin/lendet/$MAT/ndrysho)"
flash $A /admin/lendet/$MAT/ndrysho
contains "…the admin is told to deactivate it instead"      "$T/body" "Mund ta bëni joaktive."
post $A /admin/lendet/$TEST_SUBJECT/ndrysho /admin/lendet/$TEST_SUBJECT/ndrysho --data "name=Test%20Informatik%C3%AB" --data-urlencode is_active=1 >/dev/null
check "an untaught subject can be deleted"                  "302 /admin/lendet" "$(post $A /admin/lendet/$TEST_SUBJECT/fshij /admin/lendet/$TEST_SUBJECT/ndrysho)"
check "…gone"                                               "0" "$(SQL "SELECT COUNT(*) FROM subjects WHERE id=$TEST_SUBJECT")"

echo; echo "== Classes =="
check "add-class page for XI"                               "200" "$(code $A "/admin/klasat/shto?niveli=11")"
contains "…suggests the next free section (16)"             "$T/body" 'name="section" value="16"'
check "XI-3 exists → 422"                                   "422 " "$(post $A /admin/klasat/shto /admin/klasat/shto --data-urlencode grade_level=11 --data-urlencode section=3 --data-urlencode shift=1)"
contains "…says so"                                         "$T/body" "Klasa XI-3 ekziston tashmë"
check "a grade that does not exist → 422"                   "422 " "$(post $A /admin/klasat/shto /admin/klasat/shto --data-urlencode grade_level=9 --data-urlencode section=1 --data-urlencode shift=1)"
check "Enver Bajrami is already homeroom of XII-1 → 422"    "422 " "$(post $A /admin/klasat/shto /admin/klasat/shto --data-urlencode grade_level=11 --data-urlencode section=16 --data-urlencode shift=1 --data-urlencode homeroom_teacher_id=$ENVER)"
contains "…says of which class"                             "$T/body" "tashmë kujdestar i klasës XII-1"
check "a student as homeroom teacher → 422"                 "422 " "$(post $A /admin/klasat/shto /admin/klasat/shto --data-urlencode grade_level=11 --data-urlencode section=16 --data-urlencode shift=1 --data-urlencode homeroom_teacher_id=$STUDENT)"
r=$(post $A /admin/klasat/shto /admin/klasat/shto --data-urlencode grade_level=11 --data-urlencode section=16 --data-urlencode shift=1 --data-urlencode homeroom_teacher_id=$TEST_T --data "stream=Shkenca%20natyrore")
XI16=$(SQL "SELECT id FROM classes WHERE grade_level=11 AND section=16 AND academic_year_id=$YEAR")
check "add XI-16 → its page"                                 "302 /admin/klasat/$XI16" "$r"
check "…with XI's 13 subjects from the curriculum"          "13" "$(SQL "SELECT COUNT(*) FROM class_subjects WHERE class_id=$XI16")"
check "class page"                                          "200" "$(code $A /admin/klasat/$XI16)"
contains "…titled XI-16"                                     "$T/body" "Klasa XI-16"
contains "…with the homeroom teacher"                       "$T/body" "Kujdestari: Provë Mësimdhënëse"
contains "…offering Psikologji"                             "$T/body" "Psikologji"
check "unknown class → 404"                                 "404" "$(code $A /admin/klasat/999999)"
CS_MAT=$(SQL "SELECT id FROM class_subjects WHERE class_id=$XI16 AND subject_id=$MAT")
CS_FP=$(SQL "SELECT id FROM class_subjects WHERE class_id=$XI16 AND subject_id=$FP")
check "a student as a subject's teacher → 422"             "422 " "$(post $A /admin/klasat/$XI16/lendet /admin/klasat/$XI16 --data-urlencode "teacher[$CS_MAT]=$STUDENT")"
contains "…asks for a teacher from the list"                "$T/body" "Zgjidhni mësimdhënësin nga lista."
check "99 hours → 422"                                      "422 " "$(post $A /admin/klasat/$XI16/lendet /admin/klasat/$XI16 --data-urlencode "hours[$CS_MAT]=99")"
check "assign Matematikë (5 h) and Filozofi"                "302 /admin/klasat/$XI16" "$(post $A /admin/klasat/$XI16/lendet /admin/klasat/$XI16 --data-urlencode "teacher[$CS_MAT]=$TEST_T" --data-urlencode "hours[$CS_MAT]=5" --data-urlencode "teacher[$CS_FP]=$DEMO_T")"
check "…saved: teacher and the class's own hours"          "$TEST_T 5" "$(SQL "SELECT CONCAT(teacher_id,' ',weekly_hours) FROM class_subjects WHERE id=$CS_MAT")"
check "…hours left empty follow the curriculum"             "$DEMO_T NULL 2" "$(SQL "SELECT CONCAT(cs.teacher_id,' ',COALESCE(cs.weekly_hours,'NULL'),' ',gs.weekly_hours) FROM class_subjects cs JOIN grade_subjects gs ON gs.subject_id=cs.subject_id AND gs.grade_level=11 WHERE cs.id=$CS_FP")"
code $A /admin/perdoruesit/$TEST_T/ndrysho >/dev/null
contains "the teacher's page lists what they teach"         "$T/body" "XI-16 · Matematikë"
check "add Astronomi (outside XI's plan)"                  "302 /admin/klasat/$XI16" "$(post $A /admin/klasat/$XI16/lendet/shto /admin/klasat/$XI16 --data-urlencode subject_id=$ASTR)"
code $A /admin/klasat/$XI16 >/dev/null
contains "…marked as outside the curriculum"                "$T/body" "Jashtë planit mësimor"
check "…adding it twice is refused"                         "14" "$(post $A /admin/klasat/$XI16/lendet/shto /admin/klasat/$XI16 --data-urlencode subject_id=$ASTR >/dev/null; SQL "SELECT COUNT(*) FROM class_subjects WHERE class_id=$XI16")"
CS_ASTR=$(SQL "SELECT id FROM class_subjects WHERE class_id=$XI16 AND subject_id=$ASTR")
check "a curriculum subject cannot be removed by hand"      "14" "$(post $A /admin/klasat/$XI16/lendet/hiq /admin/klasat/$XI16 --data-urlencode class_subject_id=$CS_MAT >/dev/null; SQL "SELECT COUNT(*) FROM class_subjects WHERE class_id=$XI16")"
check "remove Astronomi"                                    "302 /admin/klasat/$XI16" "$(post $A /admin/klasat/$XI16/lendet/hiq /admin/klasat/$XI16 --data-urlencode class_subject_id=$CS_ASTR)"
check "…gone"                                               "13" "$(SQL "SELECT COUNT(*) FROM class_subjects WHERE class_id=$XI16")"

echo; echo "== Electives (lëndë zgjedhore) =="
MZ=$(SQL "SELECT id FROM subjects WHERE short_name = 'Zgjedh.'")
CAREER=$(SQL "SELECT id FROM subjects WHERE short_name LIKE 'Karrier%'")
# The class's teachers and hours as they are, so a save changes only what a check is about
keep()   { SQL "SELECT CONCAT('teacher[',id,']=',COALESCE(teacher_id,''),'&hours[',id,']=',COALESCE(weekly_hours,'')) FROM class_subjects WHERE class_id = $1" | tr -d '\r' | paste -sd'&' -; }
planned(){ SQL "SELECT SUM(COALESCE(cs.weekly_hours, gs.weekly_hours)) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id JOIN subjects s ON s.id=cs.subject_id LEFT JOIN grade_subjects gs ON gs.grade_level=c.grade_level AND gs.subject_id=COALESCE(s.fills_subject_id, cs.subject_id) WHERE cs.class_id = $1"; }
code $A /admin/lendet >/dev/null
contains "the subject list marks Orientim në karrierë"      "$T/body" "Zgjedhore, në vend të Mësim zgjedhor"
check "an elective with grades of its own → 422"           "422 " "$(post $A /admin/lendet/shto /admin/lendet/shto --data-urlencode "name=Test Programim" --data-urlencode fills_subject_id=$MZ --data-urlencode "grades[]=11" --data-urlencode "hours[11]=2" --data-urlencode is_active=1)"
contains "…says why"                                        "$T/body" "Lënda zgjedhore merr orët e lëndës që zëvendëson"
check "an elective in place of an elective → 422"          "422 " "$(post $A /admin/lendet/shto /admin/lendet/shto --data-urlencode "name=Test Programim" --data-urlencode fills_subject_id=$CAREER --data-urlencode is_active=1)"
check "add Test Programim in place of Mësim zgjedhor"       "302 /admin/lendet" "$(post $A /admin/lendet/shto /admin/lendet/shto --data-urlencode "name=Test Programim" --data-urlencode fills_subject_id=$MZ --data-urlencode is_active=1)"
PROG=$(SQL "SELECT id FROM subjects WHERE name = 'Test Programim'")
check "…no grade takes it by itself"                        "0" "$(SQL "SELECT COUNT(*) FROM grade_subjects WHERE subject_id = $PROG")"
check "Mësim zgjedhor cannot become an elective itself"     "422 " "$(post $A /admin/lendet/$MZ/ndrysho /admin/lendet/$MZ/ndrysho --data "name=M%C3%ABsim%20zgjedhor" --data-urlencode fills_subject_id=$MAT --data-urlencode is_active=1)"
contains "…it has electives"                                "$T/body" "Kjo lëndë ka vetë lëndë zgjedhore"
CS_MZ=$(SQL "SELECT id FROM class_subjects WHERE class_id=$XI16 AND subject_id=$MZ")
code $A /admin/klasat/$XI16 >/dev/null
contains "the class page lets XI-16 choose"                 "$T/body" "name=\"subject\[$CS_MZ\]\""
contains "…Orientim në karrierë or Test Programim"          "$T/body" "Orientim në karrierë</option>"
BEFORE_H=$(planned $XI16)
check "Matematikë in place of Mësim zgjedhor → 422"         "422 " "$(post $A /admin/klasat/$XI16/lendet /admin/klasat/$XI16 --data "$(keep $XI16)" --data-urlencode "subject[$CS_MZ]=$MAT")"
check "XI-16 takes Test Programim"                          "302 /admin/klasat/$XI16" "$(post $A /admin/klasat/$XI16/lendet /admin/klasat/$XI16 --data "$(keep $XI16)" --data-urlencode "subject[$CS_MZ]=$PROG")"
check "…in the same place, with the 2 hours of the plan"    "$PROG 2" "$(SQL "SELECT CONCAT(cs.subject_id,' ',gs.weekly_hours) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id JOIN subjects s ON s.id=cs.subject_id JOIN grade_subjects gs ON gs.grade_level=c.grade_level AND gs.subject_id=COALESCE(s.fills_subject_id, cs.subject_id) WHERE cs.id=$CS_MZ")"
check "…the class plans as many lessons as before"          "$BEFORE_H" "$(planned $XI16)"
XI_FORM=()
while read -r sid hours; do XI_FORM+=(--data-urlencode "subjects[]=$sid" --data-urlencode "hours[$sid]=$hours"); done \
  < <(SQL "SELECT subject_id, COALESCE(weekly_hours,'') FROM grade_subjects WHERE grade_level = 11" | tr -d '\r')
post $A /admin/plani-mesimor/11 /admin/plani-mesimor/11 --data-urlencode shift=1 "${XI_FORM[@]}" >/dev/null
check "saving XI's curriculum does not add Mësim zgjedhor back" "0" "$(SQL "SELECT COUNT(*) FROM class_subjects WHERE class_id=$XI16 AND subject_id=$MZ")"
check "…and XI-16 keeps 13 subjects"                         "13" "$(SQL "SELECT COUNT(*) FROM class_subjects WHERE class_id=$XI16")"
check "a taught elective cannot be deleted"                  "1" "$(post $A /admin/lendet/$PROG/fshij /admin/lendet/$PROG/ndrysho >/dev/null; SQL "SELECT COUNT(*) FROM subjects WHERE id=$PROG")"
check "back to Mësim zgjedhor"                               "302 /admin/klasat/$XI16" "$(post $A /admin/klasat/$XI16/lendet /admin/klasat/$XI16 --data "$(keep $XI16)" --data-urlencode "subject[$CS_MZ]=$MZ")"
check "…the same row again"                                  "$MZ" "$(SQL "SELECT subject_id FROM class_subjects WHERE id=$CS_MZ")"
check "an elective nobody takes can be deleted"              "302 /admin/lendet" "$(post $A /admin/lendet/$PROG/fshij /admin/lendet/$PROG/ndrysho)"

echo; echo "== Rooms: the class's own room =="
check "rooms page"                                          "200" "$(code $A /admin/sallat)"
check "add Test Salla 12"                                   "302 /admin/sallat" "$(post $A /admin/sallat/shto /admin/sallat --data-urlencode "name=Test Salla 12" --data-urlencode capacity=32)"
check "…twice → 422"                                        "422 " "$(post $A /admin/sallat/shto /admin/sallat --data-urlencode "name=Test Salla 12")"
check "capacity 0 → 422"                                    "422 " "$(post $A /admin/sallat/shto /admin/sallat --data-urlencode "name=Test Palestra" --data-urlencode capacity=0)"
ROOM=$(SQL "SELECT id FROM rooms WHERE name='Test Salla 12'")
check "XI-16 gets the room"                                  "302 /admin/klasat/$XI16" "$(post $A /admin/klasat/$XI16/ndrysho /admin/klasat/$XI16/ndrysho --data-urlencode section=16 --data-urlencode shift=1 --data-urlencode homeroom_teacher_id=$TEST_T --data-urlencode home_room_id=$ROOM)"
check "another morning class in the same room → 422"        "422 " "$(post $A /admin/klasat/$XI7/ndrysho /admin/klasat/$XI7/ndrysho --data-urlencode section=7 --data-urlencode shift=1 --data-urlencode home_room_id=$ROOM)"
contains "…the room belongs to XI-16 in that shift"          "$T/body" "Kjo sallë është e klasës XI-16 në të njëjtin ndërrim."
X1_HOMEROOM=$(SQL "SELECT homeroom_teacher_id FROM classes WHERE id=$X1")
check "an afternoon class may share it"                     "302 /admin/klasat/$X1" "$(post $A /admin/klasat/$X1/ndrysho /admin/klasat/$X1/ndrysho --data-urlencode section=1 --data-urlencode shift=2 --data-urlencode homeroom_teacher_id=$X1_HOMEROOM --data-urlencode home_room_id=$ROOM)"
code $A /admin/sallat >/dev/null
contains "rooms list shows both classes"                    "$T/body" "X-1, XI-16"
check "a room in use cannot be deleted"                     "302 /admin/sallat/$ROOM/ndrysho" "$(post $A /admin/sallat/$ROOM/fshij /admin/sallat/$ROOM/ndrysho)"
post $A /admin/klasat/$X1/ndrysho /admin/klasat/$X1/ndrysho --data-urlencode section=1 --data-urlencode shift=2 --data-urlencode homeroom_teacher_id=$X1_HOMEROOM >/dev/null

echo; echo "== Deleting classes =="
check "a class with students cannot be deleted"             "302 /admin/klasat/$XII1/ndrysho" "$(post $A /admin/klasat/$XII1/fshij /admin/klasat/$XII1/ndrysho)"
flash $A /admin/klasat/$XII1/ndrysho
contains "…the admin is told why"                           "$T/body" "Klasa XII-1 ka nxënës."
check "delete XI-16 (empty)"                                 "302 /admin/klasat" "$(post $A /admin/klasat/$XI16/fshij /admin/klasat/$XI16/ndrysho)"
check "…its subjects went with it"                          "0" "$(SQL "SELECT COUNT(*) FROM class_subjects WHERE class_id=$XI16")"

echo; echo "== Teachers: subjects and timetable numbers =="
check "teacher: number 99 and two subjects"                 "302 /admin/perdoruesit/$DEMO_T/ndrysho" "$(post $A /admin/perdoruesit/$DEMO_T/ndrysho /admin/perdoruesit/$DEMO_T/ndrysho --data-urlencode first_name=Demo --data "last_name=Gjuh%C3%AB%20shqipe%201" --data-urlencode weekly_norm=20 --data-urlencode timetable_number=99 --data-urlencode "subject_ids[]=1" --data-urlencode "subject_ids[]=$FP")"
check "…saved"                                              "99 2" "$(SQL "SELECT CONCAT(tp.timetable_number,' ',(SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id=$DEMO_T)) FROM teacher_profiles tp WHERE tp.user_id=$DEMO_T")"
check "number 99 for another teacher → 422"                 "422 " "$(post $A /admin/perdoruesit/$ENVER/ndrysho /admin/perdoruesit/$ENVER/ndrysho --data-urlencode first_name=Enver --data-urlencode last_name=Bajrami --data-urlencode weekly_norm=20 --data-urlencode timetable_number=99)"
contains "…says whose it is"                                "$T/body" "Ky numër në orar i përket një mësimdhënësi tjetër."
check "number 1000 → 422"                                   "422 " "$(post $A /admin/perdoruesit/$ENVER/ndrysho /admin/perdoruesit/$ENVER/ndrysho --data-urlencode first_name=Enver --data-urlencode last_name=Bajrami --data-urlencode weekly_norm=20 --data-urlencode timetable_number=1000)"
code $A /admin/klasat/$XII1 >/dev/null
contains "class page offers the subject's teachers first"   "$T/body" 'optgroup label="Japin Matematikë"'
code $A /admin/mesimdhenesit >/dev/null
check "a norm of 0 lessons → 422"                            "422 " "$(post $A /admin/perdoruesit/$DEMO_T/ndrysho /admin/perdoruesit/$DEMO_T/ndrysho --data-urlencode first_name=Demo --data "last_name=Gjuh%C3%AB%20shqipe%201" --data-urlencode weekly_norm=0 --data-urlencode timetable_number=99)"
contains "…explains the norm"                               "$T/body" "nga 1 deri në 40"
DLOAD=$(SQL "SELECT SUM(COALESCE(cs.weekly_hours, gs.weekly_hours)) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id LEFT JOIN grade_subjects gs ON gs.grade_level=c.grade_level AND gs.subject_id=cs.subject_id WHERE cs.teacher_id=$DEMO_T AND c.academic_year_id=$YEAR")
check "half norm (10 lessons) for a teacher with $DLOAD"           "302 /admin/perdoruesit/$DEMO_T/ndrysho" "$(post $A /admin/perdoruesit/$DEMO_T/ndrysho /admin/perdoruesit/$DEMO_T/ndrysho --data-urlencode first_name=Demo --data "last_name=Gjuh%C3%AB%20shqipe%201" --data-urlencode weekly_norm=10 --data-urlencode timetable_number=99 --data-urlencode "subject_ids[]=1" --data-urlencode "subject_ids[]=$FP")"
code $A /admin/perdoruesit/$DEMO_T/ndrysho >/dev/null
contains "…the teacher's page shows load against norm"      "$T/body" "$DLOAD nga 10 orë në javë"
contains "teacher list shows the subjects"                  "$(code $A '/admin/mesimdhenesit?q=demo.gjuheshqipe1' >/dev/null; echo $T/body)" "Shqip, Filoz."
contains "…and flags the teacher above their norm"           "$T/body" "$DLOAD / 10 · mbi normë"

echo; echo "== School years =="
check "years page"                                          "200" "$(code $A /admin/vitet-shkollore)"
contains "…marks the current year"                          "$T/body" "Aktual"
contains "…suggests the next year"                          "$T/body" 'value="2027/2028"'
check "badly written year → 422"                            "422 " "$(post $A /admin/vitet-shkollore/shto /admin/vitet-shkollore --data-urlencode name=2027 --data-urlencode starts_on=2027-09-01 --data-urlencode ends_on=2028-06-30 --data-urlencode term1_starts_on=2027-09-01 --data-urlencode term1_ends_on=2028-01-17 --data-urlencode term2_starts_on=2028-01-18 --data-urlencode term2_ends_on=2028-06-30)"
check "semesters overlapping → 422"                         "422 " "$(post $A /admin/vitet-shkollore/shto /admin/vitet-shkollore --data-urlencode name=2027/2028 --data-urlencode starts_on=2027-09-01 --data-urlencode ends_on=2028-06-30 --data-urlencode term1_starts_on=2027-09-01 --data-urlencode term1_ends_on=2028-02-17 --data-urlencode term2_starts_on=2028-01-18 --data-urlencode term2_ends_on=2028-06-30)"
contains "…explains the order"                              "$T/body" "Gjysmëvjetori i dytë fillon pasi mbaron i pari."
check "add 2027/2028"                                       "302 /admin/vitet-shkollore" "$(post $A /admin/vitet-shkollore/shto /admin/vitet-shkollore --data-urlencode name=2027/2028 --data-urlencode starts_on=2027-09-01 --data-urlencode ends_on=2028-06-30 --data-urlencode term1_starts_on=2027-09-01 --data-urlencode term1_ends_on=2028-01-17 --data-urlencode term2_starts_on=2028-01-18 --data-urlencode term2_ends_on=2028-06-30)"
NEXT=$(SQL "SELECT id FROM academic_years WHERE name='2027/2028'")
check "…with its two semesters, not current"                "2 0" "$(SQL "SELECT CONCAT((SELECT COUNT(*) FROM terms WHERE academic_year_id=$NEXT),' ',is_current) FROM academic_years WHERE id=$NEXT")"
check "make 2027/2028 current"                              "302 /admin/vitet-shkollore" "$(post $A /admin/vitet-shkollore/$NEXT/aktual /admin/vitet-shkollore)"
check "…exactly one current year"                           "2027/2028" "$(SQL "SELECT GROUP_CONCAT(name) FROM academic_years WHERE is_current=1")"
code $A /admin/klasat >/dev/null
contains "…the class list follows the new year (empty)"     "$T/body" "Ende nuk ka klasa."
CUR=$(SQL "SELECT id FROM academic_years WHERE name='2026/2027'")
post $A /admin/vitet-shkollore/$CUR/aktual /admin/vitet-shkollore >/dev/null
check "switch back to 2026/2027"                            "2026/2027" "$(SQL "SELECT GROUP_CONCAT(name) FROM academic_years WHERE is_current=1")"

echo; echo "== Activity log =="
check "every kind of change is logged"                      "12" "$(SQL "SELECT COUNT(DISTINCT action) FROM activity_log WHERE id > $LOG_START AND action IN ('curriculum.updated','grade.created','grade.deleted','subject.created','subject.updated','subject.deleted','class.created','class.updated','class.deleted','class.subjects_updated','room.created','year.current')")"

restore
echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
