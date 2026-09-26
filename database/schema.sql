-- =====================================================================
--  Gjimnazi "Kuvendi i Arbërit" — LMS
--  Database schema
--
--  Target:   MariaDB 10.4+ / MySQL 8.0+
--  Engine:   InnoDB (foreign keys + transactions)
--  Charset:  utf8mb4 / utf8mb4_unicode_ci (full Albanian support: ë, ç)
--
--  Conventions
--  - Every table has an INT UNSIGNED surrogate key `id` (except settings
--    and the 1:1 profile tables, keyed by user_id).
--  - Timestamps are DATETIME in school-local time (Europe/Belgrade);
--    the app sets the connection time_zone to match PHP.
--  - Academic records (grades, submissions, enrollments of graded
--    students) are protected with ON DELETE RESTRICT: accounts with
--    history are deactivated, never silently deleted.
--  - Composite foreign keys keep denormalised columns honest
--    (e.g. schedule_entries.class_id must match its class_subject).
--
--  This file creates tables only. Reference data lives in seed.sql.
--  Until launch this file is edited directly; after launch every
--  change goes into database/migrations/NNN_description.sql.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. School configuration
-- ---------------------------------------------------------------------

-- Key/value store for school information and platform switches
-- (name, address, contact details, about texts, grading thresholds…).
CREATE TABLE settings (
  setting_key   VARCHAR(100) NOT NULL,
  setting_value TEXT         NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. People
-- ---------------------------------------------------------------------

-- There is no self sign-up: every account is created by the school
-- (students individually or by CSV import), with a generated username
-- and a temporary password that must be changed at first login.
-- Login accepts the username or, when present, the e-mail address.
CREATE TABLE users (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role                 ENUM('admin','teacher','student') NOT NULL,
  username             VARCHAR(60)  NOT NULL,           -- "arta.gashi" (lowercase ASCII, generated from the name)
  first_name           VARCHAR(60)  NOT NULL,
  last_name            VARCHAR(60)  NOT NULL,
  email                VARCHAR(190) NULL,               -- optional; stored lowercase
  password_hash        VARCHAR(255) NOT NULL,           -- password_hash(PASSWORD_DEFAULT)
  phone                VARCHAR(30)  NULL,
  avatar_path          VARCHAR(255) NULL,               -- relative to public/uploads/
  status               ENUM('active','inactive') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1)   NOT NULL DEFAULT 1, -- 1 while the school-issued temporary password is in use
  last_login_at        DATETIME     NULL,               -- NULL = account never used (credentials not yet handed out/used)
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),                    -- NULLs allowed, duplicates not
  KEY idx_users_role_status (role, status),
  KEY idx_users_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_profiles (
  user_id          INT UNSIGNED NOT NULL,
  title            VARCHAR(30)  NULL,                   -- "Prof.", "Dr."
  specialization   VARCHAR(120) NULL,                   -- shown on the public staff page
  bio              TEXT         NULL,
  show_on_website  TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_teacher_profiles_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_profiles (
  user_id        INT UNSIGNED NOT NULL,
  student_number VARCHAR(30)  NULL,                     -- numri i amzës
  date_of_birth  DATE         NULL,
  gender         ENUM('F','M') NULL,
  parent_name    VARCHAR(120) NULL,
  parent_phone   VARCHAR(30)  NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_student_profiles_number (student_number),
  CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Academic calendar
-- ---------------------------------------------------------------------

CREATE TABLE academic_years (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(20)  NOT NULL,                     -- "2026/2027"
  starts_on  DATE         NOT NULL,
  ends_on    DATE         NOT NULL,
  is_current TINYINT(1)   NOT NULL DEFAULT 0,           -- exactly one row = 1 (enforced by the app, in a transaction)
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_academic_years_name (name),
  CONSTRAINT chk_academic_years_dates CHECK (ends_on > starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grading periods inside a year (e.g. two semesters: "Gjysmëvjetori i parë/i dytë").
CREATE TABLE terms (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  academic_year_id INT UNSIGNED     NOT NULL,
  name             VARCHAR(60)      NOT NULL,
  sort_order       TINYINT UNSIGNED NOT NULL,
  starts_on        DATE             NOT NULL,
  ends_on          DATE             NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_terms_year_order (academic_year_id, sort_order),
  CONSTRAINT fk_terms_year FOREIGN KEY (academic_year_id)
    REFERENCES academic_years (id) ON DELETE CASCADE,
  CONSTRAINT chk_terms_dates CHECK (ends_on > starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. School structure: subjects, rooms, bell schedule, classes
-- ---------------------------------------------------------------------

CREATE TABLE subjects (
  id              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  name            VARCHAR(100)      NOT NULL,           -- "Gjuhë shqipe dhe letërsi"
  short_name      VARCHAR(12)       NOT NULL,           -- "Shqip" — compact timetable cells
  description     TEXT              NULL,               -- public "Programet" page
  is_active       TINYINT(1)        NOT NULL DEFAULT 1,
  show_on_website TINYINT(1)        NOT NULL DEFAULT 1,
  sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME          NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subjects_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rooms (
  id        INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  name      VARCHAR(60)       NOT NULL,                 -- "204", "Laboratori i kimisë"
  capacity  SMALLINT UNSIGNED NULL,
  is_active TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rooms_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bell schedule. Kosovo schools often run two shifts, so period times
-- are defined per shift; a class belongs to one shift.
CREATE TABLE lesson_periods (
  id        INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  shift     TINYINT UNSIGNED NOT NULL DEFAULT 1,        -- 1 = paradite, 2 = pasdite
  number    TINYINT UNSIGNED NOT NULL,                  -- ora e 1-rë, e 2-të …
  starts_at TIME             NOT NULL,
  ends_at   TIME             NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lesson_periods_shift_number (shift, number),
  CONSTRAINT chk_lesson_periods_shift  CHECK (shift IN (1, 2)),
  CONSTRAINT chk_lesson_periods_number CHECK (number BETWEEN 1 AND 12),
  CONSTRAINT chk_lesson_periods_times  CHECK (ends_at > starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A class ("paralele") for one academic year, e.g. X-1, XI-3.
-- Display label is derived: roman(grade_level) + "-" + section.
CREATE TABLE classes (
  id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  academic_year_id    INT UNSIGNED     NOT NULL,
  grade_level         TINYINT UNSIGNED NOT NULL,        -- 10, 11, 12
  section             VARCHAR(10)      NOT NULL,        -- "1", "2" …
  stream              VARCHAR(80)      NULL,            -- drejtimi: "Shkenca natyrore"
  shift               TINYINT UNSIGNED NOT NULL DEFAULT 1,
  homeroom_teacher_id INT UNSIGNED     NULL,            -- kujdestari i klasës
  home_room_id        INT UNSIGNED     NULL,
  created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_classes_year_grade_section (academic_year_id, grade_level, section),
  UNIQUE KEY uq_classes_id_year (id, academic_year_id),           -- target of enrollments composite FK
  KEY idx_classes_homeroom (homeroom_teacher_id),
  KEY idx_classes_home_room (home_room_id),
  CONSTRAINT fk_classes_year FOREIGN KEY (academic_year_id)
    REFERENCES academic_years (id) ON DELETE RESTRICT,
  CONSTRAINT fk_classes_homeroom FOREIGN KEY (homeroom_teacher_id)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_classes_home_room FOREIGN KEY (home_room_id)
    REFERENCES rooms (id) ON DELETE SET NULL,
  CONSTRAINT chk_classes_grade_level CHECK (grade_level BETWEEN 1 AND 13),
  CONSTRAINT chk_classes_shift CHECK (shift IN (1, 2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which class a student belongs to in a given year. Keeping this as its
-- own table (instead of a class_id on the student) preserves history
-- across years and makes the end-of-year promotion a simple insert.
CREATE TABLE enrollments (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id       INT UNSIGNED NOT NULL,
  class_id         INT UNSIGNED NOT NULL,
  academic_year_id INT UNSIGNED NOT NULL,               -- copy of classes.academic_year_id (composite FK keeps it in sync)
  enrolled_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enrollments_student_year (student_id, academic_year_id),  -- one class per student per year
  KEY idx_enrollments_class (class_id, academic_year_id),
  CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_enrollments_class FOREIGN KEY (class_id, academic_year_id)
    REFERENCES classes (id, academic_year_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The central "teaching assignment": subject S is taught in class C by
-- teacher T. Assignments, assessments, grades and timetable slots all
-- hang off this row, so changing the teacher mid-year keeps history.
CREATE TABLE class_subjects (
  id           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  class_id     INT UNSIGNED     NOT NULL,
  subject_id   INT UNSIGNED     NOT NULL,
  teacher_id   INT UNSIGNED     NULL,                   -- NULL = not yet assigned
  weekly_hours TINYINT UNSIGNED NULL,                   -- planned lessons per week (timetable check)
  created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_class_subjects_class_subject (class_id, subject_id),
  UNIQUE KEY uq_class_subjects_id_class (id, class_id),            -- target of schedule_entries composite FK
  KEY idx_class_subjects_teacher (teacher_id),
  KEY idx_class_subjects_subject (subject_id),
  CONSTRAINT fk_class_subjects_class FOREIGN KEY (class_id)
    REFERENCES classes (id) ON DELETE CASCADE,
  CONSTRAINT fk_class_subjects_subject FOREIGN KEY (subject_id)
    REFERENCES subjects (id) ON DELETE RESTRICT,
  CONSTRAINT fk_class_subjects_teacher FOREIGN KEY (teacher_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Weekly timetable. One lesson per class per (day, period).
-- Teacher and room double-booking are checked by ScheduleService, because
-- they depend on real clock times (classes can be in different shifts).
CREATE TABLE schedule_entries (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  class_id         INT UNSIGNED     NOT NULL,
  class_subject_id INT UNSIGNED     NOT NULL,
  day_of_week      TINYINT UNSIGNED NOT NULL,           -- ISO-8601: 1 = e hënë … 5 = e premte
  period_number    TINYINT UNSIGNED NOT NULL,           -- matches lesson_periods.number for the class's shift
  room_id          INT UNSIGNED     NULL,               -- NULL = the class's home room
  updated_by       INT UNSIGNED     NULL,
  created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_schedule_class_slot (class_id, day_of_week, period_number),
  KEY idx_schedule_class_subject (class_subject_id, class_id),
  KEY idx_schedule_room_slot (room_id, day_of_week, period_number),
  KEY idx_schedule_updated_by (updated_by),
  CONSTRAINT fk_schedule_class_subject FOREIGN KEY (class_subject_id, class_id)
    REFERENCES class_subjects (id, class_id) ON DELETE CASCADE,
  CONSTRAINT fk_schedule_room FOREIGN KEY (room_id)
    REFERENCES rooms (id) ON DELETE SET NULL,
  CONSTRAINT fk_schedule_updated_by FOREIGN KEY (updated_by)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_schedule_day    CHECK (day_of_week BETWEEN 1 AND 7),
  CONSTRAINT chk_schedule_period CHECK (period_number BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Coursework: assignments, submissions, assessments, grades
-- ---------------------------------------------------------------------

-- Grade categories (detyrë shtëpie, test, provim, projekt …) with an
-- optional weight used when computing subject averages. All weights
-- default to 1.00, i.e. a plain average, until the school decides otherwise.
CREATE TABLE grade_types (
  id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  code       VARCHAR(30)      NOT NULL,                 -- stable identifier used in code
  name       VARCHAR(60)      NOT NULL,                 -- Albanian label
  weight     DECIMAL(4,2)     NOT NULL DEFAULT 1.00,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_grade_types_code (code),
  CONSTRAINT chk_grade_types_weight CHECK (weight > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Homework / projects that students hand in through the platform.
CREATE TABLE assignments (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  class_subject_id INT UNSIGNED NOT NULL,
  grade_type_id    INT UNSIGNED NOT NULL,               -- which grade category a mark for this counts as
  created_by       INT UNSIGNED NULL,
  title            VARCHAR(200) NOT NULL,
  description      TEXT         NOT NULL,
  instructions     TEXT         NULL,
  due_at           DATETIME     NOT NULL,
  max_points       DECIMAL(6,2) NULL,                   -- optional points scale; the mark itself is always 1–5
  allow_late       TINYINT(1)   NOT NULL DEFAULT 1,     -- accept (and flag) submissions after due_at
  status           ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at     DATETIME     NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_assignments_id_cs (id, class_subject_id),          -- target of grades composite FK
  KEY idx_assignments_cs_status_due (class_subject_id, status, due_at),
  KEY idx_assignments_grade_type (grade_type_id),
  KEY idx_assignments_created_by (created_by),
  CONSTRAINT fk_assignments_class_subject FOREIGN KEY (class_subject_id)
    REFERENCES class_subjects (id) ON DELETE RESTRICT,
  CONSTRAINT fk_assignments_grade_type FOREIGN KEY (grade_type_id)
    REFERENCES grade_types (id) ON DELETE RESTRICT,
  CONSTRAINT fk_assignments_created_by FOREIGN KEY (created_by)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_assignments_max_points CHECK (max_points IS NULL OR max_points > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher-provided materials. Files live in storage/uploads/assignments/
-- (outside the web root) under a random name and are streamed by PHP
-- after an authorization check.
CREATE TABLE assignment_files (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  assignment_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name   VARCHAR(100) NOT NULL,
  mime_type     VARCHAR(100) NOT NULL,
  size_bytes    INT UNSIGNED NOT NULL,
  uploaded_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_assignment_files_stored (stored_name),
  KEY idx_assignment_files_assignment (assignment_id),
  CONSTRAINT fk_assignment_files_assignment FOREIGN KEY (assignment_id)
    REFERENCES assignments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One submission per student per assignment; re-submitting updates it.
-- status: submitted = waiting for the teacher
--         returned  = teacher asked for a revision (student may resubmit)
--         reviewed  = teacher has reviewed it (feedback and/or a grade)
-- The mark itself lives in `grades` (assignment_id + student_id), so a
-- teacher can also grade a student who never submitted.
CREATE TABLE submissions (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  assignment_id INT UNSIGNED NOT NULL,
  student_id    INT UNSIGNED NOT NULL,
  body          TEXT         NULL,                      -- written answer / note to the teacher
  status        ENUM('submitted','returned','reviewed') NOT NULL DEFAULT 'submitted',
  submitted_at  DATETIME     NOT NULL,                  -- time of the latest (re)submission; late = submitted_at > due_at
  feedback      TEXT         NULL,                      -- komenti i mësimdhënësit
  reviewed_by   INT UNSIGNED NULL,
  reviewed_at   DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_submissions_assignment_student (assignment_id, student_id),
  KEY idx_submissions_student (student_id),
  KEY idx_submissions_status (status),
  KEY idx_submissions_reviewed_by (reviewed_by),
  CONSTRAINT fk_submissions_assignment FOREIGN KEY (assignment_id)
    REFERENCES assignments (id) ON DELETE CASCADE,
  CONSTRAINT fk_submissions_student FOREIGN KEY (student_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_submissions_reviewed_by FOREIGN KEY (reviewed_by)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE submission_files (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  submission_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name   VARCHAR(100) NOT NULL,                  -- storage/uploads/submissions/
  mime_type     VARCHAR(100) NOT NULL,
  size_bytes    INT UNSIGNED NOT NULL,
  uploaded_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_submission_files_stored (stored_name),
  KEY idx_submission_files_submission (submission_id),
  CONSTRAINT fk_submission_files_submission FOREIGN KEY (submission_id)
    REFERENCES submissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tests, exams, oral answers, in-class projects — anything graded that is
-- NOT handed in through the platform. Results are entered as grades.
CREATE TABLE assessments (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  class_subject_id INT UNSIGNED NOT NULL,
  term_id          INT UNSIGNED NOT NULL,
  grade_type_id    INT UNSIGNED NOT NULL,
  created_by       INT UNSIGNED NULL,
  title            VARCHAR(200) NOT NULL,
  description      TEXT         NULL,                   -- topics covered, what to bring …
  scheduled_on     DATE         NULL,                   -- shows in students' "upcoming" lists
  max_points       DECIMAL(6,2) NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_assessments_id_cs (id, class_subject_id),          -- target of grades composite FK
  KEY idx_assessments_cs_date (class_subject_id, scheduled_on),
  KEY idx_assessments_term (term_id),
  KEY idx_assessments_grade_type (grade_type_id),
  KEY idx_assessments_created_by (created_by),
  CONSTRAINT fk_assessments_class_subject FOREIGN KEY (class_subject_id)
    REFERENCES class_subjects (id) ON DELETE RESTRICT,
  CONSTRAINT fk_assessments_term FOREIGN KEY (term_id)
    REFERENCES terms (id) ON DELETE RESTRICT,
  CONSTRAINT fk_assessments_grade_type FOREIGN KEY (grade_type_id)
    REFERENCES grade_types (id) ON DELETE RESTRICT,
  CONSTRAINT fk_assessments_created_by FOREIGN KEY (created_by)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_assessments_max_points CHECK (max_points IS NULL OR max_points > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every individual mark (1–5, the Kosovo scale). A mark comes from an
-- assignment, from an assessment, or is entered directly ("manual" —
-- e.g. an oral answer), never from both sources at once.
-- The composite FKs guarantee a mark for an assignment/assessment is
-- filed under that same class_subject.
CREATE TABLE grades (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  student_id       INT UNSIGNED     NOT NULL,
  class_subject_id INT UNSIGNED     NOT NULL,
  term_id          INT UNSIGNED     NOT NULL,
  grade_type_id    INT UNSIGNED     NOT NULL,
  assignment_id    INT UNSIGNED     NULL,
  assessment_id    INT UNSIGNED     NULL,
  grade            TINYINT UNSIGNED NOT NULL,
  points           DECIMAL(6,2)     NULL,               -- raw score when the source has max_points
  title            VARCHAR(200)     NULL,               -- label for manual marks
  comment          TEXT             NULL,
  graded_on        DATE             NOT NULL,
  graded_by        INT UNSIGNED     NULL,
  updated_by       INT UNSIGNED     NULL,               -- last editor (teacher or admin correction)
  created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_grades_assignment_student (assignment_id, student_id),
  UNIQUE KEY uq_grades_assessment_student (assessment_id, student_id),
  KEY idx_grades_student_term (student_id, term_id),
  KEY idx_grades_cs_term (class_subject_id, term_id),
  KEY idx_grades_assignment_cs (assignment_id, class_subject_id),
  KEY idx_grades_assessment_cs (assessment_id, class_subject_id),
  KEY idx_grades_term (term_id),
  KEY idx_grades_grade_type (grade_type_id),
  KEY idx_grades_graded_by (graded_by),
  KEY idx_grades_updated_by (updated_by),
  CONSTRAINT fk_grades_student FOREIGN KEY (student_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_grades_class_subject FOREIGN KEY (class_subject_id)
    REFERENCES class_subjects (id) ON DELETE RESTRICT,
  CONSTRAINT fk_grades_term FOREIGN KEY (term_id)
    REFERENCES terms (id) ON DELETE RESTRICT,
  CONSTRAINT fk_grades_grade_type FOREIGN KEY (grade_type_id)
    REFERENCES grade_types (id) ON DELETE RESTRICT,
  CONSTRAINT fk_grades_assignment FOREIGN KEY (assignment_id, class_subject_id)
    REFERENCES assignments (id, class_subject_id) ON DELETE RESTRICT,
  CONSTRAINT fk_grades_assessment FOREIGN KEY (assessment_id, class_subject_id)
    REFERENCES assessments (id, class_subject_id) ON DELETE RESTRICT,
  CONSTRAINT fk_grades_graded_by FOREIGN KEY (graded_by)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_grades_updated_by FOREIGN KEY (updated_by)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_grades_value CHECK (grade BETWEEN 1 AND 5),
  CONSTRAINT chk_grades_single_source CHECK (assignment_id IS NULL OR assessment_id IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The teacher's concluding mark for a subject in a term (nota e
-- gjysmëvjetorit). The app suggests the weighted average of `grades`;
-- the teacher decides — as Kosovo practice requires.
CREATE TABLE term_grades (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  student_id       INT UNSIGNED     NOT NULL,
  class_subject_id INT UNSIGNED     NOT NULL,
  term_id          INT UNSIGNED     NOT NULL,
  grade            TINYINT UNSIGNED NOT NULL,
  comment          VARCHAR(500)     NULL,
  decided_by       INT UNSIGNED     NULL,
  decided_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_term_grades_student_cs_term (student_id, class_subject_id, term_id),
  KEY idx_term_grades_cs_term (class_subject_id, term_id),
  KEY idx_term_grades_term (term_id),
  KEY idx_term_grades_decided_by (decided_by),
  CONSTRAINT fk_term_grades_student FOREIGN KEY (student_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_term_grades_class_subject FOREIGN KEY (class_subject_id)
    REFERENCES class_subjects (id) ON DELETE RESTRICT,
  CONSTRAINT fk_term_grades_term FOREIGN KEY (term_id)
    REFERENCES terms (id) ON DELETE RESTRICT,
  CONSTRAINT fk_term_grades_decided_by FOREIGN KEY (decided_by)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_term_grades_value CHECK (grade BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Communication: public posts, internal announcements, notifications
-- ---------------------------------------------------------------------

CREATE TABLE post_categories (
  id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  name       VARCHAR(80)      NOT NULL,
  slug       VARCHAR(90)      NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_post_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Public news/articles on the school website.
CREATE TABLE posts (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id   INT UNSIGNED NULL,
  author_id     INT UNSIGNED NULL,
  title         VARCHAR(220) NOT NULL,
  slug          VARCHAR(240) NOT NULL,                  -- URL: /lajme/{slug}
  excerpt       VARCHAR(400) NULL,
  body          MEDIUMTEXT   NOT NULL,                  -- lightweight markup, rendered by a safe formatter (no raw HTML)
  cover_image   VARCHAR(255) NULL,                      -- relative to public/uploads/
  cover_caption VARCHAR(255) NULL,
  is_featured   TINYINT(1)   NOT NULL DEFAULT 0,        -- "Në fokus"
  status        ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at  DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_posts_slug (slug),
  KEY idx_posts_status_published (status, published_at),
  KEY idx_posts_featured (is_featured, published_at),
  KEY idx_posts_category (category_id),
  KEY idx_posts_author (author_id),
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id)
    REFERENCES post_categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_author FOREIGN KEY (author_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Internal notices shown inside the LMS (not on the public site).
-- audience = 'class' requires class_id (validated by the app).
CREATE TABLE announcements (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  author_id    INT UNSIGNED NULL,
  title        VARCHAR(200) NOT NULL,
  body         TEXT         NOT NULL,
  audience     ENUM('everyone','students','teachers','class') NOT NULL DEFAULT 'everyone',
  class_id     INT UNSIGNED NULL,
  is_pinned    TINYINT(1)   NOT NULL DEFAULT 0,
  published_at DATETIME     NOT NULL,
  expires_at   DATETIME     NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_announcements_audience_published (audience, published_at),
  KEY idx_announcements_class (class_id),
  KEY idx_announcements_author (author_id),
  CONSTRAINT fk_announcements_author FOREIGN KEY (author_id)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_announcements_class FOREIGN KEY (class_id)
    REFERENCES classes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-user notifications (the bell). `url` is always an internal path.
CREATE TABLE notifications (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED    NOT NULL,
  type       VARCHAR(40)     NOT NULL,                  -- assignment.published, submission.reviewed, grade.created …
  title      VARCHAR(200)    NOT NULL,
  body       VARCHAR(500)    NULL,
  url        VARCHAR(255)    NULL,
  read_at    DATETIME        NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user_read (user_id, read_at, created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Public website content
-- ---------------------------------------------------------------------

CREATE TABLE contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  subject    VARCHAR(200) NOT NULL,
  message    TEXT         NOT NULL,
  ip_address VARCHAR(45)  NULL,
  read_at    DATETIME     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_contact_messages_created (created_at),
  KEY idx_contact_messages_ip_created (ip_address, created_at)      -- rate limiting
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faqs (
  id           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  question     VARCHAR(300)      NOT NULL,
  answer       TEXT              NOT NULL,
  sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_published TINYINT(1)        NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE useful_links (
  id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  title       VARCHAR(150)      NOT NULL,
  url         VARCHAR(500)      NOT NULL,               -- validated http(s) only
  description VARCHAR(300)      NULL,
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Audit & security
-- ---------------------------------------------------------------------

-- "Who did what": feeds the admin dashboard and records admin
-- corrections to grades, role/status changes, deletions.
CREATE TABLE activity_log (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED    NULL,
  action       VARCHAR(60)     NOT NULL,                -- user.created, grade.updated, post.published …
  subject_type VARCHAR(40)     NULL,
  subject_id   INT UNSIGNED    NULL,
  description  VARCHAR(255)    NOT NULL,
  ip_address   VARCHAR(45)     NULL,
  created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_log_created (created_at),
  KEY idx_activity_log_user (user_id),
  KEY idx_activity_log_subject (subject_type, subject_id),
  CONSTRAINT fk_activity_log_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Brute-force protection for the login form (per identifier and per IP).
CREATE TABLE login_attempts (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  identifier   VARCHAR(190)    NOT NULL,                -- what was typed: username or e-mail, lowercased
  ip_address   VARCHAR(45)     NOT NULL,
  succeeded    TINYINT(1)      NOT NULL DEFAULT 0,
  attempted_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_attempts_identifier (identifier, attempted_at),
  KEY idx_login_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
