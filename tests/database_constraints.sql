-- same session settings the application uses
SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
-- Fixture: 2 teachers, 2 students, 2 classes, subjects in both classes
INSERT INTO users (id, role, username, first_name, last_name, email, password_hash) VALUES
 (1,'teacher','arben.krasniqi','Arben','Krasniqi','t1@x.test','x'),
 (2,'teacher','drita.berisha','Drita','Berisha','t2@x.test','x'),
 (3,'student','arta.gashi','Arta','Gashi','s1@x.test','x'),
 (4,'student','blend.hoxha','Blend','Hoxha',NULL,'x');
INSERT INTO classes (id, academic_year_id, grade_level, section, homeroom_teacher_id) VALUES (1,1,10,'1',1),(2,1,10,'2',2);
INSERT INTO class_subjects (id, class_id, subject_id, teacher_id) VALUES (1,1,4,1),(2,2,4,2),(3,1,1,2);
INSERT INTO enrollments (student_id, class_id, academic_year_id) VALUES (3,1,1),(4,2,1);
INSERT INTO schedule_entries (class_id, class_subject_id, day_of_week, period_number) VALUES (1,1,5,1),(1,3,5,2);
INSERT INTO assignments (id, class_subject_id, grade_type_id, created_by, title, description, due_at, status) VALUES (1,1,1,1,'Ekuacionet','Zgjidh 1-10','2026-10-02 23:59','published');
INSERT INTO assessments (id, class_subject_id, term_id, grade_type_id, title, scheduled_on) VALUES (1,1,1,2,'Testi 1','2026-10-09');
INSERT INTO submissions (assignment_id, student_id, body, submitted_at) VALUES (1,3,'Zgjidhjet','2026-10-01 18:00');
INSERT INTO grades (student_id, class_subject_id, term_id, grade_type_id, assignment_id, grade, graded_on) VALUES (3,1,1,1,1,5,'2026-10-03');
INSERT INTO teacher_profiles (user_id, title, timetable_number) VALUES (1,'Prof.',25),(2,'Prof.',NULL);
INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (1,4),(2,1);
SELECT 'fixture inserted OK' AS result;

SELECT '--- each statement below MUST fail ---' AS result;
-- 1. timetable slot whose class_id does not match its class_subject (cs 2 belongs to class 2)
INSERT INTO schedule_entries (class_id, class_subject_id, day_of_week, period_number) VALUES (1,2,5,3);
-- 2. same class, same day, same period twice
INSERT INTO schedule_entries (class_id, class_subject_id, day_of_week, period_number) VALUES (1,3,5,1);
-- 3. mark outside the 1-5 scale
INSERT INTO grades (student_id, class_subject_id, term_id, grade_type_id, grade, graded_on) VALUES (3,1,1,5,6,'2026-10-03');
-- 4. mark with both an assignment and an assessment as its source
INSERT INTO grades (student_id, class_subject_id, term_id, grade_type_id, assignment_id, assessment_id, grade, graded_on) VALUES (4,1,1,1,1,1,4,'2026-10-03');
-- 5. mark for assignment 1 filed under a different class_subject (2)
INSERT INTO grades (student_id, class_subject_id, term_id, grade_type_id, assignment_id, grade, graded_on) VALUES (4,2,1,1,1,4,'2026-10-03');
-- 6. second mark for the same student + assignment
INSERT INTO grades (student_id, class_subject_id, term_id, grade_type_id, assignment_id, grade, graded_on) VALUES (3,1,1,1,1,3,'2026-10-03');
-- 7. student enrolled in two classes in the same year
INSERT INTO enrollments (student_id, class_id, academic_year_id) VALUES (3,2,1);
-- 8. enrollment whose academic_year does not match the class's year
INSERT INTO enrollments (student_id, class_id, academic_year_id) VALUES (4,1,99);
-- 9. deleting a student who has grades/submissions
DELETE FROM users WHERE id = 3;
-- 10. deleting an assignment that already has grades
DELETE FROM assignments WHERE id = 1;
-- 11. deleting a subject that is taught in a class
DELETE FROM subjects WHERE id = 4;
-- 12. deleting a class that still has enrolled students
DELETE FROM classes WHERE id = 2;
-- 13. duplicate e-mail (case-insensitive collation)
INSERT INTO users (role, username, first_name, last_name, email, password_hash) VALUES ('student','x.y','X','Y','S1@X.TEST','x');
-- 14. duplicate username
INSERT INTO users (role, username, first_name, last_name, password_hash) VALUES ('student','Arta.Gashi','Arta','Gashi','x');
-- 15. status value that no longer exists
INSERT INTO users (role, username, first_name, last_name, password_hash, status) VALUES ('student','p.q','P','Q','x','pending');
-- 16. a class in a grade the school does not have (grade 9 is not in grade_levels)
INSERT INTO classes (academic_year_id, grade_level, section) VALUES (1,9,1);
-- 17. deleting a grade that still has classes
DELETE FROM grade_levels WHERE level = 10;
-- 18. curriculum hours outside 1-12
UPDATE grade_subjects SET weekly_hours = 0 WHERE grade_level = 11 AND subject_id = 4;
-- 19. curriculum entry for a grade that does not exist
INSERT INTO grade_subjects (grade_level, subject_id, weekly_hours) VALUES (9,4,4);
-- 20. two teachers with the same number on the printed timetable
UPDATE teacher_profiles SET timetable_number = 25 WHERE user_id = 2;
-- 21. a class-subject with 13 lessons a week
UPDATE class_subjects SET weekly_hours = 13 WHERE id = 1;

SELECT '--- these MUST succeed ---' AS result;
-- several students without an e-mail address
INSERT INTO users (role, username, first_name, last_name, password_hash) VALUES ('student','besa.krasniqi','Besa','Krasniqi','x'),('student','besa.krasniqi2','Besa','Krasniqi','x');
SELECT COUNT(*) AS users_without_email FROM users WHERE email IS NULL;
-- manual mark (no source) twice for the same student is fine
INSERT INTO grades (student_id, class_subject_id, term_id, grade_type_id, title, grade, graded_on) VALUES (3,1,1,5,'Përgjigje me gojë',4,'2026-10-05'),(3,1,1,5,'Përgjigje me gojë',5,'2026-10-12');
-- assessment result
INSERT INTO grades (student_id, class_subject_id, term_id, grade_type_id, assessment_id, points, grade, graded_on) VALUES (3,1,1,2,1,42.5,4,'2026-10-09');
-- a new grade, then its curriculum
INSERT INTO grade_levels (level, shift) VALUES (13, 1);
INSERT INTO grade_subjects (grade_level, subject_id, weekly_hours) VALUES (13,4,4);
-- a subject that is only in the curriculum can be deleted; its curriculum rows go with it
DELETE FROM subjects WHERE name = 'Astronomi';
SELECT COUNT(*) AS astronomy_rows_left FROM grade_subjects gs LEFT JOIN subjects s ON s.id = gs.subject_id WHERE s.id IS NULL;
-- removing a teacher un-assigns them without destroying history
DELETE FROM users WHERE id = 2;
SELECT COUNT(*) AS teacher_subjects_left_for_removed_teacher FROM teacher_subjects WHERE teacher_id = 2;
SELECT id, class_id, teacher_id FROM class_subjects WHERE id IN (2,3);
SELECT id, homeroom_teacher_id FROM classes WHERE id = 2;
SELECT COUNT(*) AS grades_for_arta FROM grades WHERE student_id = 3;
