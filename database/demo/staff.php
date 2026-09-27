<?php

declare(strict_types=1);

/*
 * REAL — the school's teachers for 2026/2027 and their numbers, from the
 * school's list "Lista e mësimdhënësve për vitin shkollor 2026–2027" (signed
 * by the principal). The number is the one used in every cell of the printed
 * timetable. Number 52 is empty on the list; 74–76 are marked only "mz",
 * which the school says means Mësim zgjedhor (no teacher named).
 *
 * subjects: filled in as the school says which subject each teacher teaches.
 * They are given to a teacher who has none in the database yet (subjects
 * ticked under Mësimdhënësit are kept). The timetable in numbers
 * (timetable-morning.php) becomes lessons once all of a class's teachers
 * have their subjects.
 *
 *   number => [first name, last name, subjects]
 */

return [
    1  => ['Elmaze', 'Grainca', []],
    2  => ['Nexhmije', 'Bega', []],
    3  => ['Hysnije', 'Mustafa', []],
    4  => ['Azbije', 'Neziri', []],
    5  => ['Arjeta', 'Avdyli', []],
    6  => ['Ardit', 'Qalaj', []],
    7  => ['Merita', 'Lekaj', []],
    8  => ['Drenushe', 'Ramadani', []],
    9  => ['Burbuqe', 'Bucaliu', []],
    10 => ['Mërgime', 'Grainca', []],
    11 => ['Shkumbin', 'Halili', []],
    12 => ['Adnan', 'Vishi', []],
    13 => ['Teuta', 'Tahiri', []],
    14 => ['Muhabere', 'Tahiri', []],
    15 => ['Ardiana', 'Ilazi', []],
    16 => ['Mirvete', 'Hyseni', []],
    17 => ['Arbenita', 'Grainca', []],
    18 => ['Adelina', 'Qerimi', []],
    19 => ['Jetulla', 'Sylejmani', []],
    20 => ['Besim', 'Qiriqi', []],
    21 => ['Lumbardha', 'Bajraliu', []],
    22 => ['Arjete', 'Zejnullahu', []],
    23 => ['Muzafer', 'Hafizi', []],
    24 => ['Lindrit', 'Rysha', []],
    25 => ['Avni', 'Hashani', []],
    26 => ['Enver', 'Bajrami', ['Matematikë', 'Orientim në karrierë']],
    27 => ['Naser', 'Tahiri', []],
    28 => ['Fedona', 'Beqiri', []],
    29 => ['Minire', 'Kurteshi', []],
    30 => ['Afërdita', 'Hoxha', []],
    31 => ['Faton', 'Hyseni', []],
    32 => ['Albatrit', 'Shaqiri', []],
    33 => ['Drenusha', 'Bega', []],
    34 => ['Margarita', 'Azizi', []],
    35 => ['Bajram', 'Sejdiu', []],
    36 => ['Blerta', 'Reçica', []],
    37 => ['Njazi', 'Hyseni', []],
    38 => ['Fisnik', 'Jahiri', []],
    39 => ['Armir', 'Aliu', []],
    40 => ['Qendresa', 'Berisha', []],
    41 => ['Burbuqe', 'Bega', []],
    42 => ['Fatbardha', 'Avdiu', []],
    43 => ['Fitore', 'Ramadani', []],
    44 => ['Ylber', 'Ukshini', []],
    45 => ['Donika', 'Krasniqi', []],
    46 => ['Shukri', 'Nuha', []],
    47 => ['Milaim', 'Rushiti', []],
    48 => ['Vildane', 'Ahmeti', []],
    49 => ['Besim', 'Idrizi', []],
    50 => ['Genc', 'Hoxha', []],
    51 => ['Murtez', 'Kurtaj', []],
    53 => ['Besarta', 'Ajeti', []],        // "Besata Ajeti" on the printed timetable
    54 => ['Flakë', 'Musliu', []],
    55 => ['Behar', 'Krasniqi', []],
    56 => ['Jehona', 'Ejupi', []],
    57 => ['Mimoza', 'Jashari', []],
    58 => ['Gentiana', 'Çerkini', []],
    59 => ['Driton', 'Fejzullahu', []],
    60 => ['Bashkim', 'Thaçi', []],
    61 => ['Arben', 'Hyseni', []],
    62 => ['Jasemine', 'Ahmeti', []],
    63 => ['Vlona', 'Rexhepi', []],
    64 => ['Vildane', 'Jashari', []],
    65 => ['Ylber', 'Ibrahimi', []],
    66 => ['Albulena', 'Ajvazi', []],
    67 => ['Edona', 'Krasniqi', []],
    68 => ['Dardan', 'Aliu', []],
    69 => ['Enkelejdë', 'Bytyçi', []],
    70 => ['Dhurata', 'Sahiti', []],
    71 => ['Donika', 'Avdiu', []],
    72 => ['Kushtrim', 'Krosa', []],
    73 => ['Avni', 'Islami', []],
];
