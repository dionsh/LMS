# Tests

Run them from the project folder (Git Bash on Windows). They use the **local development** setup only.

| Command | What it proves |
|---|---|
| `php tests/router_test.php` | URL matching, `{id:\d+}` parameters, route groups, 404/405, trailing-slash redirects, automatic CSRF on POST, `route()` URLs |
| `bash tests/database_test.sh` | the schema's foreign keys, unique keys and CHECK constraints reject bad data (15 cases) and accept valid data. It uses a throwaway database |
| `bash tests/auth_test.sh` | signing in and out, role isolation, throttling, deactivation, temporary passwords, profile — 71 checks over real HTTP with the test accounts |

Every command exits with status 0 only when all checks pass.

Add tests for each new feature as it is built. T20 turns these into the full per-role test plan.
