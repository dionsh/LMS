<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

/**
 * Holds freshly issued login slips in the issuing admin's own session so the
 * page can be printed (and reloaded) — for 30 minutes at most, or until the
 * admin presses "Mbaro". Nothing is stored in the database; other sessions
 * can never see them.
 */
final class SlipStore
{
    private const KEY = '_login_slips';
    public const LIFETIME = 1800;

    /** @return string id for /admin/fletet-e-hyrjes/{id} */
    public static function put(string $title, array $slips, string $returnTo): string
    {
        $all = self::fresh();
        $id = bin2hex(random_bytes(8));
        $all[$id] = ['title' => $title, 'slips' => $slips, 'return_to' => $returnTo, 'created_at' => time()];
        Session::set(self::KEY, $all);

        return $id;
    }

    public static function get(string $id): ?array
    {
        return self::fresh()[$id] ?? null;
    }

    public static function forget(string $id): void
    {
        $all = self::fresh();
        unset($all[$id]);
        Session::set(self::KEY, $all);
    }

    /** Stored batches younger than LIFETIME (older ones are dropped). */
    private static function fresh(): array
    {
        $all = Session::get(self::KEY, []);
        $all = is_array($all) ? $all : [];
        $fresh = array_filter($all, static fn (array $batch): bool => time() - $batch['created_at'] < self::LIFETIME);

        if (count($fresh) !== count($all)) {
            Session::set(self::KEY, $fresh);
        }

        return $fresh;
    }
}
