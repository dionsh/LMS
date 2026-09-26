<?php

declare(strict_types=1);

/*
 * Global helper functions used by controllers and views.
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Models\Setting;
use App\Support\Format;

/** Config value in dot notation: config('db.host'). */
function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** Escape a value for HTML output (text and attributes). Use for EVERY value printed in a view. */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Absolute filesystem path inside the project: root_path('storage/logs'). */
function root_path(string $path = ''): string
{
    return ROOT_PATH . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

/** URL of an internal page: url('/lajme') → '/lms-system/lajme'. */
function url(string $path = '/', array $query = []): string
{
    $url = Config::basePath() . '/' . ltrim($path, '/');

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

/** URL of a named route: route('news.show', ['slug' => $post['slug']]). */
function route(string $name, array $params = [], array $query = []): string
{
    return Router::current()->urlFor($name, $params, $query);
}

/** URL of a file in public/assets, with a cache-busting version: asset('css/app.css'). */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = ROOT_PATH . '/public/assets/' . $path;

    return url('assets/' . $path, is_file($file) ? ['v' => (string) filemtime($file)] : []);
}

/** Redirect to an internal path: return redirect('/nxenesi'); */
function redirect(string $path, int $status = 302): Response
{
    return Response::redirect(url($path), $status);
}

/** Render a partial template inside a view: <?= partial('partials/flash') ?> */
function partial(string $view, array $data = []): string
{
    return View::partial($view, $data);
}

function csrf_token(): string
{
    return Csrf::token();
}

/** Hidden input carrying the CSRF token — put it in EVERY <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

/** Previously submitted form value (after a validation redirect). */
function old(string $key, string $default = ''): string
{
    return Session::old($key, $default);
}

/** School information from the `settings` table: setting('school_name'). */
function setting(string $key, string $default = ''): string
{
    return Setting::get($key, $default);
}

/** Albanian date: sq_date($row['due_at'], 'long') → "e premte, 2 tetor 2026". See Format::date(). */
function sq_date(DateTimeInterface|string|null $value, string $style = 'date'): string
{
    return Format::date($value, $style);
}

/** Albanian number: sq_number(4.25, 2) → "4,25". */
function sq_number(float|int $value, int $decimals = 0): string
{
    return Format::number($value, $decimals);
}

function is_https(): bool
{
    $https = $_SERVER['HTTPS'] ?? '';

    return ($https !== '' && strtolower((string) $https) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}
