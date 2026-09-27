#!/usr/bin/env bash
# End-to-end tests for the timetable in numbers (orari me numra, T08c): the
# official morning timetable as printed (a teacher's number in every cell),
# the admin grid and its checks, and turning numbers into lessons once the
# teachers' subjects are known.
# Development database only; everything it changes is restored at the end.
#
#   bash tests/sheet_test.sh
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1" | tr -d '\r'; }
YEAR="(SELECT id FROM academic_years WHERE is_current = 1)"

# The timetable and subject teachers are backed up before the test applies the sheet
# (zz_sheet_*); restoring puts them back exactly. SQL literals stay ASCII (tests/README.md).
restore() {
  if [ -n "$(SQL "SHOW TABLES LIKE 'zz_sheet_se'")" ]; then
    SQL "DELETE FROM schedule_entries;
         INSERT INTO schedule_entries SELECT * FROM zz_sheet_se;
         UPDATE class_subjects cs JOIN zz_sheet_cs b ON b.id = cs.id SET cs.teacher_id = b.teacher_id;
         DROP TABLE zz_sheet_se, zz_sheet_cs;"
  fi
  SQL "DELETE ts FROM teacher_subjects ts JOIN teacher_profiles tp ON tp.user_id = ts.teacher_id WHERE tp.timetable_number < 90;
       DELETE FROM schedule_sheet_cells;
       DELETE FROM notifications WHERE type = 'schedule.changed';"
  "$PHP" database/demo/test-accounts.php > /dev/null
  "$PHP" database/demo/school.php > /dev/null     # loads the morning sheet again
}
restore

