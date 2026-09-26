<?php

declare(strict_types=1);

/*
 * The school's structure for 2026/2027, used by database/demo/school.php.
 *
 * - 3 grades × 15 classes = 45 classes, labelled X/1 … XII/15.
 * - 80 teachers stored as records WITHOUT sign-in credentials (login slips
 *   are issued later from the admin panel). Every class has a homeroom teacher.
 * - The five homeroom teachers of XII/1–XII/5 are real staff (given by the
 *   school). Everyone else is a PLACEHOLDER name until the real staff list is
 *   entered; placeholders are never shown on the public website.
 * - 10 student accounts that can sign in (password in database/demo/README.md).
 */

return [
    // Shift per grade (1 = paradite, 2 = pasdite) — an assumption until the school confirms
    'shifts' => [10 => 2, 11 => 1, 12 => 1],

    // Real homeroom teachers: [first name, last name, grade, section]
    'real_homerooms' => [
        ['Enver', 'Bajrami', 12, 1],
        ['Hysnije', 'Mustafa', 12, 2],
        ['Dhurata', 'Sahiti', 12, 3],
        ['Behar', 'Krasniqi', 12, 4],
        ['Avni', 'Hashani', 12, 5],
    ],

    // Placeholder teachers, in order. The first 40 become homeroom teachers of
    // X/1–X/15, XI/1–XI/15 and XII/6–XII/15; the remaining 35 teach without a homeroom.
    'placeholder_teachers' => [
        ['Agim', 'Berisha'], ['Liridona', 'Gashi'], ['Arben', 'Hyseni'], ['Mimoza', 'Kelmendi'],
        ['Besnik', 'Morina'], ['Teuta', 'Shala'], ['Driton', 'Ahmeti'], ['Valbona', 'Hoxha'],
        ['Fatmir', 'Rexhepi'], ['Jehona', 'Sylejmani'], ['Ilir', 'Gashi'], ['Blerta', 'Krasniqi'],
        ['Faton', 'Ramadani'], ['Kaltrina', 'Bytyqi'], ['Gazmend', 'Kryeziu'], ['Leonora', 'Musliu'],
        ['Bekim', 'Qerimi'], ['Njomza', 'Zeqiri'], ['Lulzim', 'Osmani'], ['Rina', 'Tahiri'],
        ['Mentor', 'Selimi'], ['Qendresa', 'Ibrahimi'], ['Petrit', 'Limani'], ['Arta', 'Dervishi'],
        ['Valon', 'Haliti'], ['Edona', 'Kastrati'], ['Jeton', 'Salihu'], ['Gresa', 'Mehmeti'],
        ['Ylber', 'Idrizi'], ['Donika', 'Islami'], ['Burim', 'Aliu'], ['Vlora', 'Avdiu'],
        ['Xhevdet', 'Balaj'], ['Merita', 'Elezi'], ['Skender', 'Fazliu'], ['Hana', 'Kurteshi'],
        ['Visar', 'Nimani'], ['Flutura', 'Pllana'], ['Shkelzen', 'Rama'], ['Ilirjana', 'Shehu'],
        ['Kujtim', 'Bajraktari'], ['Zana', 'Dobruna'], ['Taulant', 'Emini'], ['Fitore', 'Hajdari'],
        ['Ardian', 'Jakupi'], ['Albana', 'Kabashi'], ['Naim', 'Lokaj'], ['Rrezarta', 'Maloku'],
        ['Genc', 'Mazreku'], ['Besa', 'Nuhiu'], ['Dardan', 'Podvorica'], ['Lumnije', 'Sadiku'],
        ['Shpend', 'Sejdiu'], ['Yllka', 'Shabani'], ['Kastriot', 'Syla'], ['Vjollca', 'Tafa'],
        ['Rexhep', 'Ukaj'], ['Arbnora', 'Zeka'], ['Nexhat', 'Demaj'], ['Gentiana', 'Fetahu'],
        ['Fisnik', 'Gjinovci'], ['Drita', 'Hoti'], ['Lavdim', 'Kadriu'], ['Sevdije', 'Beqiri'],
        ['Hamdi', 'Bunjaku'], ['Elona', 'Durmishi'], ['Ramadan', 'Hajrullahu'], ['Shqipe', 'Lushtaku'],
        ['Ermal', 'Xhemajli'], ['Merita', 'Zymberi'], ['Adem', 'Uka'], ['Nora', 'Vitia'],
        ['Bardh', 'Sopjani'], ['Luljeta', 'Pacarizi'], ['Qazim', 'Tahiri'],
    ],

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
