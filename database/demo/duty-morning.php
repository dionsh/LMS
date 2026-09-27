<?php

declare(strict_types=1);

/*
 * REAL — daily duty (kujdestaria e ditës) of the morning shift, from the
 * official timetable "Orari i mësimit, Paradite, 2026/2027" (21.09.2026).
 * Teachers are given by their number on the staff list (staff.php).
 * Places left empty on the printed timetable are left out here too.
 *
 *   day (1 = e hënë) => [post => [teacher numbers, in place order]]
 */

return [
    1 => ['Salla' => [27], 'Kati i parë' => [36, 70], 'Kati i dytë' => [63, 42], 'Kati i tretë' => [29, 47]],
    2 => ['Salla' => [60], 'Kati i parë' => [61, 35], 'Kati i dytë' => [72, 46], 'Kati i tretë' => [55, 57]],
    3 => ['Salla' => [14], 'Kati i parë' => [50, 48], 'Kati i dytë' => [71],     'Kati i tretë' => [25]],
    4 => ['Salla' => [9],  'Kati i parë' => [66, 10], 'Kati i dytë' => [54, 17], 'Kati i tretë' => [41, 58]],
    5 => ['Salla' => [44], 'Kati i parë' => [22, 7],  'Kati i dytë' => [13],     'Kati i tretë' => [39]],
];
