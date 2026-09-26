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
  ('principal_name',            ''),
  -- Contact
  ('school_address',            ''),
  ('school_city',               ''),
  ('school_phone',              ''),
  ('school_email',              ''),
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
-- Subjects of the Kosovo gymnasium curriculum (edit/extend in admin)
-- ---------------------------------------------------------------------
INSERT INTO subjects (name, short_name, sort_order) VALUES
  ('Gjuhë shqipe dhe letërsi',  'Shqip',     1),
  ('Gjuhë angleze',             'Anglisht',  2),
  ('Gjuhë gjermane',            'Gjermanisht', 3),
  ('Matematikë',                'Mat',       4),
  ('Fizikë',                    'Fizikë',    5),
  ('Kimi',                      'Kimi',      6),
  ('Biologji',                  'Biologji',  7),
  ('Informatikë',               'Info',      8),
  ('Histori',                   'Histori',   9),
  ('Gjeografi',                 'Gjeografi', 10),
  ('Filozofi',                  'Filozofi',  11),
  ('Sociologji',                'Sociologji', 12),
  ('Psikologji',                'Psikologji', 13),
  ('Edukatë qytetare',          'Ed. qyt.',  14),
  ('Art figurativ',             'Art',       15),
  ('Muzikë',                    'Muzikë',    16),
  ('Edukatë fizike dhe sport',  'Ed. fizike', 17);

-- ---------------------------------------------------------------------
-- Public news categories
-- ---------------------------------------------------------------------
INSERT INTO post_categories (name, slug, sort_order) VALUES
  ('Lajme',      'lajme',      1),
  ('Arritje',    'arritje',    2),
  ('Aktivitete', 'aktivitete', 3),
  ('Shpallje',   'shpallje',   4);
