# Test accounts — development only

`php database/demo/test-accounts.php` creates, or resets, these accounts on a **local development** database. The script refuses to run unless `app.env` is `development`.

**These accounts must never exist on the live server.** The launch checklist (T23) includes removing them.

| Username | Password | Role | Purpose |
|---|---|---|---|
| `prove.admin` | `Prove-Admin-2026` | Administrator | admin area |
| `prove.mesimdhenes` | `Prove-Mesimdhenes-2026` | Mësimdhënës | teacher area |
| `prove.nxenes` | `Prove-Nxenes-2026` | Nxënës | student area |
| `prove.fillestar` | `mali-libri-deti-47` | Nxënës | temporary password: must choose a new one at first sign-in |
| `prove.joaktiv` | `Prove-Joaktiv-2026` | Nxënës (inactive) | a deactivated account cannot sign in |

Running the script again restores these passwords and clears their failed sign-in attempts.

## School structure (`php database/demo/school.php`)

Loads the 2026/2027 structure from `school-data.php`. It is safe to run again; nothing is duplicated, and changes made in the admin panel are kept.

- **37 classes**, each with a homeroom teacher (*kujdestar*) and the subjects of its grade's curriculum:
  - **XI-1 … XI-7 and XII-1 … XII-15** in the morning shift, with their **real homeroom teachers**, exactly as on the official timetable *Orari i mësimit, Paradite, 2026/2027* (21.09.2026, `images/orari.jpg`).
  - **X-1 … X-15** in the afternoon shift. Their homeroom teachers are placeholders.
- **80 teachers**, stored as records **without sign-in credentials**. They can't sign in until the admin issues a login slip (T05).
  - 22 are the real homeroom teachers of XI and XII. They are shown on the public website. They do **not** teach in the demo, because the school has not yet said which subjects they teach.
  - 58 are **placeholders** with one or two subjects each. They teach every class, balanced to 12–25 lessons a week. They are hidden from the public website (`show_on_website = 0`) and will be replaced by the real staff list.
  - The test account `prove.mesimdhenes` teaches Matematikë in XII-1, XI-5 and X-13, so the teacher's pages have something to show.
- **Weekly hours are demo values.** The school's real plan has not been given yet. Each grade adds up to 30 lessons (6 a day × 5 days), matching the official timetable. They are only filled in where the curriculum has no hours, so hours entered under *Plani mësimor* are never overwritten.
- **10 student accounts**, all with the password **`Nxenes-Demo-2026`**:

| Username | Name | Class |
|---|---|---|
| `ariana.gashi` | Ariana Gashi | XII-1 |
| `blend.hoxha` | Blend Hoxha | XII-1 |
| `diellza.morina` | Diellza Morina | XII-1 |
| `lorik.berisha` | Lorik Berisha | XII-1 |
| `erion.shala` | Erion Shala | XI-5 |
| `era.kelmendi` | Era Kelmendi | XI-5 |
| `rron.bytyqi` | Rron Bytyqi | XI-5 |
| `dea.hasani` | Dea Hasani | X-13 |
| `leart.krasniqi` | Leart Krasniqi | X-13 |
| `albiona.rexhepi` | Albiona Rexhepi | X-13 |

Rebuild everything from scratch (development only):

```
C:\xampp_ick\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS kuvendi_lms; CREATE DATABASE kuvendi_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp_ick\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 kuvendi_lms -e "source C:/xampp_ick/htdocs/lms-system/database/schema.sql; source C:/xampp_ick/htdocs/lms-system/database/seed.sql;"
C:\xampp_ick\php\php.exe database/demo/test-accounts.php
C:\xampp_ick\php\php.exe database/demo/school.php
```

The school's real first administrator is created with `php database/create-admin.php` instead. That script generates a username and a one-time temporary password.
