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

The school's real first administrator is created with `php database/create-admin.php` instead. That script generates a username and a one-time temporary password.
