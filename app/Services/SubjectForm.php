<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Validator;
use App\Models\Subject;

/**
 * Reads and validates the admin's add/edit subject form, including which
 * grades study the subject and for how many lessons a week.
 */
final class SubjectForm
{
    /** @param list<int> $gradeLevels the school's grades */
    public static function read(Request $request, array $gradeLevels): array
    {
        $chosen = $request->ids('grades');
        $hours = $request->keyed('hours');

        return [
            'name'            => $request->string('name'),
            'short_name'      => $request->string('short_name'),
            'description'     => $request->string('description'),
            'is_active'       => $request->input('is_active') === '1',
            'show_on_website' => $request->input('show_on_website') === '1',
            'fills_subject_id' => $request->string('fills_subject_id'),
            'grades'          => array_values(array_intersect($chosen, $gradeLevels)),
            'hours'           => array_intersect_key($hours, array_flip($gradeLevels)),
        ];
    }

    public static function blank(): array
    {
        return [
            'name' => '', 'short_name' => '', 'description' => '', 'is_active' => true,
            'show_on_website' => true, 'fills_subject_id' => '', 'grades' => [], 'hours' => [],
        ];
    }

    /** @param array<int, ?int> $curriculum Curriculum::forSubject() */
    public static function fromSubject(array $subject, array $curriculum): array
    {
        return [
            'name'            => $subject['name'],
            'short_name'      => $subject['short_name'],
            'description'     => (string) $subject['description'],
            'is_active'       => (int) $subject['is_active'] === 1,
            'show_on_website' => (int) $subject['show_on_website'] === 1,
            'fills_subject_id' => $subject['fills_subject_id'] !== null ? (string) $subject['fills_subject_id'] : '',
            'grades'          => array_keys($curriculum),
            'hours'           => array_map(static fn (?int $h): string => $h === null ? '' : (string) $h, $curriculum),
        ];
    }

    /**
     * @param list<int> $places subjects an elective can take the place of
     * @return array<string, string> errors by field (hours per grade: "hours-10")
     */
    public static function validate(array $values, ?int $subjectId, array $places): array
    {
        $v = new Validator($values);

        if ($values['fills_subject_id'] !== '') {
            $v->rule('fills_subject_id', ctype_digit($values['fills_subject_id']) && in_array((int) $values['fills_subject_id'], $places, true), 'Zgjidhni lëndën nga lista.')
              ->rule('fills_subject_id', $subjectId === null || !Subject::hasElectives($subjectId), 'Kjo lëndë ka vetë lëndë zgjedhore në vend të saj, prandaj nuk mund të jetë zgjedhore.')
              ->rule('fills_subject_id', $values['grades'] === [], 'Lënda zgjedhore merr orët e lëndës që zëvendëson: mos i shënoni klasat më poshtë.');
        }

        $v->required('name', 'Shkruani emrin e lëndës.')
          ->maxLength('name', 100, 'Emri mund të ketë deri në 100 karaktere.')
          ->rule('name', !Subject::nameTaken($values['name'], $subjectId), 'Kjo lëndë ekziston tashmë.')
          ->maxLength('short_name', 12, 'Shkurtimi mund të ketë deri në 12 karaktere.')
          ->maxLength('description', 2000, 'Përshkrimi mund të ketë deri në 2000 karaktere.');

        foreach ($values['grades'] as $level) {
            $v->rule('hours-' . $level, WeeklyHours::valid($values['hours'][$level] ?? ''), WeeklyHours::MESSAGE);
        }

        return $v->errors();
    }

    /** Typed values for Subject::create()/update(). An empty short name is taken from the name. */
    public static function data(array $values): array
    {
        return [
            'name'            => $values['name'],
            'short_name'      => $values['short_name'] !== '' ? $values['short_name'] : mb_substr($values['name'], 0, 12),
            'description'     => $values['description'] !== '' ? $values['description'] : null,
            'is_active'       => $values['is_active'],
            'show_on_website' => $values['show_on_website'],
            'fills_subject_id' => $values['fills_subject_id'] !== '' ? (int) $values['fills_subject_id'] : null,
        ];
    }

    /** The chosen grades with their hours: [level => ?hours] for Curriculum::saveSubject(). */
    public static function curriculum(array $values): array
    {
        $curriculum = [];
        foreach ($values['grades'] as $level) {
            $curriculum[$level] = WeeklyHours::parse($values['hours'][$level] ?? '');
        }
        ksort($curriculum);

        return $curriculum;
    }
}
