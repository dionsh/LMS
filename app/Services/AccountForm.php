<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Validator;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Models\User;

/**
 * Reads and validates the admin's add/edit account forms (all three roles).
 */
final class AccountForm
{
    public const TITLES = ['Prof.', 'Dr.', 'Mr.', 'MSc.'];

    /** The submitted values, trimmed; fields that do not apply to the role stay empty. */
    public static function read(Request $request): array
    {
        return [
            'first_name'      => $request->string('first_name'),
            'last_name'       => $request->string('last_name'),
            'email'           => mb_strtolower($request->string('email')),
            'phone'           => $request->string('phone'),
            'class_id'        => $request->string('class_id'),
            'student_number'  => $request->string('student_number'),
            'date_of_birth'   => $request->string('date_of_birth'),
            'gender'          => $request->string('gender'),
            'title'            => $request->string('title'),
            'specialization'   => $request->string('specialization'),
            'bio'              => $request->string('bio'),
            'show_on_website'  => $request->input('show_on_website') === '1',
            'timetable_number' => $request->string('timetable_number'),
            'subject_ids'      => $request->ids('subject_ids'),
            'issue_slip'       => $request->input('issue_slip') === '1',
        ];
    }

    /** Values for an empty "add" form. */
    public static function blank(): array
    {
        return [
            'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'class_id' => '',
            'student_number' => '', 'date_of_birth' => '', 'gender' => '', 'title' => 'Prof.',
            'specialization' => '', 'bio' => '', 'show_on_website' => true,
            'timetable_number' => '', 'subject_ids' => [], 'issue_slip' => true,
        ];
    }

    /** Values of an existing account (User::details()) for the edit form. */
    public static function fromUser(array $user): array
    {
        return [
            'first_name'      => $user['first_name'],
            'last_name'       => $user['last_name'],
            'email'           => (string) $user['email'],
            'phone'           => (string) $user['phone'],
            'class_id'        => $user['class_id'] !== null ? (string) $user['class_id'] : '',
            'student_number'  => (string) $user['student_number'],
            'date_of_birth'   => (string) $user['date_of_birth'],
            'gender'          => (string) $user['gender'],
            'title'           => (string) $user['title'],
            'specialization'  => (string) $user['specialization'],
            'bio'              => (string) $user['bio'],
            'show_on_website'  => (int) ($user['show_on_website'] ?? 0) === 1,
            'timetable_number' => $user['timetable_number'] !== null ? (string) $user['timetable_number'] : '',
            'subject_ids'      => $user['role'] === 'teacher' ? TeacherSubject::forTeacher((int) $user['id']) : [],
            'issue_slip'       => false,
        ];
    }

    /**
     * @param int|null $userId the account being edited (null when adding)
     * @return array<string, string> errors by field
     */
    public static function validate(array $values, string $role, ?int $userId, int $academicYearId): array
    {
        $v = new Validator($values);

        $v->required('first_name', 'Shkruani emrin.')
          ->maxLength('first_name', 60, 'Emri mund të ketë deri në 60 karaktere.')
          ->required('last_name', 'Shkruani mbiemrin.')
          ->maxLength('last_name', 60, 'Mbiemri mund të ketë deri në 60 karaktere.')
          ->maxLength('email', 190, 'Email-i është shumë i gjatë.')
          ->email('email', 'Shkruani një adresë të vlefshme, p.sh. emri@shembull.com.')
          ->rule('email', $values['email'] === '' || !User::emailTaken($values['email'], $userId), 'Ky email përdoret nga një llogari tjetër.')
          ->rule('phone', $values['phone'] === '' || preg_match('/^\+?[0-9 ()\-]{6,30}$/', $values['phone']) === 1, 'Shkruani numrin me shifra, p.sh. +383 44 123 456.');

        if ($role === 'student') {
            $classChosen = $values['class_id'] !== '';
            $v->rule('class_id', $classChosen || $userId !== null, 'Zgjidhni klasën e nxënësit.')
              ->rule('class_id', !$classChosen || (ctype_digit($values['class_id']) && SchoolClass::findInYear((int) $values['class_id'], $academicYearId) !== null), 'Zgjidhni një klasë nga lista.')
              ->maxLength('student_number', 30, 'Numri i amzës mund të ketë deri në 30 karaktere.')
              ->rule('student_number', $values['student_number'] === '' || !StudentProfile::numberTaken($values['student_number'], $userId), 'Ky numër amze i përket një nxënësi tjetër.')
              ->rule('date_of_birth', $values['date_of_birth'] === '' || self::validBirthDate($values['date_of_birth']), 'Shkruani një datë të vlefshme lindjeje.')
              ->rule('gender', in_array($values['gender'], ['', 'F', 'M'], true), 'Zgjidhni gjininë nga lista.');
        }

        if ($role === 'teacher') {
            $number = $values['timetable_number'];
            $validNumber = $number === '' || (ctype_digit($number) && (int) $number >= 1 && (int) $number <= 999);

            $v->maxLength('title', 30, 'Titulli mund të ketë deri në 30 karaktere.')
              ->maxLength('specialization', 120, 'Fusha mund të ketë deri në 120 karaktere.')
              ->maxLength('bio', 2000, 'Përshkrimi mund të ketë deri në 2000 karaktere.')
              ->rule('timetable_number', $validNumber, 'Shkruani një numër nga 1 deri në 999, ose lëreni bosh.')
              ->rule('timetable_number', !$validNumber || $number === '' || !TeacherProfile::numberTaken((int) $number, $userId), 'Ky numër në orar i përket një mësimdhënësi tjetër.')
              ->rule('subject_ids', array_diff($values['subject_ids'], Subject::ids()) === [], 'Zgjidhni lëndët nga lista.');
        }

        return $v->errors();
    }

    /** The timetable number as stored: null when left empty. */
    public static function timetableNumber(array $values): ?int
    {
        return $values['timetable_number'] === '' ? null : (int) $values['timetable_number'];
    }

    private static function validBirthDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date
            && $parsed->format('Y') >= '1950'
            && $parsed <= new \DateTimeImmutable('today');
    }
}
