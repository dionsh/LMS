<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    /** Columns safe to keep in memory for the signed-in user (never the password hash). */
    private const PUBLIC_COLUMNS = 'id, role, username, first_name, last_name, email, phone, avatar_path,
                                    status, must_change_password, last_login_at, created_at';

    public static function findById(int $id): ?array
    {
        return self::fetchOne('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = ?', [$id]);
    }

    /**
     * The account for a login identifier, including its password hash.
     * Usernames never contain "@", so the identifier decides which column is searched.
     */
    public static function findForLogin(string $identifier): ?array
    {
        $column = str_contains($identifier, '@') ? 'email' : 'username';

        return self::fetchOne(
            'SELECT ' . self::PUBLIC_COLUMNS . ', password_hash FROM users WHERE ' . $column . ' = ? LIMIT 1',
            [$identifier]
        );
    }

    public static function passwordHash(int $id): ?string
    {
        $hash = self::fetchValue('SELECT password_hash FROM users WHERE id = ?', [$id]);

        return is_string($hash) ? $hash : null;
    }

    /** Store a new password. $temporary = true means it was issued by the school and must be changed. */
    public static function updatePassword(int $id, string $plainPassword, bool $temporary = false): void
    {
        self::execute(
            'UPDATE users SET password_hash = ?, must_change_password = ? WHERE id = ?',
            [password_hash($plainPassword, PASSWORD_DEFAULT), $temporary, $id]
        );
    }

    /** Re-hash with the current algorithm/cost (after a successful login), keeping everything else. */
    public static function rehashPassword(int $id, string $plainPassword): void
    {
        self::execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
    }

    /** 'active' | 'inactive'. A deactivated user is signed out on their next request. */
    public static function setStatus(int $id, string $status): void
    {
        self::execute('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function touchLastLogin(int $id): void
    {
        self::execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function updateContact(int $id, ?string $email, ?string $phone): void
    {
        self::execute('UPDATE users SET email = ?, phone = ? WHERE id = ?', [$email, $phone, $id]);
    }

    /** Names and contact details (admin editing an account). */
    public static function updateDetails(int $id, string $firstName, string $lastName, ?string $email, ?string $phone): void
    {
        self::execute(
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?',
            [$firstName, $lastName, $email, $phone, $id]
        );
    }

    public static function countActiveAdmins(): int
    {
        return (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
    }

    /**
     * Everything the admin edit page shows about one account: user, profile,
     * the student's class this year, the teacher's homeroom class this year.
     */
    public static function details(int $id, int $academicYearId): ?array
    {
        return self::fetchOne(
            'SELECT u.id, u.role, u.username, u.first_name, u.last_name, u.email, u.phone, u.status,
                    u.must_change_password, u.last_login_at, u.created_at,
                    u.password_hash IS NOT NULL AS has_credentials,
                    sp.student_number, sp.date_of_birth, sp.gender,
                    tp.title, tp.specialization, tp.bio, tp.show_on_website, tp.timetable_number,
                    sc.id AS class_id, sc.grade_level AS class_grade, sc.section AS class_section,
                    hc.id AS homeroom_class_id, hc.grade_level AS homeroom_grade, hc.section AS homeroom_section
               FROM users u
               LEFT JOIN student_profiles sp ON sp.user_id = u.id
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
               LEFT JOIN enrollments en ON en.student_id = u.id AND en.academic_year_id = :year1
               LEFT JOIN classes sc ON sc.id = en.class_id
               LEFT JOIN classes hc ON hc.homeroom_teacher_id = u.id AND hc.academic_year_id = :year2
              WHERE u.id = :id',
            ['year1' => $academicYearId, 'year2' => $academicYearId, 'id' => $id]
        );
    }

    /**
     * Searchable, filterable account lists for the admin.
     *
     * @param array{role?: string, q?: string, status?: string, class_id?: int,
     *              credentials?: 'none'|'issued', never_signed_in?: bool} $filters
     */
    public static function search(int $academicYearId, array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::searchConditions($filters);

        $order = match ($filters['role'] ?? null) {
            'student' => 'sc.grade_level IS NULL, sc.grade_level, sc.section, u.last_name, u.first_name',
            'teacher' => 'u.last_name, u.first_name',
            default   => "FIELD(u.role, 'admin', 'teacher', 'student'), u.last_name, u.first_name",
        };

        return self::fetchAll(
            'SELECT u.id, u.role, u.first_name, u.last_name, u.username, u.email, u.status, u.last_login_at,
                    u.password_hash IS NOT NULL AS has_credentials,
                    tp.title, tp.timetable_number,
                    (SELECT GROUP_CONCAT(s.short_name ORDER BY s.sort_order SEPARATOR \', \')
                       FROM teacher_subjects ts JOIN subjects s ON s.id = ts.subject_id
                      WHERE ts.teacher_id = u.id) AS subjects,
                    sc.id AS class_id, sc.grade_level AS class_grade, sc.section AS class_section,
                    hc.id AS homeroom_class_id, hc.grade_level AS homeroom_grade, hc.section AS homeroom_section
               FROM users u
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
               LEFT JOIN enrollments en ON en.student_id = u.id AND en.academic_year_id = :year1
               LEFT JOIN classes sc ON sc.id = en.class_id
               LEFT JOIN classes hc ON hc.homeroom_teacher_id = u.id AND hc.academic_year_id = :year2
              WHERE ' . $where . '
              ORDER BY ' . $order . '
              LIMIT :limit OFFSET :offset',
            $params + ['year1' => $academicYearId, 'year2' => $academicYearId, 'limit' => $limit, 'offset' => $offset]
        );
    }

    public static function countSearch(int $academicYearId, array $filters): int
    {
        [$where, $params] = self::searchConditions($filters);

        return (int) self::fetchValue(
            'SELECT COUNT(*)
               FROM users u
               LEFT JOIN enrollments en ON en.student_id = u.id AND en.academic_year_id = :year1
               LEFT JOIN classes sc ON sc.id = en.class_id
              WHERE ' . $where,
            $params + ['year1' => $academicYearId]
        );
    }

    /**
     * WHERE clause for search()/countSearch(). Only fixed SQL fragments are
     * concatenated; every value is a bound parameter.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function searchConditions(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (isset($filters['role']) && in_array($filters['role'], ['admin', 'teacher', 'student'], true)) {
            $where[] = 'u.role = :role';
            $params['role'] = $filters['role'];
        }

        if (isset($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
            $where[] = 'u.status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['class_id'])) {
            $where[] = 'sc.id = :class_id';
            $params['class_id'] = (int) $filters['class_id'];
        }

        if (($filters['credentials'] ?? null) === 'none') {
            $where[] = 'u.password_hash IS NULL';
        } elseif (($filters['credentials'] ?? null) === 'issued') {
            $where[] = 'u.password_hash IS NOT NULL';
        }

        if (!empty($filters['never_signed_in'])) {
            $where[] = 'u.last_login_at IS NULL';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '\\%_') . '%';
            $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE :q1 OR u.username LIKE :q2 OR u.email LIKE :q3)";
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        return [implode(' AND ', $where), $params];
    }

    public static function usernameExists(string $username): bool
    {
        return self::fetchValue('SELECT 1 FROM users WHERE username = ?', [$username]) !== null;
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return self::fetchValue(
            'SELECT 1 FROM users WHERE email = ? AND id <> ?',
            [$email, $exceptId ?? 0]
        ) !== null;
    }

    /** A person of a role by exact name (used to keep data imports from creating duplicates). */
    public static function findByName(string $role, string $firstName, string $lastName): ?array
    {
        return self::fetchOne(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE role = ? AND first_name = ? AND last_name = ? LIMIT 1',
            [$role, $firstName, $lastName]
        );
    }

    /**
     * @param array{role: string, username: string, first_name: string, last_name: string,
     *              email?: ?string, password: ?string, status?: string, must_change_password?: bool} $data
     *              password null = a record without sign-in credentials (cannot sign in yet)
     */
    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO users (role, username, first_name, last_name, email, password_hash, status, must_change_password)
             VALUES (:role, :username, :first_name, :last_name, :email, :password_hash, :status, :must_change)',
            [
                'role'          => $data['role'],
                'username'      => $data['username'],
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'email'         => $data['email'] ?? null,
                'password_hash' => $data['password'] === null ? null : password_hash($data['password'], PASSWORD_DEFAULT),
                'status'        => $data['status'] ?? 'active',
                'must_change'   => $data['must_change_password'] ?? true,
            ]
        );
    }

    /** Active accounts per role: ['student' => 0, 'teacher' => 0, 'admin' => 1]. */
    public static function countActiveByRole(): array
    {
        $counts = ['student' => 0, 'teacher' => 0, 'admin' => 0];
        $rows = self::fetchAll("SELECT role, COUNT(*) AS total FROM users WHERE status = 'active' GROUP BY role");

        foreach ($rows as $row) {
            $counts[$row['role']] = (int) $row['total'];
        }

        return $counts;
    }

    /** Active people who have never signed in (no slip issued yet, or slip not used yet). */
    public static function countNeverSignedIn(): int
    {
        return (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active' AND last_login_at IS NULL");
    }

    /** Active people who have no sign-in credentials yet. */
    public static function countWithoutCredentials(): int
    {
        return (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active' AND password_hash IS NULL");
    }

    /**
     * Active teachers for <select>s, by surname, with their timetable number and
     * the subjects they teach (subject_ids: list<int>). Inactive teachers are
     * included only when listed in $alsoInclude (e.g. still assigned somewhere).
     *
     * @param list<int> $alsoInclude
     */
    public static function teacherOptions(array $alsoInclude = []): array
    {
        $extra = $alsoInclude === [] ? '' : ' OR u.id IN (' . implode(', ', array_fill(0, count($alsoInclude), '?')) . ')';

        $rows = self::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.status, tp.title, tp.timetable_number,
                    (SELECT GROUP_CONCAT(ts.subject_id) FROM teacher_subjects ts WHERE ts.teacher_id = u.id) AS subject_ids
               FROM users u
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
              WHERE u.role = 'teacher' AND (u.status = 'active'{$extra})
              ORDER BY u.last_name, u.first_name",
            array_values(array_map('intval', $alsoInclude))
        );

        foreach ($rows as &$row) {
            $row['subject_ids'] = $row['subject_ids'] === null ? [] : array_map('intval', explode(',', (string) $row['subject_ids']));
        }

        return $rows;
    }

    /**
     * Every teacher with the class they are homeroom teacher of in the given year (if any).
     * has_credentials = 0 means a record without sign-in credentials.
     */
    public static function teachersOverview(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.username, u.status, u.last_login_at,
                    u.password_hash IS NOT NULL AS has_credentials,
                    tp.title,
                    c.id AS homeroom_class_id, c.grade_level, c.section
               FROM users u
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
               LEFT JOIN classes c ON c.homeroom_teacher_id = u.id AND c.academic_year_id = :year
              WHERE u.role = :role
              ORDER BY u.last_name, u.first_name',
            ['year' => $academicYearId, 'role' => 'teacher']
        );
    }

    /** Every student with their class in the given year (if enrolled). */
    public static function studentsOverview(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.username, u.status, u.last_login_at,
                    u.password_hash IS NOT NULL AS has_credentials,
                    c.id AS class_id, c.grade_level, c.section
               FROM users u
               LEFT JOIN enrollments en ON en.student_id = u.id AND en.academic_year_id = :year
               LEFT JOIN classes c ON c.id = en.class_id
              WHERE u.role = :role
              ORDER BY c.grade_level IS NULL, c.grade_level, c.section, u.last_name, u.first_name',
            ['year' => $academicYearId, 'role' => 'student']
        );
    }
}
