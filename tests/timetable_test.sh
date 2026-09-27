#!/usr/bin/env bash
# End-to-end tests for the timetable (T08): the demo timetable, the
# whole-school sheet, one class's editor with its clash checks, planned vs
# scheduled hours, notifications, and the bell schedule. Development database
# only; everything it changes is restored at the end.
#
#   bash tests/timetable_test.sh
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1"; }
YEAR="(SELECT id FROM academic_years WHERE is_current = 1)"

restore() {
  SQL "DELETE FROM schedule_entries;
       DELETE FROM notifications WHERE type = 'schedule.changed';
       DELETE FROM rooms WHERE name LIKE 'Test%';
       UPDATE teacher_profiles SET timetable_number = NULL WHERE timetable_number >= 90;
       DELETE tp FROM teacher_profiles tp JOIN users u ON u.id = tp.user_id WHERE u.username = 'prove.mesimdhenes';
       DELETE FROM lesson_periods;
       INSERT INTO lesson_periods (shift, number, starts_at, ends_at) VALUES
         (1,1,'08:00','08:45'),(1,2,'08:50','09:35'),(1,3,'09:45','10:30'),(1,4,'10:35','11:20'),(1,5,'11:30','12:15'),(1,6,'12:20','13:05'),
         (2,1,'14:00','14:45'),(2,2,'14:50','15:35'),(2,3,'15:45','16:30'),(2,4,'16:35','17:20'),(2,5,'17:30','18:15'),(2,6,'18:20','19:05');"
  "$PHP" database/demo/test-accounts.php > /dev/null
  "$PHP" database/demo/school.php > /dev/null
}
restore

T=$(mktemp -d); pass=0; fail=0
check()    { if [ "$2" = "$3" ]; then pass=$((pass+1)); printf "PASS  %-62s %s\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-62s got '%s' expected '%s'\n" "$1" "$3" "$2"; fi; }
contains() { if grep -q "$3" "$2"; then pass=$((pass+1)); printf "PASS  %-62s contains \"%s\"\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-62s missing \"%s\"\n" "$1" "$3"; fi; }
lacks()    { if ! grep -q "$3" "$2"; then pass=$((pass+1)); printf "PASS  %-62s no \"%s\"\n" "$1" "$3";
             else fail=$((fail+1)); printf "FAIL  %-62s unexpected \"%s\"\n" "$1" "$3"; fi; }
