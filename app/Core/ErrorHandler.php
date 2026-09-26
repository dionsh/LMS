<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Labels;
use ErrorException;
use Throwable;

/**
 * Turns every error into a proper response:
 * - HttpException (404, 403, 419 …) → the Albanian error page with that status;
 * - anything else → logged, then a generic 500 page (details only in development).
 * PHP warnings/notices are promoted to exceptions so bugs are never silently ignored.
 */
final class ErrorHandler
{
    /**
     * Statuses that pick an error page but are not standard HTTP codes.
     * Apache turns unknown codes into "500 Internal Server Error", so an
     * expired form (419, "page expired") is sent as 403 with the 419 page.
     */
    private const WIRE_STATUS = [419 => 403];

    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if ((error_reporting() & $severity) === 0) {
            return false; // silenced with @
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
            exit(1);
        }

        self::render($e)->send();
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        Logger::error('Fatal error: ' . $error['message'], ['file' => $error['file'], 'line' => $error['line']]);

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            Response::html(self::fallbackPage(500), 500)->send();
        }
    }

    public static function render(Throwable $e): Response
    {
        if ($e instanceof HttpException) {
            $status = $e->status;
            $headers = $e->headers;
        } else {
            $status = 500;
            $headers = [];
            Logger::error($e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'url'       => $_SERVER['REQUEST_URI'] ?? null,
                'trace'     => $e->getTraceAsString(),
            ]);

            if (Config::isDevelopment()) {
                return Response::html(self::debugPage($e), 500);
            }
        }

        try {
            $body = View::render('errors/error', ['status' => $status, 'title' => Labels::httpError($status)[0]], 'site');
        } catch (Throwable $renderError) {
            // e.g. the database is down and the layout could not load the school name
            Logger::error('Error page could not be rendered: ' . $renderError->getMessage());
            $body = self::fallbackPage($status);
        }

        return Response::html($body, self::WIRE_STATUS[$status] ?? $status)->withHeaders($headers);
    }

    /** Minimal page that depends on nothing (no DB, no views). */
    private static function fallbackPage(int $status): string
    {
        $title = $status === 404 ? 'Faqja nuk u gjet' : 'Diçka shkoi keq';

        return '<!doctype html><html lang="sq"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $title . '</title></head><body>'
            . '<h1>' . $title . '</h1>'
            . '<p>Ju lutemi provoni përsëri më vonë.</p>'
            . '</body></html>';
    }

    /** Development only: full details of an unexpected exception. */
    private static function debugPage(Throwable $e): string
    {
        $nonce = SecurityHeaders::nonce();
        $esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html><html lang="sq"><head><meta charset="utf-8"><title>Gabim — ' . $esc(get_class($e)) . '</title>'
            . '<style nonce="' . $nonce . '">'
            . 'body{margin:0;font:15px/1.6 system-ui,sans-serif;background:#F8F7F3;color:#0D2530}'
            . 'main{max-width:1100px;margin:0 auto;padding:48px 24px}'
            . 'small{letter-spacing:.14em;text-transform:uppercase;font-weight:700;color:#B3261E}'
            . 'h1{font:500 28px/1.3 Georgia,serif;margin:8px 0 16px}'
            . 'code,pre{font:13px/1.6 Consolas,monospace}'
            . 'pre{background:#fff;border:1px solid #E2DFD7;padding:16px;overflow:auto;white-space:pre-wrap}'
            . '</style></head><body><main>'
            . '<small>Gabim në zhvillim · ' . $esc(get_class($e)) . '</small>'
            . '<h1>' . $esc($e->getMessage()) . '</h1>'
            . '<p><code>' . $esc($e->getFile()) . ':' . $e->getLine() . '</code></p>'
            . '<pre>' . $esc($e->getTraceAsString()) . '</pre>'
            . '</main></body></html>';
    }
}
