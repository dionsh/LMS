<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Subjects (lëndët) the school teaches. Which grades study them is the
 * curriculum (Curriculum); who teaches them is TeacherSubject / ClassSubject.
 *
 * An elective (fills_subject_id) is not in the curriculum itself: a class that
 * chooses it takes it in place of another subject, with that subject's hours
 * (Orientim në karrierë in place of Mësim zgjedhor).
 */
final class Subject extends Model
{
    /**
     * Every subject with its grades ("10,11,12"), how many teachers teach it
     * and in how many classes of the given year it is taught.
     */
    public static function overview(int $academicYearId): array
    {
        return self::fetchAll(
            'SELECT s.id, s.name, s.short_name, s.is_active, s.show_on_website, s.sort_order, s.fills_subject_id, f.name AS fills_name,
                    (SELECT GROUP_CONCAT(gs.grade_level ORDER BY gs.grade_level) FROM grade_subjects gs WHERE gs.subject_id = s.id) AS grades,
                    (SELECT COUNT(*) FROM teacher_subjects ts WHERE ts.subject_id = s.id) AS teachers,
                    (SELECT COUNT(*) FROM class_subjects cs JOIN classes c ON c.id = cs.class_id
                      WHERE cs.subject_id = s.id AND c.academic_year_id = :year) AS classes
               FROM subjects s
               LEFT JOIN subjects f ON f.id = s.fills_subject_id
              ORDER BY s.sort_order, s.name',
            ['year' => $academicYearId]
        );
    }

    /** Subjects for checkboxes and <select>s: [[id, name, short_name, is_active, fills_subject_id], …] */
    public static function options(bool $activeOnly = false): array
    {
        return self::fetchAll(
            'SELECT id, name, short_name, is_active, fills_subject_id FROM subjects'
            . ($activeOnly ? ' WHERE is_active = 1' : '')
            . ' ORDER BY sort_order, name'
        );
    }

    /** @return list<int> */
    public static function ids(): array
    {
        return array_map('intval', array_column(self::fetchAll('SELECT id FROM subjects'), 'id'));
    }

    public static function find(int $id): ?array
    {
        return self::fetchOne(
            'SELECT id, name, short_name, description, is_active, show_on_website, sort_order, fills_subject_id FROM subjects WHERE id = ?',
            [$id]
        );
    }

    public static function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return self::fetchValue('SELECT 1 FROM subjects WHERE name = ? AND id <> ?', [$name, $exceptId ?? 0]) !== null;
    }

    /** @param array{name: string, short_name: string, description: ?string, is_active: bool, show_on_website: bool, fills_subject_id: ?int} $data */
    public static function create(array $data): int
    {
        return self::insert(
            'INSERT INTO subjects (name, short_name, description, is_active, show_on_website, fills_subject_id, sort_order)
             SELECT :name, :short_name, :description, :active, :website, :fills, COALESCE(MAX(sort_order), 0) + 1 FROM subjects',
            [
                'name'        => $data['name'],
                'short_name'  => $data['short_name'],
                'description' => $data['description'],
                'active'      => $data['is_active'],
                'website'     => $data['show_on_website'],
                'fills'       => $data['fills_subject_id'],
            ]
        );
    }

    /** @param array{name: string, short_name: string, description: ?string, is_active: bool, show_on_website: bool, fills_subject_id: ?int} $data */
    public static function update(int $id, array $data): void
    {
        self::execute(
            'UPDATE subjects SET name = ?, short_name = ?, description = ?, is_active = ?, show_on_website = ?, fills_subject_id = ? WHERE id = ?',
            [$data['name'], $data['short_name'], $data['description'], $data['is_active'], $data['show_on_website'], $data['fills_subject_id'], $id]
        );
    }

    /**
     * The choices a class has for a subject that has electives, the subject first:
     * [subject id => [id => name, …]] (active electives only).
     */
    public static function electiveChoices(): array
    {
        $choices = [];
        foreach (self::fetchAll(
            'SELECT e.id, e.name, f.id AS fills_id, f.name AS fills_name
               FROM subjects e
               JOIN subjects f ON f.id = e.fills_subject_id
              WHERE e.is_active = 1
              ORDER BY f.sort_order, e.sort_order, e.name'
        ) as $row) {
            $choices[(int) $row['fills_id']] ??= [(int) $row['fills_id'] => $row['fills_name']];
            $choices[(int) $row['fills_id']][(int) $row['id']] = $row['name'];
        }

        return $choices;
    }

    /** Does another subject stand in for this one (is it the place of an elective)? */
    public static function hasElectives(int $id): bool
    {
        return self::fetchValue('SELECT 1 FROM subjects WHERE fills_subject_id = ? LIMIT 1', [$id]) !== null;
    }

    /** Taught in any class, in any year? Then it can only be deactivated, not deleted. */
    public static function isTaught(int $id): bool
    {
        return self::fetchValue('SELECT 1 FROM class_subjects WHERE subject_id = ? LIMIT 1', [$id]) !== null;
    }

    /** Deletes the subject with its curriculum rows and teacher links. Only for a subject that is not taught. */
    public static function delete(int $id): void
    {
        self::execute('DELETE FROM subjects WHERE id = ?', [$id]);
    }

    /** Teachers who teach this subject: [[id, first_name, last_name, title, status], …] */
    public static function teachers(int $id): array
    {
        return self::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.status, tp.title
               FROM teacher_subjects ts
               JOIN users u ON u.id = ts.teacher_id
               LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
              WHERE ts.subject_id = ?
              ORDER BY u.last_name, u.first_name',
            [$id]
        );
    }
}
