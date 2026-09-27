# Tests

Run them from the project folder (Git Bash on Windows). They use the **local development** setup only.

| Command | What it proves |
|---|---|
| `php tests/router_test.php` | URL matching, `{id:\d+}` parameters, route groups, 404/405, trailing-slash redirects, automatic CSRF on POST, `route()` URLs |
| `bash tests/database_test.sh` | the schema's foreign keys, unique keys and CHECK constraints reject bad data (28 cases: marks, enrollments, timetable slots, grades that do not exist, curriculum hours, duplicate timetable numbers, teaching norms, the duty roster, the timetable in numbers…) and accept valid data. It uses a throwaway database |
| `bash tests/auth_test.sh` | signing in and out, role isolation, throttling, deactivation, temporary passwords, profile — 71 checks over real HTTP with the test accounts |
| `bash tests/users_test.sh` | user management — lists, search, filters and pages; adding and editing students, teachers and admins; login slips (single, per class, all teachers) and signing in with them; activation and safeguards (61 checks, restores the demo data afterwards) |
| `bash tests/school_data_test.sh` | the school's real structure: 45 classes in their shifts, the curriculum (30 lessons a week per grade, the school's hours), the 72 teachers with their numbers from the staff list, the real homeroom teachers, the 20-lesson norm, a teacher for every subject, 10 enrolled students, the admin lists and the student's class on the dashboard (49 checks, loader run twice to prove it is re-runnable) |
| `bash tests/structure_test.sh` | the academic structure: curriculum per grade (adding/removing a subject reaches every class of the grade, subjects in use stay), subjects, adding/editing/deleting classes and the rules behind them (unique sections, one homeroom class per teacher, one class per room and shift), who teaches what, rooms, teachers' subjects, timetable numbers and teaching norm, school years and the current year, the activity log (108 checks, restores the demo data afterwards) |
| `bash tests/timetable_test.sh` | the timetable: the demo week (30 lessons per class, subjects as planned, no clashes, the same on every run), the whole-school sheet in both shifts and both views, the class editor refusing teacher and room clashes, sharing a room across shifts, clashes judged by clock time across shifts, planned vs scheduled hours, notifications without pile-up, the bell schedule rules (78 checks, restores the demo data afterwards) |
| `bash tests/schedule_views_test.sh` | the timetable as students and teachers see it: the student's week and subjects, now/next at chosen moments (a lesson, the big break, after school, the weekend), the teacher's week across both shifts, the "timetable changed" note (GET changes nothing), a teacher's class pages and ownership (someone else's → 404), role separation, the admin's view of a teacher's week (69 checks, restores the demo data afterwards) |
| `bash tests/duty_test.sh` | the daily duty: the morning roster from the official timetable, one teacher per place and one place per teacher and day, the afternoon kept apart, duty posts (add, rename, shrink, a post in use stays), the roster on the whole-school sheet, the teacher's duty on *Orari* and the dashboard, admin only, the activity log (43 checks, restores the demo data afterwards) |
| `bash tests/sheet_test.sh` | the timetable in numbers: the official morning timetable as printed (660 numbers, 57 teachers, all on the staff list), the grid and what it refuses, a form that did not arrive whole, unknown numbers and a teacher in two classes at once, turning numbers into lessons once teachers' subjects are known (two ready classes applied, a teacher with two subjects, nothing changes the second time, the admin's choice first), the loader leaving the sheet and applied classes alone (82 checks, restores the demo data afterwards) |

Every command exits with status 0 only when all checks pass.

**Writing HTTP tests on Windows.** Git Bash passes command-line arguments to `curl.exe` and `mysql.exe` in the Windows code page, not UTF-8. So send non-ASCII form values percent-encoded, e.g. `--data first_name=T%C3%ABst` for “Tëst”, and keep SQL literals in tests ASCII (use `LIKE` with `%` in place of ë/ç).

Add tests for each new feature as it is built. T20 turns these into the full per-role test plan.
