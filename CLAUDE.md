# Gjimnazi “Kuvendi i Arbërit” — LMS

A school website plus an LMS (student / teacher / admin) in plain PHP 8.2 + MariaDB via PDO.

**Read first:**
- `docs/ARCHITECTURE.md` — design, permissions, DB rationale, URL map, design system, Albanian glossary.
- `docs/ROADMAP.md` — the ordered task list.

## Working agreement
- Build **one roadmap task at a time**, verify its "Done when" check, tick it in ROADMAP.md, then stop for the user's review.
- The UI is 100 % Albanian and follows the glossary in ARCHITECTURE §12. Buttons use the singular imperative (Hyr, Ruaj, Dorëzo); messages use the polite *ju*. No emoji.
- Code identifiers, comments and SQL are in English. URLs are Albanian without diacritics (`/nxenesi/detyrat`).

## Environment
- XAMPP at `C:\xampp_ick`; app at `http://localhost/lms-system/`; PHP CLI `C:\xampp_ick\php\php.exe`.
- `http://localhost/lms-system/_sistemi` (development only) checks PHP, DB, time zone, folders and CSRF. Run it after environment changes.
- Request flow: `public/index.php` → `app/bootstrap.php` → `app/routes.php` → `Router` (auto-CSRF on POST, middleware) → controller → `View`. Errors are handled by `Core/ErrorHandler` (logs go to `storage/logs/`).
- DB `kuvendi_lms` (local: root, no password). `gd`/`intl` are not enabled — don't rely on them.
- Rebuild the DB from scratch (dev only). Import with mysql's `source`, not a PowerShell pipe, or UTF-8 gets mangled:
  ```
  C:\xampp_ick\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS kuvendi_lms; CREATE DATABASE kuvendi_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  C:\xampp_ick\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 kuvendi_lms -e "source C:/xampp_ick/htdocs/lms-system/database/schema.sql; source C:/xampp_ick/htdocs/lms-system/database/seed.sql;"
  ```

## Non-negotiable conventions
- SQL only in `app/Models`, only via PDO prepared statements. Dynamic identifiers (ORDER BY, columns) only from whitelists.
- All output is escaped with `e()`. Post bodies are rendered only through `PostFormatter`, and raw HTML is never stored.
- Every POST carries the CSRF token, and nothing changes state on GET.
- Role guards live on route groups in `app/routes.php`. Every ID taken from a URL is checked by a Policy, returning 404 when it isn't the user's.
- New role pages go inside the matching route group (`auth`, `password.changed`, `role:…`) and their controllers extend `PortalController` (use `$this->page()`). Object access goes through a `Policy` (`Policy::authorize()` → 404).
- Forms: validate with `Core/Validator`, show errors with `field_error()` / `field_invalid()`. Passwords go through `Services/PasswordPolicy`.
- Tests live in `tests/`. Run all three (see `tests/README.md`) before committing, and add checks for new features. Local test logins: `php database/demo/test-accounts.php` (listed in `database/demo/README.md`).
- Private files go in `storage/` and are streamed after a policy check. `public/uploads/` is for public images only.
- No inline `<script>`/`style=""` (strict CSP); use CSS classes and `public/assets/js`.
- Use the design tokens from ARCHITECTURE §11 (`public/assets/css/tokens.css`). Don't introduce new colours or fonts ad hoc. Any new colour pair goes into the contrast table on `/_stilet`, and it must pass.
- Build pages from existing components (`components.css`) and partials. Icons come via `icon('name')`, never emoji. Layouts: `site`, `auth`, `portal` (the portal expects `$user`, `$active`, `$unread`).
- Components must not depend on the HTML tag they sit on (e.g. `.hero__title` sets its own font).
- Schema changes: edit `database/schema.sql` until launch; afterwards add numbered files in `database/migrations/`.
