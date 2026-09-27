# Roadmap — task by task

The LMS is built **one task at a time**. Each task ends with something that runs and has been tested, and then pauses for review before the next one starts. Tick a box only when its **Done when** check has actually been performed.

The order differs slightly from a plain feature list, for two reasons:
- the **design system comes early**, so every page is built on it rather than restyled later;
- the **admin's school structure comes before teacher/student features**, because teachers and students can only see classes, subjects and timetables once they exist.

Legend: ✅ done · 🟡 partly done · 🔜 next · ⬜ planned

---

## Phase 0 — Plan

- ✅ **T00 · Analysis & architecture**
  - Deliverable: [ARCHITECTURE.md](ARCHITECTURE.md), covering roles, permissions, auth, workflows, URL map, design system from the logo, glossary and security.
- ✅ **T01 · Database design**
  - Deliverables: `database/schema.sql` (30 tables, 44 FKs) and `database/seed.sql` (reference data), imported into `kuvendi_lms`.
  - Done when: import is clean, UTF-8 is verified, and 13 negative constraint tests are rejected. **Passed 26 Sep 2026.**

## Phase 1 — Foundation

- ✅ **T02 · Core skeleton** — **done 26 Sep 2026**: 13/13 router tests, 22/22 environment checks on `/_sistemi`, private folders refused, CSRF rejects missing/forged/foreign tokens, production 500 page leaks nothing, fallback page works with the DB down.
  - Deliverables:
    - root and `public/` `.htaccess`, front controller, `bootstrap.php`, `config/` (+ `config.local.php`)
    - PDO `Database`, `Router` with route groups and middleware, `View` with layouts, `helpers.php` (`e`, `url`, `asset`, Albanian dates and numbers)
    - error pages 403 / 404 / 419 / 500, security headers, logger
  - Done when:
    - `http://localhost/lms-system/` renders through the router;
    - an unknown URL shows the Albanian 404;
    - `/lms-system/app/…`, `/config/…` and `/storage/…` are refused;
    - the DB connection and time zone are verified.
- ✅ **T03 · Design system & layouts** — **done 26 Sep 2026**. Style guide at `/_stilet`, portal demo at `/_stilet/portali` (development only).
  - Verified:
    - 20/20 colour pairs pass WCAG AA, computed live from `tokens.css`;
    - fonts load from our own server, with tabular figures;
    - no horizontal scroll at 375 px; every touch target ≥ 44 px;
    - drawer and menu handle focus and Esc correctly;
    - no CSP violations.
  - Fixed during review: the focus ring and active-state underlines moved to the stronger teal (the logo teal is 2.99:1 on paper).
  - Deliverables:
    - tokens (§11.2–11.3), base styles and components (§11.5)
    - logo mark as crisp SVG and the line motif
    - self-hosted Newsreader + Manrope — will ask before downloading the font files
    - three layout shells: public site, auth, portal (sidebar + phone tab bar)
    - a dev-only style-guide page
  - Done when: every component renders at 375 px and 1440 px, and the contrast pairs pass AA.
- ✅ **T04 · Authentication & roles** — **done 27 Sep 2026**: `tests/auth_test.sh` passes 71/71 over real HTTP.
  - Verified:
    - every role is shut out of the other areas (403) and guests are sent to *Hyr*;
    - the session id changes at sign-in; portal pages are `no-store`; POSTs without a token are refused;
    - wrong password and unknown account give the same answer;
    - the 6th attempt is locked, while other accounts on the same network keep working;
    - a deactivated user is out on their next click;
    - temporary passwords must be replaced;
    - profile e-mail/phone are validated and unique.
  - `database/create-admin.php` tested end to end.
  - Deliverables:
    - *Hyr* (username or e-mail) and *Dil*; no sign-up page
    - hardened sessions, login throttling, role guards, ownership-policy base
    - forced password change on first login, *Profili* (details + password)
    - `php database/create-admin.php` to create the first admin
  - Done when:
    - a student opening `/admin` or `/mesimdhenesi` gets 403, and a teacher opening `/admin` gets 403;
    - a POST without a token gets 419;
    - the 6th wrong password is throttled;
    - a deactivated user is logged out on their next request.

## Phase 2 — Admin: the school's structure

