<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Validator;
use App\Models\Room;

/**
 * Reads and validates the add/edit room form.
 */
final class RoomForm
{
    public static function read(Request $request): array
    {
        return [
            'name'      => $request->string('name'),
            'capacity'  => $request->string('capacity'),
            'is_active' => $request->input('is_active', '1') === '1',
        ];
    }

    public static function fromRoom(array $room): array
    {
        return [
            'name'      => $room['name'],
            'capacity'  => $room['capacity'] !== null ? (string) $room['capacity'] : '',
            'is_active' => (int) $room['is_active'] === 1,
        ];
    }

    /** @return array<string, string> errors by field */
    public static function validate(array $values, ?int $roomId): array
    {
        $capacity = $values['capacity'];

        return (new Validator($values))
            ->required('name', 'Shkruani emrin e sallës, p.sh. “Salla 12” ose “Palestra”.')
            ->maxLength('name', 60, 'Emri mund të ketë deri në 60 karaktere.')
            ->rule('name', !Room::nameTaken($values['name'], $roomId), 'Një sallë me këtë emër ekziston tashmë.')
            ->rule('capacity', $capacity === '' || (ctype_digit($capacity) && (int) $capacity >= 1 && (int) $capacity <= 500),
                   'Shkruani kapacitetin si numër nga 1 deri në 500, ose lëreni bosh.')
            ->errors();
    }

    public static function capacity(array $values): ?int
    {
        return $values['capacity'] === '' ? null : (int) $values['capacity'];
    }
}
