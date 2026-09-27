# Tests

Run them from the project folder (Git Bash on Windows). They use the **local development** setup only.

| Command | What it proves |
|---|---|
| `php tests/router_test.php` | URL matching, `{id:\d+}` parameters, route groups, 404/405, trailing-slash redirects, automatic CSRF on POST, `route()` URLs |
| `bash tests/database_test.sh` | the schema's foreign keys, unique keys and CHECK constraints reject bad data (21 cases: marks, enrollments, timetable slots, grades that do not exist, curriculum hours, duplicate timetable numbers…) and accept valid data. It uses a throwaway database |
| `bash tests/auth_test.sh` | signing in and out, role isolation, throttling, deactivation, temporary passwords, profile — 71 checks over real HTTP with the test accounts |
| `bash tests/users_test.sh` | user management — lists, search, filters and pages; adding and editing students, teachers and admins; login slips (single, per class, all teachers) and signing in with them; activation and safeguards (61 checks, restores the demo data afterwards) |
| `bash tests/school_data_test.sh` | the school structure: 37 classes (XI-1…7 and XII-1…15 in the morning as on the official timetable, X in the afternoon), each with a homeroom teacher and its grade's subjects, the 22 real homeroom teachers, 80 staff records that cannot sign in, a teacher for every subject, 10 enrolled students, the admin lists and the student's class on the dashboard (39 checks, loader run twice to prove it is re-runnable) |
| `bash tests/structure_test.sh` | the academic structure: curriculum per grade (adding/removing a subject reaches every class of the grade, subjects in use stay), subjects, adding/editing/deleting classes and the rules behind them (unique sections, one homeroom class per teacher, one class per room and shift), who teaches what, rooms, teachers' subjects and timetable numbers, school years and the current year, the activity log (103 checks, restores the demo data afterwards) |

| `bash tests/timetable_test.sh` | the timetable: the demo week (30 lessons per class, subjects as planned, no clashes, the same on every run), the whole-school sheet in both shifts and both views, the class editor refusing teacher and room clashes, sharing a room across shifts, clashes judged by clock time across shifts, planned vs scheduled hours, notifications without pile-up, the bell schedule rules (78 checks, restores the demo data afterwards) |
| `bash tests/schedule_views_test.sh` | the timetable as students and teachers see it: the student's week and subjects, now/next at chosen moments (a lesson, the big break, after school, the weekend), the teacher's week across both shifts, the "timetable changed" note (GET changes nothing), a teacher's class pages and ownership (someone else's → 404), role separation, the admin's view of a teacher's week (69 checks, restores the demo data afterwards) |

Every command exits with status 0 only when all checks pass.

**Writing HTTP tests on Windows.** Git Bash passes command-line arguments to `curl.exe` and `mysql.exe` in the Windows code page, not UTF-8. So send non-ASCII form values percent-encoded, e.g. `--data first_name=T%C3%ABst` for “Tëst”, and keep SQL literals in tests ASCII (use `LIKE` with `%` in place of ë/ç).

Add tests for each new feature as it is built. T20 turns these into the full per-role test plan.
