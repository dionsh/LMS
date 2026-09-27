-- =====================================================================
--  Gjimnazi "Kuvendi i Arbërit" — LMS
--  Reference data (required for the platform to work)
--
--  Everything here is editable later from the admin panel. School facts
--  that are unknown yet (address, phone, founding year …) are left empty
--  on purpose — the public site hides empty fields — rather than invented.
--
--  Demo users/classes for testing live in a separate demo seed (later task).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- School information & platform switches
-- ---------------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
  -- Identity
  ('school_name',               'Gjimnazi “Kuvendi i Arbërit”'),
  ('school_short_name',         'Kuvendi i Arbërit'),
  ('school_tagline',            ''),
  ('school_founded_year',       ''),
  ('principal_name',            'Bajram Rexhepi'),
  -- Contact (from the school's letterhead)
  ('school_address',            'Rruga “Fatmir Hasani” nr. 14'),
  ('school_city',               '70000 Ferizaj'),                -- postal code and city
  ('school_phone',              '+383 49 923 828'),
  ('school_email',              'imersion7@gmail.com'),
  ('school_map_url',            ''),
  ('facebook_url',              ''),
  ('instagram_url',             ''),
  -- Public "Rreth nesh" page (filled in during the public-site task)
  ('about_intro',               ''),
  ('about_body',                ''),
  ('principal_message',         ''),
  -- Platform behaviour
  ('grade_thresholds',          '50,65,80,90'),  -- minimum % for grades 2,3,4,5 when a score is converted to a mark
  ('upload_max_mb',             '10');

-- ---------------------------------------------------------------------
-- Grade categories (weights 1.00 = plain average until the school decides otherwise)
-- ---------------------------------------------------------------------
INSERT INTO grade_types (code, name, weight, sort_order) VALUES
  ('homework',  'Detyrë shtëpie',    1.00, 1),
  ('test',      'Test',              1.00, 2),
  ('exam',      'Provim',            1.00, 3),
  ('project',   'Projekt',           1.00, 4),
  ('oral',      'Përgjigje me gojë', 1.00, 5),
  ('classwork', 'Aktivitet në orë',  1.00, 6),
  ('other',     'Tjetër',            1.00, 7);

-- ---------------------------------------------------------------------
-- Current academic year and its two semesters (dates adjustable by admin)
-- ---------------------------------------------------------------------
INSERT INTO academic_years (name, starts_on, ends_on, is_current) VALUES
  ('2026/2027', '2026-09-01', '2027-06-30', 1);

INSERT INTO terms (academic_year_id, name, sort_order, starts_on, ends_on) VALUES
  (LAST_INSERT_ID(), 'Gjysmëvjetori i parë', 1, '2026-09-01', '2027-01-17'),
  (LAST_INSERT_ID(), 'Gjysmëvjetori i dytë', 2, '2027-01-18', '2027-06-30');

-- ---------------------------------------------------------------------
-- Bell schedule (confirmed by the school): two shifts, six 45-minute
-- lessons each. 5-minute breaks, except two 10-minute main breaks after
-- the 2nd and the 4th lesson. Morning starts 08:00, afternoon 14:00.
-- The official morning timetable (2026/2027, dated 21.09.2026) agrees:
-- every class has six lessons a day, Monday to Friday. Its printed
-- seventh column is empty, so no seventh period is defined; the admin
-- can add one in the bell-schedule editor if it is ever used.
-- ---------------------------------------------------------------------
INSERT INTO lesson_periods (shift, number, starts_at, ends_at) VALUES
  -- Paradite
  (1, 1, '08:00', '08:45'),
  (1, 2, '08:50', '09:35'),
  (1, 3, '09:45', '10:30'),
  (1, 4, '10:35', '11:20'),
  (1, 5, '11:30', '12:15'),
  (1, 6, '12:20', '13:05'),
  -- Pasdite
  (2, 1, '14:00', '14:45'),
  (2, 2, '14:50', '15:35'),
  (2, 3, '15:45', '16:30'),
  (2, 4, '16:35', '17:20'),
  (2, 5, '17:30', '18:15'),
  (2, 6, '18:20', '19:05');

-- ---------------------------------------------------------------------
-- Grades and their shift (confirmed by the school): X in the afternoon,
-- XI and XII in the morning. Classes take their grade's shift by default;
-- XI-8 … XI-15 are afternoon classes (set per class).
-- ---------------------------------------------------------------------
INSERT INTO grade_levels (level, shift) VALUES
  (10, 2),
  (11, 1),
  (12, 1);

-- ---------------------------------------------------------------------
-- The school's subjects (given by the school, 27 Sep 2026). short_name
-- is for the compact whole-school timetable. Filozofi and Psikologji are
-- two subjects of grade XI, 2 lessons a week each.
-- ---------------------------------------------------------------------
INSERT INTO subjects (name, short_name, sort_order) VALUES
  ('Gjuhë shqipe',   'Shqip',    1),
  ('Gjuhë angleze',  'Angl.',    2),
  ('Gjuhë gjermane', 'Gjerm.',   3),
  ('Matematikë',     'Mat.',     4),
  ('Kimi',           'Kimi',     5),
  ('Biologji',       'Biol.',    6),
  ('Fizikë',         'Fiz.',     7),
  ('Edukatë fizike', 'Ed. fiz.', 8),
  ('Mësim zgjedhor', 'Zgjedh.',  9),
  ('Teknologji',     'TIK',      10),
  ('Gjeografi',      'Gjeogr.',  11),
  ('Muzikë',         'Muz.',     12),
  ('Art figurativ',  'Art',      13),
  ('Histori',        'Hist.',    14),
  ('Filozofi',       'Filoz.',   15),
  ('Psikologji',     'Psik.',    16),
  ('Astronomi',      'Astr.',    17);

-- ---------------------------------------------------------------------
-- Curriculum (plani mësimor, given by the school, 27 Sep 2026): the
-- subjects of each grade and their lessons a week. Every grade has 30
-- lessons a week = 6 lessons a day × 5 days, as on the printed timetable.
-- ---------------------------------------------------------------------
INSERT INTO grade_subjects (grade_level, subject_id, weekly_hours)
SELECT plan.grade_level, s.id, plan.weekly_hours
  FROM (
            SELECT 10 AS grade_level, 'Gjuhë shqipe' AS name, 3 AS weekly_hours
  UNION ALL SELECT 10, 'Gjuhë angleze',  2 UNION ALL SELECT 10, 'Gjuhë gjermane', 2
  UNION ALL SELECT 10, 'Matematikë',     4 UNION ALL SELECT 10, 'Kimi',           2
  UNION ALL SELECT 10, 'Biologji',       3 UNION ALL SELECT 10, 'Fizikë',         2
  UNION ALL SELECT 10, 'Edukatë fizike', 2 UNION ALL SELECT 10, 'Mësim zgjedhor', 2
  UNION ALL SELECT 10, 'Teknologji',     2 UNION ALL SELECT 10, 'Gjeografi',      2
  UNION ALL SELECT 10, 'Histori',        2 UNION ALL SELECT 10, 'Art figurativ',  1
  UNION ALL SELECT 10, 'Muzikë',         1

  UNION ALL SELECT 11, 'Gjuhë shqipe',   3 UNION ALL SELECT 11, 'Gjuhë angleze',  2
  UNION ALL SELECT 11, 'Gjuhë gjermane', 2 UNION ALL SELECT 11, 'Matematikë',     4
  UNION ALL SELECT 11, 'Kimi',           3 UNION ALL SELECT 11, 'Biologji',       2
  UNION ALL SELECT 11, 'Fizikë',         3 UNION ALL SELECT 11, 'Edukatë fizike', 2
  UNION ALL SELECT 11, 'Mësim zgjedhor', 2 UNION ALL SELECT 11, 'Teknologji',     1
  UNION ALL SELECT 11, 'Gjeografi',      2 UNION ALL SELECT 11, 'Filozofi',       2
  UNION ALL SELECT 11, 'Psikologji',     2

  UNION ALL SELECT 12, 'Gjuhë shqipe',   4 UNION ALL SELECT 12, 'Gjuhë angleze',  2
  UNION ALL SELECT 12, 'Gjuhë gjermane', 1 UNION ALL SELECT 12, 'Matematikë',     4
  UNION ALL SELECT 12, 'Kimi',           3 UNION ALL SELECT 12, 'Biologji',       3
  UNION ALL SELECT 12, 'Fizikë',         3 UNION ALL SELECT 12, 'Edukatë fizike', 2
  UNION ALL SELECT 12, 'Mësim zgjedhor', 2 UNION ALL SELECT 12, 'Teknologji',     2
  UNION ALL SELECT 12, 'Gjeografi',      2 UNION ALL SELECT 12, 'Astronomi',      2
  ) AS plan
  JOIN subjects s ON s.name = plan.name COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Daily duty posts, as on the official timetable: the hall (one teacher)
-- and each of the three floors (two teachers each).
-- ---------------------------------------------------------------------
INSERT INTO duty_posts (name, places, sort_order) VALUES
  ('Salla',       1, 1),
  ('Kati i parë', 2, 2),
  ('Kati i dytë', 2, 3),
  ('Kati i tretë', 2, 4);

-- ---------------------------------------------------------------------
-- Public news categories
-- ---------------------------------------------------------------------
INSERT INTO post_categories (name, slug, sort_order) VALUES
  ('Lajme',      'lajme',      1),
  ('Arritje',    'arritje',    2),
  ('Aktivitete', 'aktivitete', 3),
  ('Shpallje',   'shpallje',   4);
