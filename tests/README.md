# Tests

Run them from the project folder (Git Bash on Windows). They use the **local development** setup only.

| Command | What it proves |
|---|---|
| `php tests/router_test.php` | URL matching, `{id:\d+}` parameters, route groups, 404/405, trailing-slash redirects, automatic CSRF on POST, `route()` URLs |
| `bash tests/database_test.sh` | the schema's foreign keys, unique keys and CHECK constraints reject bad data (15 cases) and accept valid data. It uses a throwaway database |
| `bash tests/auth_test.sh` | signing in and out, role isolation, throttling, deactivation, temporary passwords, profile — 71 checks over real HTTP with the test accounts |
| `bash tests/users_test.sh` | user management — lists, search, filters and pages; adding and editing students, teachers and admins; login slips (single, per class, all teachers) and signing in with them; activation and safeguards (61 checks, restores the demo data afterwards) |
| `bash tests/school_data_test.sh` | the school structure: 45 classes each with a homeroom teacher, 80 staff records that cannot sign in, the real XII/1–5 homeroom teachers, 10 enrolled students, the admin lists and the student's class on the dashboard (26 checks, loader run twice to prove it is re-runnable) |

Every command exits with status 0 only when all checks pass.

**Writing HTTP tests on Windows.** Git Bash passes command-line arguments to `curl.exe` and `mysql.exe` in the Windows code page, not UTF-8. So send non-ASCII form values percent-encoded, e.g. `--data first_name=T%C3%ABst` for “Tëst”, and keep SQL literals in tests ASCII (use `LIKE` with `%` in place of ë/ç).

Add tests for each new feature as it is built. T20 turns these into the full per-role test plan.
