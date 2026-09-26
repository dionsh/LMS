# Gjimnazi “Kuvendi i Arbërit” — Website & LMS

The school's public website and learning platform (students, teachers, administration).
Plain PHP 8.2 + MySQL/MariaDB via PDO: no framework, no Composer packages.

- Architecture, permissions, database design, design system: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- Build plan and progress: [docs/ROADMAP.md](docs/ROADMAP.md)

## Local setup (XAMPP on Windows)

1. Put the project in `htdocs/lms-system` and start Apache and MySQL in the XAMPP control panel.
2. Create the database and load the schema and reference data. Use `source` rather than piping the file in, so Albanian characters stay intact:
   ```
   C:\xampp_ick\mysql\bin\mysql.exe -u root -e "CREATE DATABASE kuvendi_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   C:\xampp_ick\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 kuvendi_lms -e "source C:/xampp_ick/htdocs/lms-system/database/schema.sql; source C:/xampp_ick/htdocs/lms-system/database/seed.sql;"
   ```
3. Copy `config/config.local.example.php` to `config/config.local.php` and adjust the database credentials if needed.
4. Open <http://localhost/lms-system/>. In development, <http://localhost/lms-system/_sistemi> runs a checklist of the environment (PHP, database, time zone, folders, CSRF).

## Folder overview

| Folder | Contents |
|---|---|
| `public/` | the only web-reachable folder: `index.php`, CSS/JS/images, public uploads |
| `app/` | application code: `Core/` (router, DB, session…), `Controllers/`, `Models/` (all SQL), `Views/`, `Middleware/`, `Support/` |
| `config/` | configuration (`config.local.php` holds this machine's credentials and is not committed) |
| `database/` | `schema.sql`, `seed.sql` |
| `storage/` | private files: uploaded homework, logs (never web-reachable) |
| `docs/` | architecture and roadmap |
