#!/usr/bin/env bash
# End-to-end tests for the timetable as students and teachers see it (T09):
# the student's Orari and Lëndët, the teacher's Orari and Klasat, "now / next"
# at chosen moments (?tani=, development only), the "timetable changed" note,
# ownership (another teacher's class is a 404) and role separation.
# Development database only; everything it changes is restored at the end.
#
#   bash tests/schedule_views_test.sh
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1"; }
YEAR="(SELECT id FROM academic_years WHERE is_current = 1)"

restore() {
  SQL "DELETE FROM schedule_entries; DELETE FROM notifications WHERE type = 'schedule.changed';"
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
code()   { curl -s -o "$T/body" -w "%{http_code}" -b "$1" -c "$1" "$B$2"; }
login()  { local t; t=$(token "$1" /hyr); curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" \
             --data-urlencode "_token=$t" --data-urlencode "login=$2" --data-urlencode "password=$3" "$B/hyr" | sed "s#$B##"; }
post()   { local jar=$1 path=$2 page=$3; shift 3; local t; t=$(token "$jar" "$page")
           curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$jar" -c "$jar" --data-urlencode "_token=$t" "$@" "$B$path" | sed "s#$B##"; }
# "Matematikë · Prof. Arta Dervishi" style details of XII-1's lesson on a day and period
lesson() { SQL "SELECT CONCAT(s.name,'|',COALESCE(CONCAT(tp.title,' '),''),u.first_name,' ',u.last_name) FROM schedule_entries se JOIN class_subjects cs ON cs.id=se.class_subject_id JOIN subjects s ON s.id=cs.subject_id JOIN users u ON u.id=cs.teacher_id LEFT JOIN teacher_profiles tp ON tp.user_id=u.id WHERE se.class_id=$XII1 AND se.day_of_week=$1 AND se.period_number=$2"; }

XII1=$(SQL "SELECT id FROM classes WHERE grade_level=12 AND section=1 AND academic_year_id=$YEAR")
TEACHER=$(SQL "SELECT id FROM users WHERE username='prove.mesimdhenes'")
CS_MINE=$(SQL "SELECT cs.id FROM class_subjects cs WHERE cs.class_id=$XII1 AND cs.teacher_id=$TEACHER")
CS_OTHER=$(SQL "SELECT cs.id FROM class_subjects cs WHERE cs.class_id=$XII1 AND cs.teacher_id<>$TEACHER LIMIT 1")
STUDENT_ID=$(SQL "SELECT id FROM users WHERE username='ariana.gashi'")

S="$T/student"; login $S ariana.gashi Nxenes-Demo-2026 >/dev/null
M="$T/teacher"; login $M prove.mesimdhenes Prove-Mesimdhenes-2026 >/dev/null
A="$T/admin"; login $A prove.admin Prove-Admin-2026 >/dev/null

echo "== Who sees which timetable =="
check "student → teacher's Orari is forbidden"               "403" "$(code $S /mesimdhenesi/orari)"
check "teacher → student's Orari is forbidden"               "403" "$(code $M /nxenesi/orari)"
check "teacher → admin's view of a teacher is forbidden"     "403" "$(code $M /admin/orari/mesimdhenesi/$TEACHER)"
check "guest → sign-in page"                                 "302" "$(curl -s -o /dev/null -w '%{http_code}' $B/nxenesi/orari)"

echo; echo "== The student's timetable (Ariana Gashi, XII-1) =="
check "Orari"                                                "200" "$(code $S /nxenesi/orari)"
contains "…class and shift"                                  "$T/body" "Klasa XII-1 · Paradite"
contains "…homeroom teacher"                                 "$T/body" "Kujdestari: Prof. Enver Bajrami"
contains "…30 lessons a week"                                "$T/body" "30 orë në javë"
contains "…the week grid"                                    "$T/body" "Orari javor i klasës XII-1"
check "…all 30 lessons are in the grid"                      "30" "$(sed -n '/class="schedule-week"/,$p' $T/body | grep -c 'class="timetable__subject"')"
IFS='|' read -r SUBJ2 TEACH2 <<< "$(lesson 1 2)"
code $S "/nxenesi/orari?tani=2026-09-28T09:10" >/dev/null
contains "Monday 09:10: the lesson in progress"              "$T/body" "Tani · Ora 2 · 08:50–09:35"
contains "…its subject"                                      "$T/body" "now__subject\" id=\"now-title\">$SUBJ2<"
contains "…and its teacher"                                  "$T/body" "$TEACH2"
contains "…25 minutes left"                                  "$T/body" "Edhe 25 minuta"
contains "…Monday's tab opens first on phones"               "$T/body" 'id="dita-tab-1" aria-controls="dita-1"[^>]*aria-selected="true"'
IFS='|' read -r SUBJ3 TEACH3 <<< "$(lesson 1 3)"
code $S "/nxenesi/orari?tani=2026-09-28T09:40" >/dev/null
contains "in the big break: the next lesson"                 "$T/body" "Në vazhdim · Ora 3 · 09:45–10:30"
contains "…starting at 09:45"                                "$T/body" "Fillon në 09:45."
code $S "/nxenesi/orari?tani=2026-09-28T14:00" >/dev/null
contains "after the last lesson"                             "$T/body" "Mësimi për sot mbaroi."
code $S "/nxenesi/orari?tani=2026-09-30T10:40" >/dev/null
contains "Wednesday: Wednesday's tab opens first"            "$T/body" 'id="dita-tab-3" aria-controls="dita-3"[^>]*aria-selected="true"'
contains "…marked as today"                                  "$T/body" "E mërkurë · sot"
code $S "/nxenesi/orari?tani=2026-10-03T10:00" >/dev/null
contains "Saturday: no school"                               "$T/body" "Sot nuk ka mësim."
contains "…lessons resume on Monday"                         "$T/body" "Mësimi rifillon të hënën."
contains "…and Monday's tab opens first"                     "$T/body" 'id="dita-tab-1" aria-controls="dita-1"[^>]*aria-selected="true"'
check "a malformed ?tani= is ignored"                        "200" "$(code $S '/nxenesi/orari?tani=bad')"
check "student dashboard at Monday 09:10"                    "200" "$(code $S '/nxenesi?tani=2026-09-28T09:10')"
contains "…shows the lesson in progress"                     "$T/body" "Tani · Ora 2"
contains "…and today's lessons"                              "$T/body" 'id="today-title">Sot<'

echo; echo "== The student's subjects =="
check "Lëndët"                                               "200" "$(code $S /nxenesi/lendet)"
contains "…12 subjects, 30 hours"                            "$T/body" "12 lëndë · 30 orë në javë"
contains "…grade XII's own subject"                          "$T/body" "Astronomi"
contains "…with its teacher"                                 "$T/body" "Provë Mësimdhënëse"
contains "…and the days it is taught"                        "$T/body" "Hën"

echo; echo "== When the timetable changes =="
code $A /admin/orari/klasa/$XII1 >/dev/null
FULL=(); while read -r line; do FULL+=(--data-urlencode "$line"); done < <(SQL "SELECT CONCAT('cell[',day_of_week,'][',period_number,']=',class_subject_id) FROM schedule_entries WHERE class_id=$XII1")
ARGS=(); while read -r line; do ARGS+=(--data-urlencode "$line"); done < <(SQL "SELECT CONCAT('cell[',day_of_week,'][',period_number,']=',class_subject_id) FROM schedule_entries WHERE class_id=$XII1 AND NOT (day_of_week=5 AND period_number=6)")
post $A /admin/orari/klasa/$XII1 /admin/orari/klasa/$XII1 "${ARGS[@]}" >/dev/null
code $S /nxenesi/orari >/dev/null
contains "the student is told the timetable changed"         "$T/body" "Orari i klasës suaj ndryshoi"
contains "…and the week now has 29 lessons"                  "$T/body" "29 orë në javë"
code $S /nxenesi/orari >/dev/null
check "…opening the page (GET) changes nothing"             "1" "$(SQL "SELECT COUNT(*) FROM notifications WHERE user_id=$STUDENT_ID AND type='schedule.changed' AND read_at IS NULL")"
check "“E pashë” marks it as seen"                           "302 /nxenesi/orari" "$(post $S /nxenesi/orari/lexuar /nxenesi/orari)"
check "…no unread note left"                                 "0" "$(SQL "SELECT COUNT(*) FROM notifications WHERE user_id=$STUDENT_ID AND type='schedule.changed' AND read_at IS NULL")"
code $S /nxenesi/orari >/dev/null
lacks "…and the note is gone"                                "$T/body" "Orari i klasës suaj ndryshoi"
check "the note cannot be dismissed without a token"        "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $S -X POST $B/nxenesi/orari/lexuar)"
post $A /admin/orari/klasa/$XII1 /admin/orari/klasa/$XII1 "${FULL[@]}" >/dev/null
check "the lesson is put back (30 again)"                    "30" "$(SQL "SELECT COUNT(*) FROM schedule_entries WHERE class_id=$XII1")"

echo; echo "== A student without a class =="
U="$T/noclass"; login $U prove.nxenes Prove-Nxenes-2026 >/dev/null
code $U /nxenesi/orari >/dev/null
contains "Orari explains why it is empty"                    "$T/body" "Ende nuk jeni regjistruar në një klasë."
code $U /nxenesi/lendet >/dev/null
contains "…and so does Lëndët"                               "$T/body" "Ende nuk jeni regjistruar në një klasë."

echo; echo "== The teacher's timetable (Provë Mësimdhënëse) =="
check "Orari"                                                "200" "$(code $M /mesimdhenesi/orari)"
contains "…12 lessons, 3 classes, both shifts"               "$T/body" "12 orë në javë · 3 klasa · paradite dhe pasdite"
contains "…a morning grid"                                   "$T/body" "Orari javor · paradite"
contains "…and an afternoon grid"                            "$T/body" "Orari javor · pasdite"
contains "…lessons show the class"                           "$T/body" "X-13"
contains "…read-only"                                        "$T/body" "Orari është i administratës"
# The test teacher's first afternoon lesson of the week; at 13:50 that day the morning is over
read -r AD AP AS AE <<< "$(SQL "SELECT se.day_of_week, se.period_number, TIME_FORMAT(p.starts_at,'%H:%i'), TIME_FORMAT(p.ends_at,'%H:%i') FROM schedule_entries se JOIN classes c ON c.id=se.class_id JOIN class_subjects cs ON cs.id=se.class_subject_id JOIN lesson_periods p ON p.shift=c.shift AND p.number=se.period_number WHERE cs.teacher_id=$TEACHER AND c.shift=2 ORDER BY se.day_of_week, se.period_number LIMIT 1")"
AFTERNOON="2026-09-$((27 + AD))T13:50"
code $M "/mesimdhenesi/orari?tani=$AFTERNOON" >/dev/null
contains "13:50: next is the afternoon class"               "$T/body" "Në vazhdim · Ora $AP · $AS–$AE"
contains "…X-13"                                             "$T/body" 'id="now-title">X-13<'
check "teacher dashboard that day at 13:50"                 "200" "$(code $M "/mesimdhenesi?tani=$AFTERNOON")"
contains "…today's lessons"                                  "$T/body" "Orët e sotme"
contains "…with the afternoon class"                         "$T/body" "X-13"

echo; echo "== The teacher's classes =="
check "Klasat"                                               "200" "$(code $M /mesimdhenesi/klasat)"
contains "…3 subjects, 12 hours"                             "$T/body" "3 lëndë në klasa · 12 orë në javë"
contains "…XII-1"                                            "$T/body" ">XII-1<"
contains "…XI-5"                                             "$T/body" ">XI-5<"
check "one of them: XII-1 · Matematikë"                      "200" "$(code $M /mesimdhenesi/klasat/$CS_MINE)"
contains "…titled with class and subject"                    "$T/body" "XII-1 · Matematikë"
contains "…lists the students"                               "$T/body" "Ariana Gashi"
check "…and its 4 lessons in the week"                       "4" "$(grep -c 'class="item__title">E ' $T/body)"
check "another teacher's class-subject → 404"                "404" "$(code $M /mesimdhenesi/klasat/$CS_OTHER)"
check "an unknown one → 404"                                 "404" "$(code $M /mesimdhenesi/klasat/999999)"
check "a student opening it → 403"                           "403" "$(code $S /mesimdhenesi/klasat/$CS_MINE)"

echo; echo "== The admin checking a teacher's week =="
check "admin → the teacher's week"                           "200" "$(code $A /admin/orari/mesimdhenesi/$TEACHER)"
contains "…titled with the teacher"                          "$T/body" "Orari i Provë Mësimdhënëse"
contains "…12 lessons"                                       "$T/body" "12 orë në javë"
check "…a student's id → 404"                                "404" "$(code $A /admin/orari/mesimdhenesi/$STUDENT_ID)"
code $A /admin/perdoruesit/$TEACHER/ndrysho >/dev/null
contains "the teacher's page links to the week"              "$T/body" "/admin/orari/mesimdhenesi/$TEACHER"

restore
echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
