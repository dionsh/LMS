<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PortalController;
use App\Core\HttpException;
use App\Core\Response;
use App\Services\SlipStore;

/**
 * The printable login slips — only in the session of the admin who issued
 * them, for at most 30 minutes.
 */
final class SlipController extends PortalController
{
    /** GET /admin/fletet-e-hyrjes/{batch} */
    public function show(string $batch): Response
    {
        $slips = SlipStore::get($batch) ?? throw new HttpException(404);

        return $this->page('admin/slips', [
            'title'  => $slips['title'],
            'batch'  => $batch,
            'slips'  => $slips,
            'styles' => ['slips'],
            'portal' => absolute_url('/hyr'),
        ]);
    }

    /** POST /admin/fletet-e-hyrjes/{batch}/mbaro — remove the passwords from the session */
    public function finish(string $batch): Response
    {
        $slips = SlipStore::get($batch);
        SlipStore::forget($batch);

        return redirect($slips !== null ? $slips['return_to'] : '/admin');
    }
}
