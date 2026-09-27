<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Validator;
use App\Models\AcademicYear;
use DateTimeImmutable;

/**
 * Reads and validates a school year with its two semesters (gjysmëvjetorët).
 */
final class YearForm
{
    public const TERM_NAMES = [1 => 'Gjysmëvjetori i parë', 2 => 'Gjysmëvjetori i dytë'];

    private const FIELDS = ['name', 'starts_on', 'ends_on', 'term1_starts_on', 'term1_ends_on', 'term2_starts_on', 'term2_ends_on'];

    public static function read(Request $request): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = $request->string($field);
        }

        return $values;
    }

    /** The year after $previous, with every date moved on by one year (e.g. 2027/2028). */
    public static function following(?array $previous, array $terms): array
    {
        if ($previous === null) {
            $start = (int) date('Y');
            return self::values($start . '/' . ($start + 1), "{$start}-09-01", ($start + 1) . '-06-30', [
                1 => ["{$start}-09-01", ($start + 1) . '-01-17'],
                2 => [($start + 1) . '-01-18', ($start + 1) . '-06-30'],
            ]);
        }

        $shift = static fn (string $date): string => (new DateTimeImmutable($date))->modify('+1 year')->format('Y-m-d');
        [$from, $to] = array_map('intval', explode('/', $previous['name']) + [1 => 0]);
        $termDates = [];
        foreach ($terms as $term) {
            $termDates[(int) $term['sort_order']] = [$shift($term['starts_on']), $shift($term['ends_on'])];
        }

        return self::values(($from + 1) . '/' . ($to + 1), $shift($previous['starts_on']), $shift($previous['ends_on']), $termDates);
    }

    public static function fromYear(array $year, array $terms): array
    {
        $termDates = [];
        foreach ($terms as $term) {
            $termDates[(int) $term['sort_order']] = [$term['starts_on'], $term['ends_on']];
        }

        return self::values($year['name'], $year['starts_on'], $year['ends_on'], $termDates);
    }

    /** @return array<string, string> errors by field */
    public static function validate(array $values, ?int $yearId): array
    {
        $v = new Validator($values);
        $dates = [];
        foreach (array_slice(self::FIELDS, 1) as $field) {
            $dates[$field] = self::date($values[$field]);
            $v->rule($field, $dates[$field] !== null, 'Shkruani një datë të vlefshme.');
        }

        $v->rule('name', preg_match('#^(\d{4})/(\d{4})$#', $values['name'], $m) === 1 && (int) $m[2] === (int) $m[1] + 1,
                 'Shkruani vitin si “2027/2028”.')
          ->rule('name', !AcademicYear::nameTaken($values['name'], $yearId), 'Ky vit shkollor ekziston tashmë.');

        if (!in_array(null, $dates, true)) {
            $v->rule('ends_on', $dates['ends_on'] > $dates['starts_on'], 'Viti duhet të mbarojë pasi fillon.')
              ->rule('term1_starts_on', $dates['term1_starts_on'] >= $dates['starts_on'], 'Gjysmëvjetori nuk mund të fillojë para vitit shkollor.')
              ->rule('term1_ends_on', $dates['term1_ends_on'] > $dates['term1_starts_on'], 'Gjysmëvjetori duhet të mbarojë pasi fillon.')
              ->rule('term2_starts_on', $dates['term2_starts_on'] > $dates['term1_ends_on'], 'Gjysmëvjetori i dytë fillon pasi mbaron i pari.')
              ->rule('term2_ends_on', $dates['term2_ends_on'] > $dates['term2_starts_on'], 'Gjysmëvjetori duhet të mbarojë pasi fillon.')
              ->rule('term2_ends_on', $dates['term2_ends_on'] <= $dates['ends_on'], 'Gjysmëvjetori nuk mund të mbarojë pas vitit shkollor.');
        }

        return $v->errors();
    }

    /** [sort order => [starts_on, ends_on]] from validated values. */
    public static function terms(array $values): array
    {
        return [
            1 => [$values['term1_starts_on'], $values['term1_ends_on']],
            2 => [$values['term2_starts_on'], $values['term2_ends_on']],
        ];
    }

    private static function values(string $name, string $startsOn, string $endsOn, array $terms): array
    {
        return [
            'name'            => $name,
            'starts_on'       => $startsOn,
            'ends_on'         => $endsOn,
            'term1_starts_on' => $terms[1][0] ?? '',
            'term1_ends_on'   => $terms[1][1] ?? '',
            'term2_starts_on' => $terms[2][0] ?? '',
            'term2_ends_on'   => $terms[2][1] ?? '',
        ];
    }

    /** 'Y-m-d' string → date, or null when it is not a real date. */
    private static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