token()  { curl -s -b "$1" -c "$1" "$B$2" | grep -oE 'name="_token" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)"/\1/'; }
go()     { curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" "$B$2" | sed "s#$B##"; }
code()   { curl -s -o "$T/body" -w "%{http_code}" -b "$1" -c "$1" "$B$2"; }
login()  { local t; t=$(token "$1" /hyr); curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" \
             --data-urlencode "_token=$t" --data-urlencode "login=$2" --data-urlencode "password=$3" "$B/hyr" | sed "s#$B##"; }
post()   { local jar=$1 path=$2 page=$3; shift 3; local t; t=$(token "$jar" "$page")
           curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$jar" -c "$jar" --data-urlencode "_token=$t" "$@" "$B$path" | sed "s#$B##"; }
# The class's current week as form fields, one "cell[d][p]=cs" per line
grid()   { SQL "SELECT CONCAT('cell[',day_of_week,'][',period_number,']=',class_subject_id) FROM schedule_entries WHERE class_id=$1 ORDER BY day_of_week, period_number"; }
# Save a week from a file of form fields (extra fields may follow)
save()   { local class=$1 file=$2; shift 2; local args=(); while read -r line; do args+=(--data-urlencode "$line"); done < "$file"
           post $A "/admin/orari/klasa/$class" "/admin/orari/klasa/$class" "${args[@]}" "$@"; }
week()   { SQL "SELECT GROUP_CONCAT(CONCAT(day_of_week,period_number,':',class_subject_id,':',COALESCE(room_id,'-')) ORDER BY day_of_week, period_number) FROM schedule_entries WHERE class_id=$1"; }
bells()  { local shift=$1; shift; post $A "/admin/orari/oret/$shift" /admin/orari/oret "$@"; }
MORNING=(--data-urlencode "starts[1]=08:00" --data-urlencode "ends[1]=08:45" --data-urlencode "starts[2]=08:50" --data-urlencode "ends[2]=09:35"
         --data-urlencode "starts[3]=09:45" --data-urlencode "ends[3]=10:30" --data-urlencode "starts[4]=10:35" --data-urlencode "ends[4]=11:20"
         --data-urlencode "starts[5]=11:30" --data-urlencode "ends[5]=12:15")

A="$T/admin"; login $A prove.admin Prove-Admin-2026 >/dev/null
LOG_START=$(SQL "SELECT COALESCE(MAX(id), 0) FROM activity_log")
XII1=$(SQL "SELECT id FROM classes WHERE grade_level=12 AND section=1 AND academic_year_id=$YEAR")
XII2=$(SQL "SELECT id FROM classes WHERE grade_level=12 AND section=2 AND academic_year_id=$YEAR")
X1=$(SQL "SELECT id FROM classes WHERE grade_level=10 AND section=1 AND academic_year_id=$YEAR")
MAT=$(SQL "SELECT id FROM subjects WHERE name LIKE 'Matematik%'")
MAT_XII1=$(SQL "SELECT id FROM class_subjects WHERE class_id=$XII1 AND subject_id=$MAT")

echo "== Who may see and change the timetable =="
S="$T/student"; login $S ariana.gashi Nxenes-Demo-2026 >/dev/null
M="$T/teacher"; login $M prove.mesimdhenes Prove-Mesimdhenes-2026 >/dev/null
check "student → /admin/orari is forbidden"                  "403" "$(code $S /admin/orari)"
check "teacher → the bell schedule is forbidden"             "403" "$(code $M /admin/orari/oret)"
check "teacher cannot save a class's week"                   "403 " "$(post $M /admin/orari/klasa/$XII1 /mesimdhenesi --data-urlencode "cell[1][1]=$MAT_XII1")"
check "saving without CSRF token is refused"                 "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $A --data "cell[1][1]=$MAT_XII1" $B/admin/orari/klasa/$XII1)"

echo; echo "== The demo timetable =="
check "1350 lessons: 45 classes × 30"                        "1350" "$(SQL "SELECT COUNT(*) FROM schedule_entries")"
check "every class has 6 lessons a day, Monday to Friday"   "6" "$(SQL "SELECT GROUP_CONCAT(DISTINCT n) FROM (SELECT class_id, day_of_week, COUNT(*) n FROM schedule_entries GROUP BY class_id, day_of_week) x")"
check "every subject as often as its weekly hours"           "0" "$(SQL "SELECT COUNT(*) FROM class_subjects cs JOIN classes c ON c.id=cs.class_id LEFT JOIN grade_subjects gs ON gs.grade_level=c.grade_level AND gs.subject_id=cs.subject_id WHERE COALESCE(cs.weekly_hours, gs.weekly_hours) <> (SELECT COUNT(*) FROM schedule_entries se WHERE se.class_subject_id=cs.id)")"
check "no subject more than twice a day"                     "0" "$(SQL "SELECT COUNT(*) FROM (SELECT class_subject_id, day_of_week FROM schedule_entries GROUP BY 1,2 HAVING COUNT(*) > 2) x")"
check "no teacher in two classes at once"                    "0" "$(SQL "SELECT COUNT(*) FROM (SELECT cs.teacher_id, se.day_of_week, se.period_number, c.shift FROM schedule_entries se JOIN classes c ON c.id=se.class_id JOIN class_subjects cs ON cs.id=se.class_subject_id GROUP BY 1,2,3,4 HAVING COUNT(*) > 1) x")"
before=$(SQL "SELECT MD5(GROUP_CONCAT(CONCAT(class_id,day_of_week,period_number,class_subject_id) ORDER BY class_id, day_of_week, period_number)) FROM schedule_entries")
SQL "DELETE FROM schedule_entries"; "$PHP" database/demo/school.php > /dev/null
check "the demo timetable is the same on every run"          "$before" "$(SQL "SELECT MD5(GROUP_CONCAT(CONCAT(class_id,day_of_week,period_number,class_subject_id) ORDER BY class_id, day_of_week, period_number)) FROM schedule_entries")"

echo; echo "== The whole-school sheet =="
check "morning sheet"                                        "200" "$(code $A /admin/orari)"
contains "…titled like the printed timetable"                "$T/body" "Orari i mësimit"
check "…22 classes (XI-1…7, XII-1…15)"                      "22" "$(grep -c 'class="sheet__class"' $T/body)"
contains "…with the homeroom teachers on the right"          "$T/body" "Dardan Aliu"
contains "…grade bands"                                      "$T/body" "Klasa XII<"
contains "…days across"                                      "$T/body" "e mërkurë"
contains "…a print button"                                   "$T/body" "data-print"
contains "…every class complete"                             "$T/body" "Çdo klasë e ka orarin e plotë."
lacks "…and no clashes"                                      "$T/body" "përplasje në orar"
check "afternoon sheet: X-1…15 and XI-8…15"                "23" "$(code $A '/admin/orari?ndrrimi=2' >/dev/null; grep -c 'class="sheet__class"' $T/body)"
SQL "UPDATE teacher_profiles tp JOIN users u ON u.id = tp.user_id SET tp.timetable_number = 99 WHERE u.username = 'prove.mesimdhenes'"
SQL "INSERT IGNORE INTO teacher_profiles (user_id, show_on_website, timetable_number) SELECT id, 0, 99 FROM users WHERE username = 'prove.mesimdhenes'"
code $A '/admin/orari?ndrrimi=1&shfaq=mesimdhenesit' >/dev/null
contains "teacher view: cells show the timetable number"     "$T/body" '<td class="sheet__cell[^"]*" title="[^"]*Matematikë · Provë Mësimdhënëse">99</td>'
contains "…others show initials until they have one"          "$T/body" "Mësimdhënësit në orar"
contains "…and the legend says who 99 is"                    "$T/body" 'sheet-legend__code">99<'

echo; echo "== One class's week =="
check "unknown class → 404"                                  "404" "$(code $A /admin/orari/klasa/999999)"
check "XII-1 editor"                                         "200" "$(code $A /admin/orari/klasa/$XII1)"
contains "…titled with the class"                            "$T/body" "Orari i klasës XII-1"
contains "…shows the periods with their times"               "$T/body" "08:50–09:35"
contains "…warns which teachers are busy elsewhere"          "$T/body" "· ka orë në "
contains "…planned vs scheduled"                             "$T/body" "30 nga 30 orë në orar"
grid $XII1 > "$T/week"
check "saving the same week changes nothing"                 "302 /admin/orari/klasa/$XII1" "$(save $XII1 "$T/week")"
code $A /admin/orari/klasa/$XII1 >/dev/null
contains "…and says so"                                      "$T/body" "Orari i klasës XII-1 nuk ndryshoi."
check "…no one is notified"                                  "0" "$(SQL "SELECT COUNT(*) FROM notifications WHERE type='schedule.changed'")"

# A slot where the test teacher teaches XI-5 and XII-1 has something else
read -r D P <<< "$(SQL "SELECT se.day_of_week, se.period_number FROM schedule_entries se JOIN classes c ON c.id=se.class_id JOIN class_subjects cs ON cs.id=se.class_subject_id JOIN users u ON u.id=cs.teacher_id WHERE u.username='prove.mesimdhenes' AND c.grade_level=11 AND NOT EXISTS (SELECT 1 FROM schedule_entries x WHERE x.class_id=$XII1 AND x.day_of_week=se.day_of_week AND x.period_number=se.period_number AND x.class_subject_id=$MAT_XII1) LIMIT 1")"
sed "s/^cell\[$D\]\[$P\]=.*/cell[$D][$P]=$MAT_XII1/" "$T/week" > "$T/clash"
BEFORE=$(week $XII1)
check "the same teacher in XI-5 at that time → 422"          "422 " "$(save $XII1 "$T/clash")"
contains "…names the teacher and the other class"            "$T/body" "Provë Mësimdhënëse ka orë në klasën XI-5 në këtë kohë."
contains "…marks the cell"                                   "$T/body" "id=\"cell-$D-$P\" name=\"cell\[$D\]\[$P\]\" aria-invalid=\"true\""
check "…and nothing was saved"                               "$BEFORE" "$(week $XII1)"
OTHER_CS=$(SQL "SELECT id FROM class_subjects WHERE class_id=$XII2 LIMIT 1")
check "a subject of another class → 422"                     "422 " "$(save $XII1 "$T/week" --data-urlencode "cell[5][6]=$OTHER_CS")"
contains "…asks for this class's subjects"                   "$T/body" "Zgjidhni një lëndë të kësaj klase."

# A real change: leave Friday's last period free (always allowed)
grep -v '^cell\[5\]\[6\]=' "$T/week" > "$T/short"
check "leave Friday's last period free"                      "302 /admin/orari/klasa/$XII1" "$(save $XII1 "$T/short")"
code $A /admin/orari/klasa/$XII1 >/dev/null
contains "…the admin is told the students were notified"     "$T/body" "Nxënësit e klasës u njoftuan."
check "…the 4 students of XII-1 are notified"                "4" "$(SQL "SELECT COUNT(*) FROM notifications n JOIN enrollments e ON e.student_id=n.user_id WHERE n.type='schedule.changed' AND e.class_id=$XII1")"
contains "…the editor counts 29 of 30"                       "$T/body" "29 nga 30 orë në orar"
contains "…and says which subject is short"                  "$T/body" "mungojnë 1"
code $A /admin/klasat >/dev/null
contains "…the class list shows it"                          "$T/body" "29 nga 30 orë"
code $A /admin/orari >/dev/null
contains "…the sheet lists XII-1 as incomplete"              "$T/body" "1 klasa pa orar të plotë"
check "put the lesson back"                                  "302 /admin/orari/klasa/$XII1" "$(save $XII1 "$T/week")"
check "…a second change does not pile up notifications"      "4" "$(SQL "SELECT COUNT(*) FROM notifications WHERE type='schedule.changed'")"
check "…students of other classes are not notified"          "0" "$(SQL "SELECT COUNT(*) FROM notifications n JOIN enrollments e ON e.student_id=n.user_id WHERE n.type='schedule.changed' AND e.class_id<>$XII1")"

echo; echo "== Rooms in the timetable =="
post $A /admin/sallat/shto /admin/sallat --data-urlencode "name=Test Palestra" >/dev/null
GYM=$(SQL "SELECT id FROM rooms WHERE name='Test Palestra'")
grid $XII2 > "$T/week2"; grid $X1 > "$T/weekx"
check "XII-1: Monday 1st lesson in the gym"                  "302 /admin/orari/klasa/$XII1" "$(save $XII1 "$T/week" --data-urlencode "room[1][1]=$GYM")"
check "…stored on that lesson only"                          "1" "$(SQL "SELECT COUNT(*) FROM schedule_entries WHERE room_id=$GYM")"
check "XII-2 in the gym at the same time → 422"              "422 " "$(save $XII2 "$T/week2" --data-urlencode "room[1][1]=$GYM")"
contains "…the room is taken by XII-1"                       "$T/body" "Salla Test Palestra është e zënë nga klasa XII-1 në këtë kohë."
check "an afternoon class in the gym at its 1st lesson"      "302 /admin/orari/klasa/$X1" "$(save $X1 "$T/weekx" --data-urlencode "room[1][1]=$GYM")"
code $A /admin/orari/klasa/$XII2 >/dev/null
contains "the editor warns that the gym is taken"            "$T/body" "Test Palestra · e zënë nga XII-1"
check "a room used in the timetable cannot be deleted"       "302 /admin/sallat/$GYM/ndrysho" "$(post $A /admin/sallat/$GYM/fshij /admin/sallat/$GYM/ndrysho)"

echo; echo "== The bell schedule =="
check "bell schedule page"                                   "200" "$(code $A /admin/orari/oret)"
contains "…morning starts at 08:00"                          "$T/body" 'value="08:00"'
contains "…24-hour times"                                    "$T/body" 'value="18:20"'
check "a lesson ending before it starts → 422"               "422 " "$(bells 1 "${MORNING[@]}" --data-urlencode "starts[6]=12:20" --data-urlencode "ends[6]=12:00")"
contains "…explains"                                         "$T/body" "Ora duhet të mbarojë pasi fillon."
check "overlapping lessons → 422"                            "422 " "$(bells 1 "${MORNING[@]}" --data-urlencode "starts[6]=12:00" --data-urlencode "ends[6]=12:45")"
contains "…explains"                                         "$T/body" "Ora 6 fillon para se të mbarojë ora 5."
check "a gap in the middle → 422"                            "422 " "$(bells 1 "${MORNING[@]}" --data-urlencode "starts[7]=13:10" --data-urlencode "ends[7]=13:55")"
check "“8.00” is not a time → 422"                           "422 " "$(bells 1 "${MORNING[@]}" --data-urlencode "starts[6]=12.20" --data-urlencode "ends[6]=13:05")"
check "removing period 6 while it is used → 422"             "422 " "$(bells 1 "${MORNING[@]}")"
contains "…explains why"                                     "$T/body" "Ora 6 përdoret në orarin e klasave"
check "add a 7th morning period (13:10–13:55)"               "302 /admin/orari/oret" "$(bells 1 "${MORNING[@]}" --data-urlencode "starts[6]=12:20" --data-urlencode "ends[6]=13:05" --data-urlencode "starts[7]=13:10" --data-urlencode "ends[7]=13:55")"
check "…7 morning periods"                                   "7" "$(SQL "SELECT COUNT(*) FROM lesson_periods WHERE shift=1")"
code $A /admin/orari/klasa/$XII1 >/dev/null
contains "…the class editor offers period 7"                 "$T/body" "13:10–13:55"
check "…the sheet has 7 columns per day"                     "35" "$(code $A /admin/orari >/dev/null; grep -c 'class="sheet__period' $T/body)"
check "remove it again"                                      "302 /admin/orari/oret" "$(bells 1 "${MORNING[@]}" --data-urlencode "starts[6]=12:20" --data-urlencode "ends[6]=13:05" --data-urlencode "starts[7]=" --data-urlencode "ends[7]=")"
check "move the morning 5 minutes later (07:55 start)"       "302 /admin/orari/oret" "$(bells 1 --data-urlencode "starts[1]=7:55" --data-urlencode "ends[1]=08:40" --data-urlencode "starts[2]=08:50" --data-urlencode "ends[2]=09:35" --data-urlencode "starts[3]=09:45" --data-urlencode "ends[3]=10:30" --data-urlencode "starts[4]=10:35" --data-urlencode "ends[4]=11:20" --data-urlencode "starts[5]=11:30" --data-urlencode "ends[5]=12:15" --data-urlencode "starts[6]=12:20" --data-urlencode "ends[6]=13:05")"
code $A /admin/orari/klasa/$XII1 >/dev/null
contains "…every class's lessons follow"                     "$T/body" "07:55–08:40"
check "…the timetable itself is untouched"                   "1350" "$(SQL "SELECT COUNT(*) FROM schedule_entries")"
# Clashes are judged by clock time: pull the afternoon's 1st period onto the morning's
check "afternoon period 1 at 08:00 (overlaps the morning)"  "302 /admin/orari/oret" "$(bells 2 --data-urlencode "starts[1]=08:00" --data-urlencode "ends[1]=08:45" --data-urlencode "starts[2]=14:50" --data-urlencode "ends[2]=15:35" --data-urlencode "starts[3]=15:45" --data-urlencode "ends[3]=16:30" --data-urlencode "starts[4]=16:35" --data-urlencode "ends[4]=17:20" --data-urlencode "starts[5]=17:30" --data-urlencode "ends[5]=18:15" --data-urlencode "starts[6]=18:20" --data-urlencode "ends[6]=19:05")"
EXPECTED=$(SQL "SELECT COUNT(*) FROM schedule_entries a JOIN classes ca ON ca.id=a.class_id JOIN class_subjects csa ON csa.id=a.class_subject_id JOIN schedule_entries b ON b.day_of_week=a.day_of_week JOIN classes cb ON cb.id=b.class_id JOIN class_subjects csb ON csb.id=b.class_subject_id WHERE ca.shift=1 AND a.period_number=1 AND cb.shift=2 AND b.period_number=1 AND csa.teacher_id=csb.teacher_id")
EXPECTED=$((EXPECTED + 1))   # and the gym: XII-1 and X-1 at their first lesson on Monday
code $A /admin/orari >/dev/null
check "…teachers of both shifts now clash (by clock time)"  "yes" "$([ "$EXPECTED" -gt 0 ] && grep -q "$EXPECTED përplasje në orar" $T/body && echo yes || echo "no ($EXPECTED expected)")"
code $A /admin/orari/klasa/$XII1 >/dev/null
contains "…and the class editor warns about them"            "$T/body" "· ka orë në X-"

echo; echo "== Activity log =="
check "timetable and bell changes are logged"                "2" "$(SQL "SELECT COUNT(DISTINCT action) FROM activity_log WHERE id > $LOG_START AND action IN ('schedule.updated','bells.updated')")"

restore
echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