- ✅ **T05 · User management** — **done 27 Sep 2026**: `tests/users_test.sh` passes 61/61.
  - Verified:
    - a student added through the form signs in with the password printed on the slip and must choose a new one;
    - all 80 teachers get slips in one step (one log line, no passwords logged);
    - class slips go only to students who have not signed in;
    - slips are visible only in the issuing admin's session and disappear on "Mbaro";
    - A4 print layout measured at 8 slips of 93 × 68 mm, content fitting;
    - self-deactivation is blocked;
    - malformed (non-UTF-8) input gives a normal 422, never a 500.
  - Deliverables:
    - list, filter, search and paginate users; add students, teachers and admins (generated username)
    - **one-time printable login slips**: for one person, for a whole class (students not yet signed in), or for all teachers without credentials
    - edit, activate/deactivate (never yourself, never the last admin), issue a new temporary password
    - teacher profile fields for the public staff page
    - (spreadsheet import dropped at the school's request)
  - Done when:
    - slips print cleanly on A4;
    - a student added through the form can sign in with their slip and is forced to change the password;
    - all 80 teachers can be given slips in one step.
- ✅ **T06 · Academic structure** — **done 27 Sep 2026**: `tests/structure_test.sh` passes 103/103, and `tests/database_test.sh` rejects 21/21.
  - Verified:
    - a class added through the form gets its grade's subjects;
    - every subject can be given a teacher, and the subject's own teachers are offered first;
    - a subject added to a grade's curriculum reaches all its classes; one taken out leaves them unless it is in use;
    - the rules hold: one section number per grade and year, one homeroom class per teacher, one class per room and shift;
    - subjects and rooms that are in use cannot be deleted, only deactivated;
    - a new school year can be made current and back;
    - every change is in the activity log.
  - Deliverables:
    - grades as data (`grade_levels`) and the curriculum per grade (*Plani mësimor*: subjects and weekly hours)
    - the school's 16 subjects (seed); years & terms (set current); rooms
    - classes (grade, section, stream, shift, homeroom teacher, own room), labelled **XII-1** as on the official timetable
    - students are enrolled and moved from their account page; subject → teacher per class, with weekly hours
    - teachers' subjects and their number on the printed timetable
  - Done when:
    - a class added through the form has its subjects and can be given a teacher for each;
    - a curriculum change reaches every class of the grade.
- 🟡 **T07 · Demo school data** — **part done 27 Sep 2026** (brought forward at the school's request):
  - `database/demo/school.php` loads:
    - 37 classes: XI-1…7 and XII-1…15 in the morning with their **real homeroom teachers** from the official timetable, and X-1…15 in the afternoon;
    - 80 teachers (records without credentials): the 22 real homeroom teachers plus 58 placeholders who teach every subject;
    - demo weekly hours (30 a week, as on the timetable);
    - 10 student accounts in X-13, XI-5 and XII-1.
  - `tests/school_data_test.sh` passes 39/39.
  - Timetable (T08): a clash-free demo week for every class.
  - Still to come: sample work (homework, tests, marks) with T10–T13.
  - Deliverable: `database/demo/` with a realistic test school (a few classes, ~8 teachers, ~60 students, timetable, sample work) so every later task can be tested properly. Test logins are recorded there, not in chat.
- ✅ **T08 · Timetable & bell schedule** — **done 27 Sep 2026**: `tests/timetable_test.sh` passes 78/78.
  - Verified:
    - the demo timetable has 30 lessons for each of the 37 classes (6 a day), every subject exactly its weekly hours, no teacher or room clash;
    - a teacher already in XI-5 cannot be put into XII-1 at the same time (422, named in the message, nothing saved);
    - a room cannot hold two classes at once, while a morning and an afternoon class can use the same room;
    - clashes are judged by clock time: moving the afternoon's first period onto the morning's makes exactly the expected 29 clashes appear;
    - changing the bell times moves every class's lessons without touching the timetable;
    - a period still in use cannot be removed; a 7th period can be added and appears in the editor and the sheet;
    - saving a changed week notifies the class's students once (no pile-up), an unchanged week notifies no one;
    - planned vs scheduled hours show on the editor, the class list and the sheet.
  - Deliverables:
    - **the whole-school sheet** (*Orari i mësimit*), per shift, laid out like the printed timetable:
      - classes down the side, days × periods across, grade bands, homeroom teachers on the right;
      - cells show the subject or, like the paper, the teacher's number (initials until numbers are entered), with a legend;
      - prints on one A4 landscape page per shift;
      - clash and completeness report
    - grid timetable builder per class (admin only): subject per slot, optional room for lessons outside the class's room; busy teachers and rooms are marked in the lists
    - clash detection by real clock time (teacher, room; one lesson per class and slot in the database)
    - bell schedule editor for both shifts (24-hour times, add/remove periods)
    - planned-vs-scheduled hours
    - students notified when their class's timetable changes (the bell list itself comes in T14)
    - demo timetable for all 37 classes (seeded, the same on every run)

## Phase 3 — LMS core

- ✅ **T09 · Portal shells & timetable views** — **done 27 Sep 2026**: `tests/schedule_views_test.sh` passes 69/69.
  - Verified (with `?tani=` to set the moment, development only):
    - at 09:10 on a Monday a student sees the lesson in progress, its teacher, the minutes left and what comes next;
    - in the big break the student sees the next lesson; after school, "Mësimi për sot mbaroi"; at the weekend, "Mësimi rifillon të hënën", with Monday's tab open;
    - a teacher at 14:20 sees their afternoon class as next, across shifts;
    - a timetable change shows a note until the student presses "E pashë"; opening the page changes nothing;
    - a teacher opening another teacher's class gets 404; roles cannot open each other's pages.
  - Deliverables:
    - student *Orari*:
      - the class's week with subject and teacher for every lesson;
      - day tabs on phones (today first), week grid from 768 px;
      - now/next, the "timetable changed" note, printable
    - student *Lëndët*: subjects, teachers, weekly hours and days
    - teacher *Orari* (read-only), across both shifts, with now/next; teacher *Klasat* and a page per class-subject (its lessons in the week and its students)
    - dashboards: the now/next card and today's lessons (the rest of the dashboards stays in T15)
    - admin: any teacher's week (`/admin/orari/mesimdhenesi/{id}`), linked from the teacher's page
    - moved to T10: the page for one subject (`/nxenesi/lendet/{id}`), because it holds homework and marks
- ⬜ **T10 · Assignments**
  - Deliverables:
    - the student's page for one subject (teacher, lessons, homework), `/nxenesi/lendet/{id}`
    - teacher create / edit / draft / publish with attachments
    - student list and detail
    - secure file streaming (`/skedari/...`)
- ⬜ **T11 · Submissions**
  - Deliverables:
    - student submit/resubmit (text + files), late logic, status labels
    - teacher's "all students in one table" view, including missing submissions
- ⬜ **T12 · Reviewing & feedback**
  - Deliverables:
    - teacher feedback + mark (points → suggested mark) or return for revision
    - student sees mark and feedback
    - notifications
  - Done when: **the full W3 loop works end to end** (ARCHITECTURE §8).
- ⬜ **T13 · Assessments & grade book**
  - Deliverables:
    - tests/exams/projects/oral; results-entry grid; manual marks
    - *Ditari i notave* with weighted averages and term grades
    - student *Notat* page
    - admin corrections, logged
- ⬜ **T14 · Announcements & notifications**
  - Deliverables:
    - announcement CRUD (admin: any audience; teacher: own classes), pinning, expiry
    - bell with unread count, mark as read
- ⬜ **T15 · Dashboards**
  - Deliverable: student, teacher and admin dashboards as specified in §11.7, fed by real data, with designed empty states.

## Phase 4 — Public website

- ⬜ **T16 · News management**
  - Deliverable: posts CRUD, cover upload, categories, featured, drafts/publish, safe formatter, Albanian slugs.
- ⬜ **T17 · Public pages**
  - Deliverables:
    - Ballina, Lajme (list / category / article), Rreth nesh, Programet, Stafi, Pyetje të shpeshta
    - Kontakti (form → admin inbox, honeypot, rate limit)
    - footer driven by settings
- ⬜ **T18 · Admin: school content & insight**
  - Deliverables:
    - school-information and platform-settings editor (incl. grade weights)
    - FAQ and useful links
    - contact inbox, activity log, statistics

## Phase 5 — Quality & launch

- ⬜ **T19 · UI refinement**
  - Deliverable: editorial polish, motion, empty states, and a full review of the Albanian copy against the glossary.
- ⬜ **T20 · End-to-end testing**
  - Deliverables:
    - `docs/TESTING.md` checklist per role, covering both workflows from the brief plus W4–W7
    - every item executed and the bugs fixed
- ⬜ **T21 · Security review**
  - Deliverable: an authorization sweep of every route, upload abuse tests, a header/CSP audit, an OWASP checklist, and fixes.
- ⬜ **T22 · Responsive & accessibility pass**
  - Deliverable: checks on phones and tablets, keyboard-only use, screen-reader labels, and reduced motion.
- ⬜ **T23 · Launch preparation**
  - Deliverables:
    - production config and a dedicated DB user
    - backup routine and `docs/DEPLOYMENT.md`
    - end-of-year rollover tool (W8)

---

## Later (not in v1)

Attendance (*mungesat*) · parent accounts · e-mail notifications & password reset by e-mail · class materials library · PDF report cards · messaging.
