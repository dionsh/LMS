<?php

declare(strict_types=1);

/*
 * The school's structure for 2026/2027, used by database/demo/school.php.
 *
 * REAL (from the school):
 * - Classes XI-1 … XI-7 and XII-1 … XII-15 in the morning shift, and their
 *   homeroom teachers, exactly as on the official timetable "Orari i mësimit,
 *   Paradite, 2026/2027" dated 21.09.2026 (images/orari.jpg).
 * - Grade X in the afternoon shift (15 classes, as before).
 * - The subjects and which grades study them (database/seed.sql).
 *
 * DEMO PLACEHOLDERS (to be replaced by the school's real data):
 * - Weekly hours per subject: the school has not given its plan yet. Each
 *   grade adds up to 30 lessons = 6 lessons × 5 days, as on the timetable.
 * - 58 placeholder teachers with the subjects they teach. They teach every
 *   class in the demo; the real homeroom teachers do not teach in the demo,
 *   because which subjects they teach is not known yet.
 *   Placeholders are never shown on the public website.
 * - 10 student accounts that can sign in (password in database/demo/README.md).
 */

return [
    // Number of classes (paralele) per grade
    'classes' => [10 => 15, 11 => 7, 12 => 15],

    // Homeroom teachers from the official timetable: [first name, last name, grade, section]
    'real_homerooms' => [
        ['Dardan', 'Aliu', 11, 1],
        ['Ylber', 'Ukshini', 11, 2],
        ['Arjete', 'Zejnullahu', 11, 3],
        ['Genc', 'Hoxha', 11, 4],
        ['Bajram', 'Sejdiu', 11, 5],
        ['Gentiana', 'Çerkini', 11, 6],
        ['Fitore', 'Ramadani', 11, 7],
        ['Enver', 'Bajrami', 12, 1],
        ['Hysnije', 'Mustafa', 12, 2],
        ['Dhurata', 'Sahiti', 12, 3],
        ['Behar', 'Krasniqi', 12, 4],
        ['Avni', 'Hashani', 12, 5],
        ['Besata', 'Ajeti', 12, 6],
        ['Arbenita', 'Grainca', 12, 7],
        ['Azbije', 'Neziri', 12, 8],
        ['Vildane', 'Ahmeti', 12, 9],
        ['Albulena', 'Ajvazi', 12, 10],
        ['Flakë', 'Musliu', 12, 11],
        ['Burbuqe', 'Bucaliu', 12, 12],
        ['Enkelejdë', 'Bytyçi', 12, 13],
        ['Ardiana', 'Ilazi', 12, 14],
        ['Armir', 'Aliu', 12, 15],
    ],

    // DEMO weekly hours (only filled where the curriculum has none yet)
    'demo_hours' => [
        10 => ['Gjuhë shqipe' => 4, 'Gjuhë angleze' => 3, 'Gjuhë gjermane' => 2, 'Matematikë' => 4, 'Kimi' => 2,
               'Biologji' => 2, 'Fizikë' => 2, 'Edukatë fizike' => 2, 'Mësim zgjedhor' => 2, 'Teknologji' => 2,
               'Gjeografi' => 1, 'Muzikë' => 1, 'Art figurativ' => 1, 'Histori' => 2],
        11 => ['Gjuhë shqipe' => 4, 'Gjuhë angleze' => 3, 'Gjuhë gjermane' => 2, 'Matematikë' => 4, 'Kimi' => 2,
               'Biologji' => 2, 'Fizikë' => 3, 'Edukatë fizike' => 2, 'Mësim zgjedhor' => 2, 'Teknologji' => 2,
               'Gjeografi' => 2, 'Filozofi dhe psikologji' => 2],
        12 => ['Gjuhë shqipe' => 4, 'Gjuhë angleze' => 3, 'Gjuhë gjermane' => 2, 'Matematikë' => 4, 'Kimi' => 2,
               'Biologji' => 2, 'Fizikë' => 3, 'Edukatë fizike' => 2, 'Mësim zgjedhor' => 3, 'Teknologji' => 2,
               'Gjeografi' => 2, 'Astronomi' => 1],
    ],

    // Placeholder teachers: [first name, last name, subjects]. The first 15 are
    // homeroom teachers of X-1 … X-15.
    'placeholder_teachers' => [
        ['Agim', 'Berisha', ['Gjuhë shqipe']], ['Liridona', 'Gashi', ['Gjuhë shqipe']],
        ['Mimoza', 'Kelmendi', ['Gjuhë shqipe']], ['Besnik', 'Morina', ['Gjuhë shqipe']],
        ['Teuta', 'Shala', ['Gjuhë shqipe']], ['Driton', 'Ahmeti', ['Gjuhë shqipe']],
        ['Valbona', 'Hoxha', ['Gjuhë shqipe']], ['Fatmir', 'Rexhepi', ['Gjuhë shqipe']],
        ['Jehona', 'Sylejmani', ['Matematikë']], ['Ilir', 'Gashi', ['Matematikë']],
        ['Blerta', 'Krasniqi', ['Matematikë']], ['Faton', 'Ramadani', ['Matematikë']],
        ['Kaltrina', 'Bytyqi', ['Matematikë']], ['Gazmend', 'Kryeziu', ['Matematikë']],
        ['Leonora', 'Musliu', ['Matematikë']], ['Bekim', 'Qerimi', ['Matematikë']],
        ['Njomza', 'Zeqiri', ['Gjuhë angleze']], ['Lulzim', 'Osmani', ['Gjuhë angleze']],
        ['Rina', 'Tahiri', ['Gjuhë angleze']], ['Mentor', 'Selimi', ['Gjuhë angleze']],
        ['Qendresa', 'Ibrahimi', ['Gjuhë angleze']], ['Petrit', 'Limani', ['Gjuhë angleze']],
        ['Arta', 'Dervishi', ['Fizikë', 'Astronomi']], ['Valon', 'Haliti', ['Fizikë', 'Astronomi']],
        ['Edona', 'Kastrati', ['Fizikë', 'Astronomi']], ['Jeton', 'Salihu', ['Fizikë', 'Astronomi']],
        ['Gresa', 'Mehmeti', ['Fizikë', 'Astronomi']], ['Ylber', 'Idrizi', ['Fizikë', 'Astronomi']],
        ['Donika', 'Islami', ['Gjuhë gjermane']], ['Burim', 'Aliu', ['Gjuhë gjermane']],
        ['Vlora', 'Avdiu', ['Gjuhë gjermane']], ['Xhevdet', 'Balaj', ['Gjuhë gjermane']],
        ['Merita', 'Elezi', ['Kimi']], ['Skender', 'Fazliu', ['Kimi']],
        ['Hana', 'Kurteshi', ['Kimi']], ['Visar', 'Nimani', ['Kimi']],
        ['Flutura', 'Pllana', ['Biologji']], ['Shkelzen', 'Rama', ['Biologji']],
        ['Ilirjana', 'Shehu', ['Biologji']], ['Kujtim', 'Bajraktari', ['Biologji']],
        ['Zana', 'Dobruna', ['Edukatë fizike']], ['Taulant', 'Emini', ['Edukatë fizike']],
        ['Fitore', 'Hajdari', ['Edukatë fizike']], ['Ardian', 'Jakupi', ['Edukatë fizike']],
        ['Albana', 'Kabashi', ['Teknologji']], ['Naim', 'Lokaj', ['Teknologji']],
        ['Rrezarta', 'Maloku', ['Teknologji']], ['Genc', 'Mazreku', ['Teknologji']],
        ['Besa', 'Nuhiu', ['Gjeografi']], ['Dardan', 'Podvorica', ['Gjeografi']],
        ['Lumnije', 'Sadiku', ['Gjeografi']],
        ['Shpend', 'Sejdiu', ['Histori', 'Mësim zgjedhor']], ['Yllka', 'Shabani', ['Histori', 'Mësim zgjedhor']],
        ['Kastriot', 'Syla', ['Muzikë', 'Mësim zgjedhor']], ['Vjollca', 'Tafa', ['Art figurativ', 'Mësim zgjedhor']],
        ['Rexhep', 'Ukaj', ['Filozofi dhe psikologji', 'Mësim zgjedhor']],
        ['Arbnora', 'Zeka', ['Mësim zgjedhor']], ['Nexhat', 'Demaj', ['Mësim zgjedhor']],
    ],

    // The test teacher account (database/demo/test-accounts.php) teaches Matematikë in the
    // three demo classes that have students: [grade, section]
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
