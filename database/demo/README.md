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

**Real data** (from the school; the source photos stay on the developer's machine, not in the repository):
- **45 classes**, each with the subjects and weekly hours of its grade's curriculum (`database/seed.sql`):
  - morning: **XI-1 … XI-7 and XII-1 … XII-15**, with their real homeroom teachers from the official timetable *Orari i mësimit, Paradite, 2026/2027* (21.09.2026);
  - afternoon: **X-1 … X-15 and XI-8 … XI-15**.
- **The school's 72 teachers** with their timetable numbers (`staff.php`, from the staff list 2026–2027).
  - They are records **without sign-in credentials** until the admin issues login slips (T05), and are shown on the public website.
  - Which subjects they teach is filled into `staff.php` as the school sends it. So far only 26 (Enver Bajrami) is known: Matematikë and Mësim zgjedhor (career orientation). Subjects go to a teacher who has none in the database yet, so subjects ticked in the admin panel are kept.
- **The official morning timetable** (`timetable-morning.php`): the printed sheet, a teacher's number in every cell. It is loaded into the timetable in numbers (`/admin/orari/numrat`) while the morning has none, so changes made there are kept.
  - It becomes a class's timetable as soon as the subjects of all the class's teachers are known. It replaces only a demo timetable; a class whose timetable has real teachers is left alone.
  - Until then the morning classes keep the demo timetable below.
- **The morning's daily duty** (*kujdestaria e ditës*, `duty-morning.php`): who keeps watch in the hall and on each floor, per day, by timetable number, from the bottom of the official morning timetable. It is loaded only while the morning has no roster, so changes made in the admin panel are kept.

**Demo data** (development only, until the school's data arrives):
- **Demo teachers** ("Demo Matematikë 1", "Demo Matematikë 2" …) teach every subject whose real teacher is not known.
  - Each has at most 20 lessons a week (the norm), is hidden from the public website and has no timetable number.
  - They are also the afternoon classes' homeroom teachers.
- The test account `prove.mesimdhenes` teaches Matematikë in XII-1, XI-5 and X-13, so the teacher's pages have something to show in both shifts.
- **A demo timetable** for every class that has none: each subject as many times a week as planned, at most twice a day, no teacher in two classes at once. It is generated with a fixed seed, so it is the same on every run.
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
