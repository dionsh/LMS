<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Renders PHP templates from app/Views.
 *
 *   View::render('site/home', ['title' => 'Ballina'], 'site');
 *
 * The view is rendered first; its HTML is then passed to the layout as
 * $content. Inside templates every value MUST be printed with e() —
 * `<?= $content ?>` in layouts is the only unescaped echo.
 */
final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'site'): string
    {
        $content = self::renderFile(self::resolve($view), $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderFile(self::resolve('layouts/' . $layout), ['content' => $content] + $data);
    }

    /** Render a partial (no layout), e.g. View::partial('partials/flash'). */
    public static function partial(string $view, array $data = []): string
    {
        return self::renderFile(self::resolve($view), $data);
    }

    private static function resolve(string $view): string
    {
        if (preg_match('#^[a-z0-9_\-]+(/[a-z0-9_\-]+)*$#', $view) !== 1) {
            throw new InvalidArgumentException("Invalid view name [{$view}].");
        }

        $file = ROOT_PATH . '/app/Views/' . $view . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View [{$view}] does not exist.");
        }

        return $file;
    }

    private static function renderFile(string $__file, array $__data): string
    {
        extract($__data, EXTR_SKIP);
        ob_start();

        try {
            require $__file;
            return (string) ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
