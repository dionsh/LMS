<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Config;
use App\Core\Request;
use DateTimeImmutable;

/**
 * "Now" for pages that depend on the time of day (the lesson in progress,
 * today's lessons). In development only, ?tani=2026-10-02T09:10 pretends it
 * is that moment, so these pages can be checked at any hour; production
 * always uses the real time.
 */
final class Clock
{
    public static function now(): DateTimeImmutable
    {
        if (Config::get('app.env') === 'development') {
            $override = Request::current()?->query('tani');
            if (is_string($override) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $override) === 1) {
                $moment = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $override);
                if ($moment !== false) {
                    return $moment;
                }
            }
        }

        return new DateTimeImmutable();
    }
}