T=$(mktemp -d); pass=0; fail=0
check()    { if [ "$2" = "$3" ]; then pass=$((pass+1)); printf "PASS  %-62s %s\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-62s got '%s' expected '%s'\n" "$1" "$3" "$2"; fi; }
contains() { if grep -q "$3" "$2"; then pass=$((pass+1)); printf "PASS  %-62s contains \"%s\"\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-62s missing \"%s\"\n" "$1" "$3"; fi; }
token()  { curl -s -b "$1" -c "$1" "$B$2" | grep -oE 'name="_token" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)"/\1/'; }
code()   { curl -s -o "$T/body" -w "%{http_code}" -b "$1" -c "$1" "$B$2"; }
login()  { local t; t=$(token "$1" /hyr); curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" \
             --data-urlencode "_token=$t" --data-urlencode "login=$2" --data-urlencode "password=$3" "$B/hyr" | sed "s#$B##"; }
post()   { local jar=$1 path=$2 page=$3; shift 3; local t; t=$(token "$jar" "$page")
           curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$jar" -c "$jar" --data-urlencode "_token=$t" "$@" "$B$path" | sed "s#$B##"; }
# A shift's sheet as the form sends it: n[class][day*100+period]=number&…
body()   { SQL "SELECT CONCAT('n[',sc.class_id,'][',sc.day_of_week*100+sc.period_number,']=',sc.teacher_number) FROM schedule_sheet_cells sc JOIN classes c ON c.id = sc.class_id WHERE c.shift = $1" | paste -sd'&' -; }
# Save a sheet from a body file; later fields replace earlier ones (n[…]=52)
save()   { local shift=$1 file=$2; shift 2; post $A "/admin/orari/numrat/$shift" "/admin/orari/numrat?ndrrimi=$shift" --data-binary @"$file" --data "plote=1" "$@"; }
cell()   { SQL "SELECT teacher_number FROM schedule_sheet_cells WHERE class_id = $1 AND day_of_week = $2 AND period_number = $3"; }
cells()  { SQL "SELECT COUNT(*) FROM schedule_sheet_cells sc JOIN classes c ON c.id = sc.class_id WHERE c.shift = $1"; }
lesson() { SQL "SELECT CONCAT(s.short_name,'/',tp.timetable_number) FROM schedule_entries se JOIN class_subjects cs ON cs.id = se.class_subject_id JOIN subjects s ON s.id = cs.subject_id JOIN teacher_profiles tp ON tp.user_id = cs.teacher_id WHERE se.class_id = $1 AND se.day_of_week = $2 AND se.period_number = $3"; }
week()   { SQL "SELECT CONCAT(COUNT(*),'-',SUM(CRC32(CONCAT_WS(':',class_id,day_of_week,period_number,class_subject_id)))) FROM schedule_entries"; }
give()   { SQL "INSERT INTO teacher_subjects (teacher_id, subject_id) SELECT tp.user_id, s.id FROM teacher_profiles tp, subjects s WHERE tp.timetable_number = $1 AND s.short_name = '$2'"; }
cellval(){ grep -A1 "name=\"n\[$1\]\[$2\]\"" "$T/body" | grep -oE 'value="[0-9]*"' | head -1; }

A="$T/admin"; login $A prove.admin Prove-Admin-2026 >/dev/null
S="$T/student"; login $S ariana.gashi Nxenes-Demo-2026 >/dev/null
M="$T/teacher"; login $M prove.mesimdhenes Prove-Mesimdhenes-2026 >/dev/null
LOG_START=$(SQL "SELECT COALESCE(MAX(id), 0) FROM activity_log")
class_id() { SQL "SELECT id FROM classes WHERE grade_level = $1 AND section = $2 AND academic_year_id = $YEAR"; }
XI1=$(class_id 11 1); XI2=$(class_id 11 2); XI3=$(class_id 11 3); XII1=$(class_id 12 1); XII2=$(class_id 12 2); XII15=$(class_id 12 15)
T29=$(SQL "SELECT user_id FROM teacher_profiles WHERE timetable_number = 29")

echo "== Who may see and change it =="
check "student → /admin/orari/numrat is forbidden"           "403" "$(code $S /admin/orari/numrat)"
check "teacher → /admin/orari/numrat is forbidden"           "403" "$(code $M /admin/orari/numrat)"
check "saving without CSRF token is refused"                 "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $A --data "n[$XI1][101]=29&plote=1" $B/admin/orari/numrat/1)"
check "applying without CSRF token is refused"               "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $A --data "x=1" $B/admin/orari/numrat/1/apliko)"
check "unknown shift → 404"                                  "404 " "$(post $A /admin/orari/numrat/3 /admin/orari/numrat --data plote=1)"

echo; echo "== The official morning timetable, as printed =="
check "660 numbers: 22 classes × 5 days × 6 lessons"         "660" "$(cells 1)"
check "…in the 22 morning classes"                           "22" "$(SQL "SELECT COUNT(DISTINCT class_id) FROM schedule_sheet_cells")"
check "XI-1 on Monday: 29 68 35 44 44 29"                    "29 68 35 44 44 29" "$(SQL "SELECT GROUP_CONCAT(teacher_number ORDER BY period_number SEPARATOR ' ') FROM schedule_sheet_cells WHERE class_id = $XI1 AND day_of_week = 1")"
check "XII-15 on Friday, 6th lesson: 70"                     "70" "$(cell $XII15 5 6)"
check "57 teachers teach in the morning"                     "57" "$(SQL "SELECT COUNT(DISTINCT teacher_number) FROM schedule_sheet_cells")"
check "…every number is one of the staff list"               "0" "$(SQL "SELECT COUNT(*) FROM schedule_sheet_cells sc LEFT JOIN teacher_profiles tp ON tp.timetable_number = sc.teacher_number WHERE tp.user_id IS NULL")"
check "…every homeroom teacher teaches their own class"       "0" "$(SQL "SELECT COUNT(*) FROM classes c JOIN teacher_profiles tp ON tp.user_id = c.homeroom_teacher_id WHERE c.shift = 1 AND NOT EXISTS (SELECT 1 FROM schedule_sheet_cells sc WHERE sc.class_id = c.id AND sc.teacher_number = tp.timetable_number)")"
check "…Minire Kurteshi (29) has a full norm of 20"          "20" "$(SQL "SELECT COUNT(*) FROM schedule_sheet_cells WHERE teacher_number = 29")"
check "no afternoon sheet yet"                               "0" "$(cells 2)"
check "nothing applied: the morning keeps its demo week"     "0" "$(SQL "SELECT COUNT(*) FROM schedule_entries se JOIN class_subjects cs ON cs.id = se.class_subject_id JOIN teacher_profiles tp ON tp.user_id = cs.teacher_id WHERE tp.timetable_number < 90")"

echo; echo "== The page =="
check "the numbers page"                                     "200" "$(code $A /admin/orari/numrat)"
contains "…titled"                                           "$T/body" "Orari me numra"
check "…a cell for every lesson of the 22 classes"           "660" "$(grep -c 'class="sheet-input"' $T/body)"
check "…XI-1, Monday, 1st lesson: 29"                        'value="29"' "$(cellval $XI1 101)"
contains "…660 lessons in the sheet"                         "$T/body" 'stat__value">660<'
contains "…57 teachers"                                      "$T/body" 'stat__value">57<'
check "…no problems"                                         "0" "$(grep -c 'probleme në orar\|1 problem në orar' $T/body)"
check "…all 22 classes wait for their teachers' subjects"    "22" "$(grep -c 'badge--plain">Pret lëndët<' $T/body)"
contains "…who each number is"                               "$T/body" "Minire Kurteshi"
contains "…with a link to give them their subjects"          "$T/body" "Shëno lëndët"
check "…nobody above their norm"                             "0" "$(grep -c 'mbi normë' $T/body)"
check "…no class ready, so no button to apply"               "0" "$(grep -c 'Apliko në orar' $T/body)"
code $A /admin/orari >/dev/null
contains "the whole-school sheet links to it"                "$T/body" "/admin/orari/numrat?ndrrimi=1"
check "the afternoon page"                                   "200" "$(code $A '/admin/orari/numrat?ndrrimi=2')"
check "…an empty cell for every afternoon lesson"            "690" "$(grep -c 'class="sheet-input"' $T/body)"
contains "…says the sheet is empty"                          "$T/body" "Ky orar është ende bosh"

echo; echo "== Saving =="
body 1 > "$T/morning"
check "saving the sheet unchanged"                           "302 /admin/orari/numrat?ndrrimi=1" "$(save 1 "$T/morning")"
code $A '/admin/orari/numrat?ndrrimi=1' >/dev/null
contains "…says nothing changed"                             "$T/body" "Orari me numra nuk ndryshoi."
check "…and nothing is logged"                               "0" "$(SQL "SELECT COUNT(*) FROM activity_log WHERE id > $LOG_START AND action = 'schedule.sheet_updated'")"
check "a letter in a cell → 422"                             "422 " "$(save 1 "$T/morning" --data "n[$XI1][102]=x")"
contains "…asks for a number"                                "$T/body" "Shkruani numrin e mësimdhënësit (1–999)."
check "…marks the cell"                                      "1" "$(grep -A2 "id=\"n-$XI1-1-2\"" $T/body | grep -c 'aria-invalid="true"')"
check "…and nothing was saved"                               "68" "$(cell $XI1 1 2)"
check "0 → 422"                                              "422 " "$(save 1 "$T/morning" --data "n[$XI1][102]=0")"
check "1000 → 422"                                           "422 " "$(save 1 "$T/morning" --data "n[$XI1][102]=1000")"
check "a form that did not arrive whole → 422"               "422 " "$(post $A /admin/orari/numrat/1 '/admin/orari/numrat?ndrrimi=1' --data-binary @"$T/morning")"
contains "…says so"                                          "$T/body" "Serveri nuk e mori formularin të plotë"
check "…and nothing was saved"                               "660" "$(cells 1)"

echo; echo "== What the numbers say =="
check "save: 29 also in XI-2, number 52 in XII-1"            "302 /admin/orari/numrat?ndrrimi=1" "$(save 1 "$T/morning" --data "n[$XI2][101]=29" --data "n[$XII1][101]=52")"
check "…saved"                                               "29 52" "$(echo $(cell $XI2 1 1) $(cell $XII1 1 1))"
code $A '/admin/orari/numrat?ndrrimi=1' >/dev/null
contains "a teacher in two classes at once"                  "$T/body" "Minire Kurteshi (29) është në dy klasa njëkohësisht: XI-1 dhe XI-2, e hënë, ora 1."
contains "a number that is nobody's"                         "$T/body" "Numri 52 nuk i përket asnjë mësimdhënësi (XII-1)."
contains "…counted"                                          "$T/body" "2 probleme në orar"
check "…the three cells are marked"                          "3" "$(grep -c 'is-invalid' $T/body)"
check "…the three classes have problems"                     "3" "$(grep -c 'badge--danger">Ka probleme<' $T/body)"
check "back to the printed sheet"                            "302 /admin/orari/numrat?ndrrimi=1" "$(save 1 "$T/morning")"
check "…saved"                                               "44 25" "$(echo $(cell $XI2 1 1) $(cell $XII1 1 1))"
check "changes are logged"                                   "2" "$(SQL "SELECT COUNT(*) FROM activity_log WHERE id > $LOG_START AND action = 'schedule.sheet_updated'")"
check "the loader leaves a sheet that exists alone"          "29" "$(SQL "UPDATE schedule_sheet_cells SET teacher_number = 29 WHERE class_id = $XI2 AND day_of_week = 1 AND period_number = 1"; "$PHP" database/demo/school.php >/dev/null; cell $XI2 1 1)"
SQL "UPDATE schedule_sheet_cells SET teacher_number = 44 WHERE class_id = $XI2 AND day_of_week = 1 AND period_number = 1"

echo; echo "== From numbers to lessons =="
SQL "DROP TABLE IF EXISTS zz_sheet_se, zz_sheet_cs;
     CREATE TABLE zz_sheet_se AS SELECT * FROM schedule_entries;
     CREATE TABLE zz_sheet_cs AS SELECT id, teacher_id FROM class_subjects;"
BEFORE=$(week)
check "applying while no class is ready"                     "302 /admin/orari/numrat?ndrrimi=1" "$(post $A /admin/orari/numrat/1/apliko '/admin/orari/numrat?ndrrimi=1')"
check "…changes nothing"                                     "$BEFORE" "$(week)"
# What the 12 teachers of XI-1 teach (made up for the test): 68 two subjects, 1 + 2 lessons
give 29 Mat.; give 35 Shqip; give 44 Kimi; give 1 Fiz.; give 68 TIK; give 68 Filoz.; give 61 Angl.
give 57 Gjerm.; give 22 Biol.; give 58 "Ed. fiz."; give 14 Zgjedh.; give 10 Gjeogr.; give 50 Psik.
code $A '/admin/orari/numrat?ndrrimi=1' >/dev/null
check "XI-1 and XI-3 (the same 12 teachers) are ready"       "2" "$(grep -c 'badge--success">Gati<' $T/body)"
contains "…named above the button"                           "$T/body" "Gati për t’u aplikuar: XI-1, XI-3."
contains "…with the button"                                  "$T/body" "Apliko në orar"
contains "two subjects of one teacher are flagged"           "$T/body" "XI-1: Dardan Aliu (68) jep Teknologji (1) dhe Filozofi (2). Teknologji: e premte, ora 1."
contains "a teacher's subjects in the list"                  "$T/body" "Matematik"
check "apply"                                                "302 /admin/orari/numrat?ndrrimi=1" "$(post $A /admin/orari/numrat/1/apliko '/admin/orari/numrat?ndrrimi=1')"
code $A '/admin/orari/numrat?ndrrimi=1' >/dev/null
contains "…says which classes"                               "$T/body" "Orari u aplikua në 2 klasa: XI-1, XI-3."
check "…both are now in the timetable"                       "2" "$(grep -c 'badge--info">Në orar<' $T/body)"
check "XI-1: 30 lessons"                                     "30" "$(SQL "SELECT COUNT(*) FROM schedule_entries WHERE class_id = $XI1")"
check "…Monday 1st: Matematikë with 29"                      "Mat./29" "$(lesson $XI1 1 1)"
check "…Monday 2nd: Filozofi with 68"                        "Filoz./68" "$(lesson $XI1 1 2)"
check "…Tuesday 2nd: Filozofi with 68"                       "Filoz./68" "$(lesson $XI1 2 2)"
check "…Friday 1st: TIK, the day 68 has 1 lesson here"      "TIK/68" "$(lesson $XI1 5 1)"
check "…Friday 6th: Kimi with 44"                            "Kimi/44" "$(lesson $XI1 5 6)"
check "…29 teaches Matematikë in XI-1"                       "$T29" "$(SQL "SELECT cs.teacher_id FROM class_subjects cs JOIN subjects s ON s.id = cs.subject_id WHERE cs.class_id = $XI1 AND s.short_name = 'Mat.'")"
check "XI-3 too: Wednesday 1st, Matematikë with 29"          "Mat./29" "$(lesson $XI3 3 1)"
check "XI-2 keeps its week (5 has no subjects yet)"          "0" "$(SQL "SELECT COUNT(*) FROM schedule_entries se JOIN class_subjects cs ON cs.id = se.class_subject_id WHERE se.class_id = $XI2 AND cs.teacher_id = $T29")"
check "no clash anywhere in the school"                      "0" "$(code $A /admin/orari >/dev/null; grep -c 'përplasje në orar' $T/body)"
code $A /admin/orari/mesimdhenesi/$T29 >/dev/null
contains "29's week shows XI-1"                              "$T/body" "XI-1"
AFTER=$(week)
check "applying again changes nothing"                       "302 /admin/orari/numrat?ndrrimi=1" "$(post $A /admin/orari/numrat/1/apliko '/admin/orari/numrat?ndrrimi=1')"
check "…the same week"                                       "$AFTER" "$(week)"
check "…logged once"                                         "1" "$(SQL "SELECT COUNT(*) FROM activity_log WHERE id > $LOG_START AND action = 'schedule.sheet_applied'")"
check "the loader keeps an applied class"                    "Mat./29" "$("$PHP" database/demo/school.php >/dev/null; lesson $XI1 1 1)"
# The admin's choice wins: in XI-3, 44 is given Fizikë (instead of Kimi)
SQL "UPDATE class_subjects cs JOIN subjects s ON s.id = cs.subject_id SET cs.teacher_id = NULL WHERE cs.class_id = $XI3 AND s.short_name = 'Kimi';
     UPDATE class_subjects cs JOIN subjects s ON s.id = cs.subject_id SET cs.teacher_id = (SELECT user_id FROM teacher_profiles WHERE timetable_number = 44) WHERE cs.class_id = $XI3 AND s.short_name = 'Fiz.'"
code $A '/admin/orari/numrat?ndrrimi=1' >/dev/null
contains "a subject given in the class counts first"         "$T/body" "XI-3: Fizikë e ka Ylber Ukshini (44)"
check "…so XI-3 waits for the admin"                         "21" "$(grep -c 'badge--plain">Pret lëndët<' $T/body)"

echo; echo "== 26: Matematikë and Orientim në karrierë in XII-1 and XII-2 =="
# The other teachers of XII-1 and XII-2 (made up for the test); 26's subjects come from staff.php
give 3 Shqip; give 15 Gjerm.; give 36 Kimi; give 55 Biol.; give 41 Fiz.; give 42 Fiz.; give 25 Angl.
give 60 "Ed. fiz."; give 47 TIK; give 67 Gjeogr.; give 66 Gjeogr.; give 11 Astr.; give 13 Astr.
code $A '/admin/orari/numrat?ndrrimi=1' >/dev/null
contains "XII-1 and XII-2 are ready"                         "$T/body" "Gati për t’u aplikuar: XII-1, XII-2."
contains "…26's Orientim në karrierë in XII-1: Thursday"     "$T/body" "XII-1: Enver Bajrami (26) jep Matematikë (4) dhe Orientim në karrierë (2). Orientim në karrierë: e enjte, ora 2 dhe 4."
contains "…in XII-2: a day with 2 lessons with 26 (Thursday)" "$T/body" "Orientim në karrierë: e enjte, ora 1 dhe 6."
check "apply"                                                "302 /admin/orari/numrat?ndrrimi=1" "$(post $A /admin/orari/numrat/1/apliko '/admin/orari/numrat?ndrrimi=1')"
check "XII-1, Thursday 2nd and 4th: Orientim në karrierë"    "Karrierë/26 Karrierë/26" "$(echo $(lesson $XII1 4 2) $(lesson $XII1 4 4))"
check "…Monday 3rd and 4th, Tuesday 4th, Wednesday 6th: Matematikë" "Mat./26 Mat./26 Mat./26 Mat./26" "$(echo $(lesson $XII1 1 3) $(lesson $XII1 1 4) $(lesson $XII1 2 4) $(lesson $XII1 3 6))"
check "XII-2, Thursday 1st and 6th: Orientim në karrierë"    "Karrierë/26 Karrierë/26" "$(echo $(lesson $XII2 4 1) $(lesson $XII2 4 6))"
check "…Tuesday 5th and 6th: Matematikë"                     "Mat./26 Mat./26" "$(echo $(lesson $XII2 2 5) $(lesson $XII2 2 6))"
check "an XII-1 student's timetable"                         "200" "$(code $S '/nxenesi/orari?tani=2026-10-01T09:00')"
contains "…shows Orientim në karrierë with Enver Bajrami"    "$T/body" "Orientim në karrierë"
contains "…and says the timetable changed"                    "$T/body" "Orari i klasës suaj ndryshoi"
# A correction in the class editor stays: swap Monday 3rd (Matematikë) with Thursday 2nd (Orientim në karrierë)
SQL "UPDATE schedule_entries a JOIN schedule_entries b ON b.class_id = a.class_id AND b.day_of_week = 4 AND b.period_number = 2
        SET a.class_subject_id = b.class_subject_id, b.class_subject_id = a.class_subject_id
      WHERE a.class_id = $XII1 AND a.day_of_week = 1 AND a.period_number = 3"
check "…(swapped in the class editor)"                        "Karrierë/26 Mat./26" "$(echo $(lesson $XII1 1 3) $(lesson $XII1 4 2))"
code $A '/admin/orari/numrat?ndrrimi=1' >/dev/null
contains "a correction in the class editor is kept"          "$T/body" "Orientim në karrierë: e hënë, ora 3; e enjte, ora 4."
check "…XII-1 is still in the timetable, nothing to apply"   "0" "$(grep -c 'Gati për t’u aplikuar' $T/body)"

echo; echo "== Activity log =="
check "saving and applying are logged"                       "2" "$(SQL "SELECT COUNT(DISTINCT action) FROM activity_log WHERE id > $LOG_START AND action IN ('schedule.sheet_updated','schedule.sheet_applied')")"

restore
check "restored: the morning keeps its demo week again"      "0" "$(SQL "SELECT COUNT(*) FROM schedule_entries se JOIN class_subjects cs ON cs.id = se.class_subject_id JOIN teacher_profiles tp ON tp.user_id = cs.teacher_id WHERE tp.timetable_number < 90")"
echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
