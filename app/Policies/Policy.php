<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\HttpException;

/**
 * Base for ownership checks — the second layer of authorization.
 *
 * The role guard on a route group decides WHO may open an area (e.g. only
 * teachers under /mesimdhenesi). A policy decides WHICH records they may touch:
 * a teacher only their own class-subjects, a student only their own work.
 *
 *   final class ClassSubjectPolicy extends Policy
 *   {
 *       public static function teaches(array $teacher, array $classSubject): bool { … }
 *   }
 *   ClassSubjectPolicy::authorize(ClassSubjectPolicy::teaches($user, $cs));
 *
 * A failed check answers 404, not 403: people must not learn that someone
 * else's assignment, grade or file exists.
 */
abstract class Policy
{
    public static function authorize(bool $allowed): void
    {
        if (!$allowed) {
            throw new HttpException(404);
        }
    }
}
