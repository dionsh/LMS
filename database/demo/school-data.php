<?php

declare(strict_types=1);

/*
 * The school's structure for 2026/2027, used by database/demo/school.php.
 *
 * REAL (from the school):
 * - 45 classes. Morning: XI-1 … XI-7 and XII-1 … XII-15, as on the official
 *   timetable "Orari i mësimit, Paradite, 2026/2027" (21.09.2026). Afternoon:
 *   X-1 … X-15 and XI-8 … XI-15.
 * - The teachers and their timetable numbers (staff.php), the homeroom teachers
 *   of the morning classes (below), the subjects and weekly hours of each grade
 *   (database/seed.sql).
 *
 * DEMO (development only, until the school's data arrives):
 * - Which subjects the teachers teach is not known yet, so every subject still
 *   without a teacher is given a DEMO teacher ("Demo Matematikë 1", …; at most
 *   20 lessons a week, never on the public website). The afternoon classes'
 *   homeroom teachers are demo teachers too.
 * - A generated timetable for every class that has none.
 * - 10 student accounts that can sign in (password in database/demo/README.md).
 */

return [
    // Classes: [grade, first section, last section, shift (1 = paradite, 2 = pasdite)]
    'classes' => [
        [10, 1, 15, 2],
        [11, 1, 7, 1],
        [11, 8, 15, 2],
        [12, 1, 15, 1],
    ],

    // Homeroom teachers of the morning classes (official timetable): [grade, section, teacher number]
    'homerooms' => [
        [11, 1, 68], [11, 2, 44], [11, 3, 22], [11, 4, 50], [11, 5, 35], [11, 6, 58], [11, 7, 43],
        [12, 1, 26], [12, 2, 3], [12, 3, 70], [12, 4, 55], [12, 5, 25], [12, 6, 53], [12, 7, 17],
        [12, 8, 4], [12, 9, 48], [12, 10, 66], [12, 11, 54], [12, 12, 9], [12, 13, 69], [12, 14, 15],
        [12, 15, 39],
    ],

    // Lessons a week a demo teacher takes at most (the school's full norm)
    'demo_norm' => 20,

    // The test teacher account (database/demo/test-accounts.php) teaches Matematikë in the
    // three demo classes that have students, where no real teacher is known: [grade, section]
    'test_teacher' => ['username' => 'prove.mesimdhenes', 'subject' => 'Matematikë', 'classes' => [[12, 1], [11, 5], [10, 13]]],

    // Demo student accounts: [first name, last name, grade, section, date of birth, gender]
    'students' => [
        ['Ariana', 'Gashi', 12, 1, '2009-03-14', 'F'],
        ['Blend', 'Hoxha', 12, 1, '2009-07-02', 'M'],
        ['Diellza', 'Morina', 12, 1, '2009-11-21', 'F'],
        ['Lorik', 'Berisha', 12, 1, '2009-05-09', 'M'],
        ['Erion', 'Shala', 11, 5, '2010-02-17', 'M'],
        ['Era', 'Kelmendi', 11, 5, '2010-09-30', 'F'],
        ['Rron', 'Bytyqi', 11, 5, '2010-06-12', 'M'],
        ['Dea', 'Hasani', 10, 13, '2011-01-25', 'F'],
        ['Leart', 'Krasniqi', 10, 13, '2011-04-08', 'M'],
        ['Albiona', 'Rexhepi', 10, 13, '2011-08-19', 'F'],
    ],

    'student_password' => 'Nxenes-Demo-2026',
];
