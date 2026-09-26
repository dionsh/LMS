<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Response;

final class HomeController extends Controller
{
    /** Ballina — a placeholder until the public site is built (T17). */
    public function index(): Response
    {
        return $this->view('site/home');
    }
}
