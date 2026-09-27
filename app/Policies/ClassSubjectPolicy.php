<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Who may open a class-subject ("Matematikë in XII-1"): only the teacher who
 * teaches it. Anyone else gets 404 (Policy::authorize), so the page does not
 * even reveal that the class-subject exists.
 */
final class ClassSubjectPolicy extends Policy
{
    /** @param array|null $classSubject ClassSubject::find() */
    public static function teaches(array $user, ?array $classSubject): bool
    {
        return $classSubject !== null
            && $user['role'] === 'teacher'
            && $classSubject['teacher_id'] !== null
            && (int) $classSubject['teacher_id'] === (int) $user['id'];
    }
}
