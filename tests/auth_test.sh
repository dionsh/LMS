#!/usr/bin/env bash
# End-to-end tests for signing in, roles and passwords (T04), over real HTTP.
# Development database only: it signs in with the test accounts from
# database/demo/test-accounts.php and resets them before and after.
#
#   bash tests/auth_test.sh
#
# Settings (defaults fit XAMPP on Windows with Git Bash):
#   BASE_URL, PHP_BIN, MYSQL_BIN, DB_NAME
cd "$(dirname "$0")/.." || exit 1
B="${BASE_URL:-http://localhost/lms-system}"
PHP="${PHP_BIN:-/c/xampp_ick/php/php.exe}"
MYSQL="${MYSQL_BIN:-/c/xampp_ick/mysql/bin/mysql.exe}"
SQL() { "$MYSQL" -u root --default-character-set=utf8mb4 -N "${DB_NAME:-kuvendi_lms}" -e "$1"; }
"$PHP" database/demo/test-accounts.php > /dev/null || { echo "Could not reset the test accounts"; exit 1; }

T=$(mktemp -d)
pass=0; fail=0

check() { # label expected actual
  if [ "$2" = "$3" ]; then pass=$((pass+1)); printf "PASS  %-62s %s\n" "$1" "$3";
  else fail=$((fail+1)); printf "FAIL  %-62s got '%s' expected '%s'\n" "$1" "$3" "$2"; fi; }
contains() { # label file needle
  if grep -q "$3" "$2"; then pass=$((pass+1)); printf "PASS  %-62s contains \"%s\"\n" "$1" "$3";
  else fail=$((fail+1)); printf "FAIL  %-62s missing \"%s\"\n" "$1" "$3"; fi; }

