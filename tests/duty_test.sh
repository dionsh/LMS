#!/usr/bin/env bash
# End-to-end tests for the daily duty (kujdestaria e ditës, T08b): the
# morning roster imported from the official timetable, the admin grid and its
# rules, the posts, the whole-school sheet and the teacher's own duty days.
# Development database only; everything it changes is restored at the end.
#
#   bash tests/duty_test.sh
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1"; }
YEAR="(SELECT id FROM academic_years WHERE is_current = 1)"

restore() {
  SQL "DELETE FROM duty_assignments;
       DELETE FROM duty_posts WHERE name LIKE 'Test%';
       UPDATE duty_posts SET places = 2 WHERE sort_order = 4;"   # SQL literals stay ASCII (tests/README.md)
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
code()   { curl -s -o "$T/body" -w "%{http_code}" -b "$1" -c "$1" "$B$2"; }
login()  { local t; t=$(token "$1" /hyr); curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" \
             --data-urlencode "_token=$t" --data-urlencode "login=$2" --data-urlencode "password=$3" "$B/hyr" | sed "s#$B##"; }
post()   { local jar=$1 path=$2 page=$3; shift 3; local t; t=$(token "$jar" "$page")
           curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$jar" -c "$jar" --data-urlencode "_token=$t" "$@" "$B$path" | sed "s#$B##"; }
# The morning roster as it is now, as form fields "d1-p2-1=<teacher id>"
fields() { SQL "SELECT CONCAT('d',day_of_week,'-p',duty_post_id,'-',place,'=',teacher_id) FROM duty_assignments WHERE shift = $1 AND academic_year_id = $YEAR"; }
save()   { local shift=$1 file=$2; shift 2; local args=(); while read -r line; do args+=(--data-urlencode "$line"); done < "$file"
           post $A "/admin/kujdestaria/$shift" "/admin/kujdestaria?ndrrimi=$shift" "${args[@]}" "$@"; }
roster() { SQL "SELECT GROUP_CONCAT(CONCAT(a.day_of_week,':',p.name,':',tp.timetable_number) ORDER BY a.day_of_week, p.sort_order, a.place SEPARATOR ' | ') FROM duty_assignments a JOIN duty_posts p ON p.id=a.duty_post_id JOIN teacher_profiles tp ON tp.user_id=a.teacher_id WHERE a.shift = 1"; }

A="$T/admin"; login $A prove.admin Prove-Admin-2026 >/dev/null
S="$T/student"; login $S ariana.gashi Nxenes-Demo-2026 >/dev/null
M="$T/teacher"; login $M prove.mesimdhenes Prove-Mesimdhenes-2026 >/dev/null
LOG_START=$(SQL "SELECT COALESCE(MAX(id), 0) FROM activity_log")
POST_HALL=$(SQL "SELECT id FROM duty_posts WHERE name = 'Salla'")
POST_F1=$(SQL "SELECT id FROM duty_posts WHERE name LIKE 'Kati i par%'")
TEST_T=$(SQL "SELECT id FROM users WHERE username = 'prove.mesimdhenes'")
NASER=$(SQL "SELECT user_id FROM teacher_profiles WHERE timetable_number = 27")
STUDENT=$(SQL "SELECT id FROM users WHERE username = 'ariana.gashi'")

echo "== Who may see and change it =="
check "student → /admin/kujdestaria is forbidden"            "403" "$(code $S /admin/kujdestaria)"
check "teacher → /admin/kujdestaria is forbidden"            "403" "$(code $M /admin/kujdestaria)"
check "saving without CSRF token is refused"                 "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $A --data "d1-p$POST_HALL-1=$NASER" $B/admin/kujdestaria/1)"
check "unknown shift → 404"                                  "404 " "$(post $A /admin/kujdestaria/3 /admin/kujdestaria)"

echo; echo "== The morning roster from the official timetable =="
check "the hall and three floors, 2 teachers per floor"      "Salla:1 Kati i parë:2 Kati i dytë:2 Kati i tretë:2" "$(SQL "SELECT GROUP_CONCAT(CONCAT(name,':',places) ORDER BY sort_order SEPARATOR ' ') FROM duty_posts")"
check "31 places filled (4 are empty on the sheet)"          "31" "$(SQL "SELECT COUNT(*) FROM duty_assignments WHERE shift = 1")"
check "Monday: 27 · 36, 70 · 63, 42 · 29, 47"                "1:Salla:27 | 1:Kati i parë:36 | 1:Kati i parë:70 | 1:Kati i dytë:63 | 1:Kati i dytë:42 | 1:Kati i tretë:29 | 1:Kati i tretë:47" "$(roster | awk -F' [|] 2:' '{print $1}')"
check "Friday's hall: Ylber Ukshini (44)"                    "Ylber Ukshini" "$(SQL "SELECT CONCAT(u.first_name,' ',u.last_name) FROM duty_assignments a JOIN duty_posts p ON p.id=a.duty_post_id JOIN users u ON u.id=a.teacher_id WHERE a.shift=1 AND a.day_of_week=5 AND p.name='Salla'")"
check "no afternoon roster yet"                              "0" "$(SQL "SELECT COUNT(*) FROM duty_assignments WHERE shift = 2")"
check "every duty teacher teaches that morning"              "0" "$(SQL "SELECT COUNT(*) FROM duty_assignments a JOIN teacher_profiles tp ON tp.user_id=a.teacher_id WHERE tp.timetable_number IN (12,16,18,19,20,21,23,31,33,34,37,59,62,64,73)")"

echo; echo "== The admin grid =="
check "duty page"                                            "200" "$(code $A /admin/kujdestaria)"
contains "…titled"                                           "$T/body" "Kujdestaria e ditës"
contains "…shows Monday's hall teacher selected"             "$T/body" "value=\"$NASER\" selected>27 · Naser Tahiri"
contains "…teachers with lessons that day come first"        "$T/body" 'optgroup label="Kanë mësim këtë ditë"'
check "afternoon page"                                       "200" "$(code $A '/admin/kujdestaria?ndrrimi=2')"
fields 1 > "$T/morning"
check "saving the same roster"                               "302 /admin/kujdestaria?ndrrimi=1" "$(save 1 "$T/morning")"
check "…nothing changes"                                     "31" "$(SQL "SELECT COUNT(*) FROM duty_assignments WHERE shift = 1")"
check "the same teacher twice on Monday → 422"              "422 " "$(save 1 "$T/morning" --data-urlencode "d1-p$POST_F1-1=$NASER")"
contains "…says who and when"                                "$T/body" "Naser Tahiri ka tashmë kujdestari të hënën."
check "…and nothing was saved"                               "31" "$(SQL "SELECT COUNT(*) FROM duty_assignments WHERE shift = 1")"
check "a student on duty → 422"                              "422 " "$(save 1 "$T/morning" --data-urlencode "d3-p$POST_HALL-1=$STUDENT")"
contains "…asks for a teacher"                               "$T/body" "Zgjidhni mësimdhënësin nga lista."
grep -v "^d3-p$POST_HALL-1=" "$T/morning" > "$T/changed"
echo "d3-p$POST_HALL-1=$TEST_T" >> "$T/changed"
check "Wednesday's hall → the test teacher"                  "302 /admin/kujdestaria?ndrrimi=1" "$(save 1 "$T/changed")"
check "…saved"                                               "$TEST_T" "$(SQL "SELECT teacher_id FROM duty_assignments WHERE shift=1 AND day_of_week=3 AND duty_post_id=$POST_HALL")"
echo "d2-p$POST_HALL-1=$TEST_T" > "$T/afternoon"
check "the afternoon: Tuesday's hall"                        "302 /admin/kujdestaria?ndrrimi=2" "$(save 2 "$T/afternoon")"
check "…saved for the afternoon only"                        "1 31" "$(SQL "SELECT CONCAT(SUM(shift=2),' ',SUM(shift=1)) FROM duty_assignments")"

echo; echo "== The whole-school sheet and the teacher =="
code $A /admin/orari >/dev/null
contains "the morning sheet shows the duty"                  "$T/body" "Kujdestaria e ditës"
contains "…with Monday's hall teacher"                       "$T/body" "Naser Tahiri"
code $M "/mesimdhenesi/orari?tani=2026-09-30T09:00" >/dev/null
contains "the teacher sees their duty days"                  "$T/body" "Kujdestaria e ditës"
contains "…Wednesday, the hall, morning"                     "$T/body" "E mërkurë</strong>"
contains "…marked as today on Wednesday"                     "$T/body" 'badge--info">Sot<'
code $M "/mesimdhenesi?tani=2026-09-30T09:00" >/dev/null
contains "the dashboard reminds them that day"               "$T/body" "Sot keni kujdestarinë e ditës: Salla."
code $M "/mesimdhenesi?tani=2026-09-28T09:00" >/dev/null
check "…and not on a day without duty"                       "0" "$(grep -c 'Sot keni kujdestarinë' $T/body)"

echo; echo "== The posts =="
check "add a post (Test Oborri, 1 place)"                    "302 /admin/kujdestaria" "$(post $A /admin/kujdestaria/vendet/shto /admin/kujdestaria --data-urlencode "name=Test Oborri" --data-urlencode places=1)"
check "…twice is refused"                                    "1" "$(post $A /admin/kujdestaria/vendet/shto /admin/kujdestaria --data-urlencode "name=Test Oborri" --data-urlencode places=1 >/dev/null; SQL "SELECT COUNT(*) FROM duty_posts WHERE name='Test Oborri'")"
check "…7 places are too many"                               "0" "$(post $A /admin/kujdestaria/vendet/shto /admin/kujdestaria --data-urlencode "name=Test Shumë" --data-urlencode places=7 >/dev/null; SQL "SELECT COUNT(*) FROM duty_posts WHERE name='Test Shumë'")"
YARD=$(SQL "SELECT id FROM duty_posts WHERE name='Test Oborri'")
code $A /admin/kujdestaria >/dev/null
contains "…the grid has a row for it"                        "$T/body" "name=\"d1-p$YARD-1\""
check "an unused post can be removed"                        "302 /admin/kujdestaria" "$(post $A /admin/kujdestaria/vendet/$YARD/fshij /admin/kujdestaria)"
check "…gone"                                                "0" "$(SQL "SELECT COUNT(*) FROM duty_posts WHERE id=$YARD")"
POST_F3=$(SQL "SELECT id FROM duty_posts WHERE sort_order = 4")
check "a post in use cannot be removed"                      "1" "$(post $A /admin/kujdestaria/vendet/$POST_F3/fshij /admin/kujdestaria >/dev/null; SQL "SELECT COUNT(*) FROM duty_posts WHERE id=$POST_F3")"
check "one place fewer on the 3rd floor"                     "302 /admin/kujdestaria" "$(post $A /admin/kujdestaria/vendet/$POST_F3 /admin/kujdestaria --data "name=Kati%20i%20tret%C3%AB" --data-urlencode places=1)"
check "…its 2nd-place teachers are taken off"               "0" "$(SQL "SELECT COUNT(*) FROM duty_assignments WHERE duty_post_id=$POST_F3 AND place=2")"

echo; echo "== Activity log =="
check "roster and post changes are logged"                   "3" "$(SQL "SELECT COUNT(DISTINCT action) FROM activity_log WHERE id > $LOG_START AND action IN ('duty.updated','duty.post_created','duty.post_deleted')")"

restore
echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
