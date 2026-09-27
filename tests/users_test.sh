#!/usr/bin/env bash
# End-to-end tests for user management (T05): lists, adding and editing
# accounts, login slips (single, per class, all teachers), activation.
# Development database only; everything it changes is restored at the end.
#
#   bash tests/users_test.sh
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1"; }

restore() {
  SQL "DELETE FROM login_attempts WHERE identifier LIKE 'test.%';
       DELETE FROM users WHERE username LIKE 'test.%';
       UPDATE users SET password_hash = NULL, must_change_password = 1, last_login_at = NULL
        WHERE role = 'teacher' AND username <> 'prove.mesimdhenes';"
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
slips_in() { grep -c 'class="slip"' "$1"; }
first_password() { grep -oE 'slip__password">[^<]+' "$1" | head -1 | sed 's/.*">//'; }

A="$T/admin"; login $A prove.admin Prove-Admin-2026 >/dev/null
LOG_START=$(SQL "SELECT COALESCE(MAX(id), 0) FROM activity_log")   # only this run's log lines are counted
CLASS_XII1=$(SQL "SELECT id FROM classes WHERE grade_level=12 AND section=1")
CLASS_XI5=$(SQL "SELECT id FROM classes WHERE grade_level=11 AND section=5")
ENVER=$(SQL "SELECT id FROM users WHERE username='enver.bajrami'")
ADMIN=$(SQL "SELECT id FROM users WHERE username='prove.admin'")

echo "== Who may manage accounts =="
S="$T/student"; login $S prove.nxenes Prove-Nxenes-2026 >/dev/null
check "student → /admin/nxenesit is forbidden"            "403" "$(code $S /admin/nxenesit)"
M="$T/teacher"; login $M prove.mesimdhenes Prove-Mesimdhenes-2026 >/dev/null
check "teacher cannot issue teacher slips"                  "403 " "$(post $M /admin/mesimdhenesit/fletet /mesimdhenesi)"
check "guest → sign-in page"                                "302 /hyr" "$(go $T/guest /admin/perdoruesit)"
check "adding without CSRF token is refused"                "403" "$(curl -s -o /dev/null -w '%{http_code}' -b $A --data 'first_name=X&last_name=Y' $B/admin/nxenesit/shto)"

echo; echo "== Lists, search, filters, pages =="
code $A /admin/nxenesit >/dev/null
contains "students list counts everyone"                    "$T/body" "13 nxënës"
code $A "/admin/nxenesit?klasa=$CLASS_XII1" >/dev/null
contains "filter by class XII-1"                            "$T/body" "Nxënësit e klasës XII-1"
contains "…shows its 4 students"                            "$T/body" "4 nxënës"
code $A "/admin/nxenesit?q=gashi" >/dev/null
contains "search by surname finds Ariana Gashi"             "$T/body" "ariana.gashi"
code $A "/admin/nxenesit?gjendja=joaktiv" >/dev/null
contains "status filter finds the inactive account"         "$T/body" "prove.joaktiv"
code $A "/admin/mesimdhenesit?llogaria=pa-flete" >/dev/null
NOSLIP=$(SQL "SELECT COUNT(*) FROM users WHERE role='teacher' AND password_hash IS NULL")
contains "all $NOSLIP teachers without a login slip"          "$T/body" "$NOSLIP mësimdhënës"
contains "…shown in pages of 50"                            "$T/body" "Po shfaqen 1–50 nga $NOSLIP"
contains "…with a link to page 2 keeping the filter"        "$T/body" "llogaria=pa-flete&amp;faqja=2"
code $A "/admin/mesimdhenesit?llogaria=pa-flete&faqja=2" >/dev/null
contains "page 2"                                           "$T/body" "Po shfaqen 51–100 nga $NOSLIP"
code $A "/admin/nxenesit?q=%25" >/dev/null
contains "a % in the search is literal (no match-all)"      "$T/body" "Asnjë nxënës nuk përputhet"

echo; echo "== Adding a student =="
check "empty form → 422"                                    "422 " "$(post $A /admin/nxenesit/shto /admin/nxenesit/shto --data-urlencode first_name= --data-urlencode last_name=)"
contains "…asks for the first name"                         "$T/body" "Shkruani emrin."
contains "…asks for the class"                              "$T/body" "Zgjidhni klasën e nxënësit."
check "birth date in the future → 422"                      "422 " "$(post $A /admin/nxenesit/shto /admin/nxenesit/shto --data first_name=T%C3%ABst --data last_name=Nx%C3%ABn%C3%ABsi --data-urlencode class_id=$CLASS_XII1 --data-urlencode date_of_birth=2031-01-01)"
contains "…explains the date"                               "$T/body" "Shkruani një datë të vlefshme lindjeje."
check "malformed (non-UTF-8) text → normal 422, never 500"   "422 " "$(post $A /admin/nxenesit/shto /admin/nxenesit/shto --data first_name=T%EBst --data last_name=)"
check "class from outside the list → 422"                   "422 " "$(post $A /admin/nxenesit/shto /admin/nxenesit/shto --data first_name=T%C3%ABst --data last_name=Nx%C3%ABn%C3%ABsi --data-urlencode class_id=999999)"
r=$(post $A /admin/nxenesit/shto /admin/nxenesit/shto --data "first_name=T%C3%ABst" --data "last_name=Nx%C3%ABn%C3%ABsi" --data-urlencode "class_id=$CLASS_XII1" --data-urlencode "date_of_birth=2009-02-03" --data-urlencode "gender=F" --data-urlencode "student_number=T-001" --data-urlencode "issue_slip=1")
check "valid student with slip → printable slip page"      "302 /admin/fletet-e-hyrjes/" "$(echo "$r" | sed -E 's#[a-f0-9]+$##')"
SLIP_URL=$(echo "$r" | awk '{print $2}')
code $A "$SLIP_URL" >/dev/null
contains "slip shows the generated username"                "$T/body" "test.nxenesi"
contains "slip shows the class"                             "$T/body" "Nxënës · Klasa XII-1"
contains "slip shows the full sign-in address"              "$T/body" "$B/hyr"
PASSWORD=$(first_password "$T/body")
check "slip carries a temporary password"                   "yes" "$(echo "$PASSWORD" | grep -qE '^[a-z]+-[a-z]+-[a-z]+-[0-9]{2}$' && echo yes || echo no)"
check "student enrolled in XII-1 with profile"              "XII-1 T-001 F" "$(SQL "SELECT CONCAT(ELT(c.grade_level-9,'X','XI','XII'),'-',c.section,' ',sp.student_number,' ',sp.gender) FROM users u JOIN enrollments e ON e.student_id=u.id JOIN classes c ON c.id=e.class_id JOIN student_profiles sp ON sp.user_id=u.id WHERE u.username='test.nxenesi'")"
N="$T/new"
check "the new student signs in with the slip"             "302 /ndrysho-fjalekalimin" "$(login $N test.nxenesi "$PASSWORD")"
A2="$T/admin2"; login $A2 prove.admin Prove-Admin-2026 >/dev/null
check "another admin session cannot see these slips"       "404" "$(code $A2 "$SLIP_URL")"
check "'Mbaro' clears the slips and returns"               "302 /admin/perdoruesit/$(SQL "SELECT id FROM users WHERE username='test.nxenesi'")/ndrysho" "$(post $A "$SLIP_URL/mbaro" "$SLIP_URL")"
check "…after which the slip page is gone"                 "404" "$(code $A "$SLIP_URL")"
check "issued password never lands in the activity log"    "0" "$([ -n "$PASSWORD" ] && SQL "SELECT COUNT(*) FROM activity_log WHERE description LIKE '%$PASSWORD%'" || echo 'no password captured')"

r=$(post $A /admin/nxenesit/shto /admin/nxenesit/shto --data "first_name=T%C3%ABst" --data "last_name=Paflet%C3%AB" --data-urlencode "class_id=$CLASS_XI5")
check "student without a slip → saved, to the edit page"   "302" "$(echo "$r" | awk '{print $1}')"
check "…has no credentials"                                 "1" "$(SQL "SELECT password_hash IS NULL FROM users WHERE username='test.paflete'")"
check "duplicate student number → 422"                      "422 " "$(post $A /admin/nxenesit/shto /admin/nxenesit/shto --data first_name=T%C3%ABst --data last_name=Dyfish --data-urlencode class_id=$CLASS_XI5 --data-urlencode student_number=T-001)"
contains "…says the number belongs to another student"     "$T/body" "Ky numër amze i përket një nxënësi tjetër."

echo; echo "== Editing =="
NEW=$(SQL "SELECT id FROM users WHERE username='test.nxenesi'")
check "edit page"                                           "200" "$(code $A /admin/perdoruesit/$NEW/ndrysho)"
check "move to XI-5 and rename"                             "302 /admin/perdoruesit/$NEW/ndrysho" "$(post $A /admin/perdoruesit/$NEW/ndrysho /admin/perdoruesit/$NEW/ndrysho --data first_name=T%C3%ABsta --data last_name=Nx%C3%ABn%C3%ABsi --data-urlencode class_id=$CLASS_XI5 --data-urlencode student_number=T-001 --data-urlencode gender=F --data-urlencode date_of_birth=2009-02-03)"
check "…saved in the database"                              "Tësta XI-5" "$(SQL "SELECT CONCAT(u.first_name,' ',ELT(c.grade_level-9,'X','XI','XII'),'-',c.section) FROM users u JOIN enrollments e ON e.student_id=u.id JOIN classes c ON c.id=e.class_id WHERE u.id=$NEW")"
check "…username stays the same"                            "test.nxenesi" "$(SQL "SELECT username FROM users WHERE id=$NEW")"
check "teacher profile fields save"                         "302 /admin/perdoruesit/$ENVER/ndrysho" "$(post $A /admin/perdoruesit/$ENVER/ndrysho /admin/perdoruesit/$ENVER/ndrysho --data-urlencode first_name=Enver --data-urlencode last_name=Bajrami --data-urlencode title=Prof. --data specialization=Matematik%C3%AB --data-urlencode show_on_website=1 --data-urlencode timetable_number=26 --data-urlencode weekly_norm=20)"
check "…specialization stored"                              "Matematikë 1" "$(SQL "SELECT CONCAT(specialization,' ',show_on_website) FROM teacher_profiles WHERE user_id=$ENVER")"
code $A /admin/perdoruesit/$ENVER/ndrysho >/dev/null
contains "teacher edit page shows the homeroom class"      "$T/body" "Kujdestar i klasës XII-1"

echo; echo "== Login slips for teachers =="
r=$(post $A /admin/perdoruesit/$ENVER/fleta /admin/perdoruesit/$ENVER/ndrysho)
code $A "$(echo "$r" | awk '{print $2}')" >/dev/null
contains "one teacher's slip"                               "$T/body" "enver.bajrami"
EP=$(first_password "$T/body")
E="$T/enver"
check "Enver Bajrami can now sign in"                       "302 /ndrysho-fjalekalimin" "$(login $E enver.bajrami "$EP")"
WAIT=$(SQL "SELECT COUNT(*) FROM users WHERE role='teacher' AND status='active' AND password_hash IS NULL")
r=$(post $A /admin/mesimdhenesit/fletet /admin/mesimdhenesit)
code $A "$(echo "$r" | awk '{print $2}')" >/dev/null
check "all remaining teachers get a slip in one step"      "$WAIT" "$(slips_in "$T/body")"
check "…no teacher is left without credentials"            "0" "$(SQL "SELECT COUNT(*) FROM users WHERE role='teacher' AND password_hash IS NULL")"
check "…one log line for the whole batch"                  "1" "$(SQL "SELECT COUNT(*) FROM activity_log WHERE id > $LOG_START AND description LIKE 'L%shoi $WAIT flet% hyrjeje.'")"

echo; echo "== Login slips for a class =="
CW=$(SQL "SELECT COUNT(*) FROM users u JOIN enrollments e ON e.student_id=u.id WHERE e.class_id=$CLASS_XII1 AND u.status='active' AND u.last_login_at IS NULL")
r=$(post $A /admin/nxenesit/fletet "/admin/nxenesit?klasa=$CLASS_XII1" --data-urlencode class_id=$CLASS_XII1)
code $A "$(echo "$r" | awk '{print $2}')" >/dev/null
check "slips only for XII-1 students not yet signed in"    "$CW" "$(slips_in "$T/body")"
contains "…titled with the class"                           "$T/body" "Fletët e hyrjes · Klasa XII-1"

echo; echo "== Activation and safeguards =="
check "deactivate a student"                                "302 /admin/perdoruesit/$NEW/ndrysho" "$(post $A /admin/perdoruesit/$NEW/statusi /admin/perdoruesit/$NEW/ndrysho --data-urlencode status=inactive)"
check "…inactive in the database"                           "inactive" "$(SQL "SELECT status FROM users WHERE id=$NEW")"
check "…and signed out on their next click"                 "302 /hyr" "$(go $N /ndrysho-fjalekalimin)"
post $A /admin/perdoruesit/$NEW/fleta /admin/perdoruesit/$NEW/ndrysho >/dev/null
code $A /admin/perdoruesit/$NEW/ndrysho >/dev/null
contains "no slip for an inactive account"                  "$T/body" "Aktivizoni llogarinë para se të lëshoni"
check "activate again"                                      "active" "$(post $A /admin/perdoruesit/$NEW/statusi /admin/perdoruesit/$NEW/ndrysho --data-urlencode status=active >/dev/null; SQL "SELECT status FROM users WHERE id=$NEW")"
post $A /admin/perdoruesit/$ADMIN/statusi /admin/perdoruesit/$ADMIN/ndrysho --data-urlencode status=inactive >/dev/null
check "an admin cannot deactivate themselves"               "active" "$(SQL "SELECT status FROM users WHERE id=$ADMIN")"
code $A /admin/perdoruesit/$ADMIN/ndrysho >/dev/null
contains "…and is told why"                                 "$T/body" "Nuk mund ta çaktivizoni llogarinë tuaj."
post $A /admin/perdoruesit/$ADMIN/fleta /admin/perdoruesit/$ADMIN/ndrysho >/dev/null
code $A /admin/perdoruesit/$ADMIN/ndrysho >/dev/null
contains "no slip for yourself (use the profile)"           "$T/body" "Për llogarinë tuaj përdorni faqen e profilit"
r=$(post $A /admin/perdoruesit/shto /admin/perdoruesit/shto --data "first_name=T%C3%ABst" --data "last_name=Admin" --data-urlencode "issue_slip=1")
check "adding an administrator → slip"                      "302 /admin/fletet-e-hyrjes/" "$(echo "$r" | sed -E 's#[a-f0-9]+$##')"
check "…is an admin who must choose a password"            "admin 1" "$(SQL "SELECT CONCAT(role,' ',must_change_password) FROM users WHERE username='test.admin'")"
check "every change is in the activity log"                 "4" "$(SQL "SELECT COUNT(DISTINCT action) FROM activity_log WHERE id > $LOG_START AND action IN ('user.created','user.updated','user.credentials_issued','user.deactivated')")"

restore
echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