token()  { curl -s -b "$1" -c "$1" "$B$2" | grep -oE 'name="_token" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)"/\1/'; }
go()     { curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" "$B$2" | sed "s#$B##"; }
code()   { curl -s -o "$T/body" -w "%{http_code}" -b "$1" -c "$1" "$B$2"; }
login()  { local t; t=$(token "$1" /hyr); curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$1" -c "$1" \
             --data-urlencode "_token=$t" --data-urlencode "login=$2" --data-urlencode "password=$3" "$B/hyr" | sed "s#$B##"; }
post()   { # jar path page-for-token data...
  local jar=$1 path=$2 page=$3; shift 3; local t; t=$(token "$jar" "$page")
  curl -s -o "$T/body" -w "%{http_code} %{redirect_url}" -b "$jar" -c "$jar" --data-urlencode "_token=$t" "$@" "$B$path" | sed "s#$B##"; }
sid()    { grep kai_session "$1" | awk '{print $7}'; }

echo "== Guests =="
G="$T/guest"
check "guest → /nxenesi redirects to sign-in"            "302 /hyr" "$(go $G /nxenesi)"
check "guest → /admin redirects to sign-in"              "302 /hyr" "$(go $G /admin)"
check "guest → /profili redirects to sign-in"            "302 /hyr" "$(go $G /profili)"
check "sign-in page"                                     "200"      "$(code $G /hyr)"
contains "sign-in page explains the redirect"            "$T/body"  "Hyni për të vazhduar"
check "POST /hyr without CSRF token is refused"          "403"      "$(curl -s -o /dev/null -w '%{http_code}' --data 'login=a&password=b' $B/hyr)"

echo; echo "== Student =="
S="$T/student"; code $S /hyr >/dev/null; before=$(sid $S)
check "student signs in → student dashboard"             "302 /nxenesi" "$(login $S prove.nxenes Prove-Nxenes-2026)"
after=$(sid $S)
check "session id changes at sign-in (no fixation)"      "changed"  "$([ "$before" != "$after" ] && echo changed || echo same)"
check "student dashboard"                                "200"      "$(code $S /nxenesi)"
contains "dashboard greets the student by name"          "$T/body"  ", Provë."
check "dashboard is not cached (Cache-Control)"          "no-store" "$(curl -s -D - -o /dev/null -b $S $B/nxenesi | grep -i '^cache-control' | tr -d '\r' | awk '{print $2}')"
check "student → /admin is forbidden"                    "403"      "$(code $S /admin)"
check "student → /mesimdhenesi is forbidden"             "403"      "$(code $S /mesimdhenesi)"
check "student → /hyr goes to own dashboard"             "302 /nxenesi" "$(go $S /hyr)"
check "/paneli → own dashboard"                          "302 /nxenesi" "$(go $S /paneli)"
check "profile page"                                     "200"      "$(code $S /profili)"

echo; echo "== Teacher and admin =="
M="$T/teacher"; A="$T/admin"
check "teacher signs in → teacher dashboard"             "302 /mesimdhenesi" "$(login $M prove.mesimdhenes Prove-Mesimdhenes-2026)"
check "teacher dashboard"                                "200"      "$(code $M /mesimdhenesi)"
check "teacher → /admin is forbidden"                    "403"      "$(code $M /admin)"
check "teacher → /nxenesi is forbidden"                  "403"      "$(code $M /nxenesi)"
check "admin signs in → admin dashboard"                 "302 /admin" "$(login $A prove.admin Prove-Admin-2026)"
check "admin dashboard"                                  "200"      "$(code $A /admin)"
contains "admin dashboard shows the setup checklist"     "$T/body"  "Përgatitja e shkollës"
check "admin → /nxenesi is forbidden"                    "403"      "$(code $A /nxenesi)"
check "admin → /mesimdhenesi is forbidden"               "403"      "$(code $A /mesimdhenesi)"

echo; echo "== Sign-in details =="
I="$T/intended"
go $I /profili >/dev/null
check "after sign-in, back to the page first requested"  "302 /profili" "$(login $I prove.nxenes Prove-Nxenes-2026)"
C="$T/case"
check "username is case-insensitive"                     "302 /nxenesi" "$(login $C PROVE.NXENES Prove-Nxenes-2026)"
W="$T/wrong"
check "wrong password → 422"                             "422 "     "$(login $W prove.nxenes wrong-password-1)"
contains "wrong password → generic message"              "$T/body"  "Emri i përdoruesit ose fjalëkalimi është i pasaktë"
check "unknown account → same 422"                       "422 "     "$(login $W nobody.here whatever-123)"
contains "unknown account → same generic message"        "$T/body"  "Emri i përdoruesit ose fjalëkalimi është i pasaktë"
check "inactive account with correct password → 403"     "403 "     "$(login $W prove.joaktiv Prove-Joaktiv-2026)"
contains "inactive account → explains deactivation"      "$T/body"  "çaktivizuar"
check "a failed attempt is recorded"                     "1"        "$(SQL "SELECT COUNT(*)>0 FROM login_attempts WHERE identifier='nobody.here' AND succeeded=0")"
check "last_login_at is set after signing in"            "1"        "$(SQL "SELECT last_login_at IS NOT NULL FROM users WHERE username='prove.admin'")"
SQL "DELETE FROM login_attempts WHERE identifier='nobody.here'"

echo; echo "== Throttling =="
L="$T/lock"
SQL "DELETE FROM login_attempts WHERE identifier='prove.mesimdhenes'"
for i in 1 2 3 4 5; do r=$(login $L prove.mesimdhenes "wrong-$i-xyz"); done
check "5th wrong password still gets a normal answer"    "422 "     "$r"
check "6th attempt is locked — even with correct password" "429 "   "$(login $L prove.mesimdhenes Prove-Mesimdhenes-2026)"
contains "lock message says when to try again"           "$T/body"  "provoni përsëri pas"
O="$T/other"
check "other accounts on the same network still work"    "302 /admin" "$(login $O prove.admin Prove-Admin-2026)"
SQL "DELETE FROM login_attempts WHERE identifier='prove.mesimdhenes'"

echo; echo "== Deactivation while signed in =="
D="$T/deact"
login $D prove.nxenes Prove-Nxenes-2026 >/dev/null
check "signed in before deactivation"                    "200"      "$(code $D /nxenesi)"
SQL "UPDATE users SET status='inactive' WHERE username='prove.nxenes'"
check "next click after deactivation → sign-in page"     "302 /hyr" "$(go $D /nxenesi)"
code $D /hyr >/dev/null
contains "sign-in page explains the deactivation"        "$T/body"  "çaktivizuar"
SQL "UPDATE users SET status='active' WHERE username='prove.nxenes'"

echo; echo "== Sign out =="
check "POST /dil without token is refused"               "403"      "$(curl -s -o /dev/null -w '%{http_code}' -b $S -c $S -X POST $B/dil)"
check "POST /dil with token → sign-in page"              "302 /hyr" "$(post $S /dil /nxenesi)"
code $S /hyr >/dev/null
contains "goodbye message"                               "$T/body"  "Dolët nga llogaria"
check "after sign-out the dashboard is closed"           "302 /hyr" "$(go $S /nxenesi)"

echo; echo "== First sign-in with a temporary password =="
F="$T/first"
check "temporary password → must choose a new one"      "302 /ndrysho-fjalekalimin" "$(login $F prove.fillestar mali-libri-deti-47)"
check "dashboard is blocked until then"                  "302 /ndrysho-fjalekalimin" "$(go $F /nxenesi)"
check "profile is blocked until then"                    "302 /ndrysho-fjalekalimin" "$(go $F /profili)"
check "wrong temporary password → 422"                   "422 "     "$(post $F /ndrysho-fjalekalimin /ndrysho-fjalekalimin --data-urlencode current_password=nope --data-urlencode password=Ylli-Pylli-Arra-7 --data-urlencode password_confirmation=Ylli-Pylli-Arra-7)"
contains "…says the temporary password is wrong"         "$T/body"  "Fjalëkalimi i përkohshëm nuk është i saktë"
check "too simple a password → 422"                      "422 "     "$(post $F /ndrysho-fjalekalimin /ndrysho-fjalekalimin --data-urlencode current_password=mali-libri-deti-47 --data-urlencode password=12345678 --data-urlencode password_confirmation=12345678)"
contains "…explains it is too simple"                    "$T/body"  "shumë i thjeshtë"
check "confirmation mismatch → 422"                      "422 "     "$(post $F /ndrysho-fjalekalimin /ndrysho-fjalekalimin --data-urlencode current_password=mali-libri-deti-47 --data-urlencode password=Ylli-Pylli-Arra-7 --data-urlencode password_confirmation=Ylli-Pylli-Arra-8)"
contains "…says they do not match"                       "$T/body"  "nuk përputhen"
check "password equal to username → 422"                "422 "     "$(post $F /ndrysho-fjalekalimin /ndrysho-fjalekalimin --data-urlencode current_password=mali-libri-deti-47 --data-urlencode password=prove.fillestar --data-urlencode password_confirmation=prove.fillestar)"
check "valid new password → dashboard"                   "302 /nxenesi" "$(post $F /ndrysho-fjalekalimin /ndrysho-fjalekalimin --data-urlencode current_password=mali-libri-deti-47 --data-urlencode password=Ylli-Pylli-Arra-7 --data-urlencode password_confirmation=Ylli-Pylli-Arra-7)"
check "flag cleared in the database"                     "0"        "$(SQL "SELECT must_change_password FROM users WHERE username='prove.fillestar'")"
check "change recorded in the activity log"              "1"        "$(SQL "SELECT COUNT(*)>0 FROM activity_log WHERE action='auth.password_set'")"
F2="$T/first2"
check "old temporary password no longer works"           "422 "     "$(login $F2 prove.fillestar mali-libri-deti-47)"
check "new password works"                               "302 /nxenesi" "$(login $F2 prove.fillestar Ylli-Pylli-Arra-7)"

echo; echo "== Profile =="
P="$T/profile"; login $P prove.nxenes Prove-Nxenes-2026 >/dev/null
check "invalid e-mail → 422"                             "422 "     "$(post $P /profili /profili --data-urlencode email=not-an-email --data-urlencode phone=)"
contains "…error shown under the field"                  "$T/body"  'id="email-error"'
SQL "UPDATE users SET email='zene@shembull.test' WHERE username='prove.admin'"
check "e-mail used by another account → 422"            "422 "     "$(post $P /profili /profili --data-urlencode email=Zene@Shembull.test --data-urlencode phone=)"
check "valid e-mail and phone → saved"                   "302 /profili" "$(post $P /profili /profili --data-urlencode email=Nxenesja@Shembull.test --data-urlencode 'phone=+383 44 123 456')"
check "e-mail stored lowercase"                          "nxenesja@shembull.test" "$(SQL "SELECT email FROM users WHERE username='prove.nxenes'")"
E="$T/email"
check "can sign in with the e-mail address too"          "302 /nxenesi" "$(login $E NXENESJA@shembull.test Prove-Nxenes-2026)"
check "password change with wrong current → 422"         "422 "     "$(post $P /profili/fjalekalimi /profili --data-urlencode current_password=wrong --data-urlencode password=Deti-Hena-Lisi-9 --data-urlencode password_confirmation=Deti-Hena-Lisi-9)"
contains "…says the current password is wrong"           "$T/body"  "Fjalëkalimi aktual nuk është i saktë"
SQL "UPDATE users SET email=NULL, phone=NULL WHERE username IN ('prove.admin','prove.nxenes')"

echo; echo "== Security headers on the sign-in page =="
H=$(curl -s -D - -o /dev/null $B/hyr | tr -d '\r')
check "CSP present"                                      "yes" "$(echo "$H" | grep -qi '^content-security-policy' && echo yes || echo no)"
check "X-Frame-Options DENY"                             "DENY" "$(echo "$H" | grep -i '^x-frame-options' | awk '{print $2}')"

"$PHP" database/demo/test-accounts.php > /dev/null
echo; echo "RESULT: $pass passed, $fail failed"
rm -r "$T"
[ "$fail" -eq 0 ]
