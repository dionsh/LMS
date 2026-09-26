<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Browser security headers sent with every response.
 *
 * The Content-Security-Policy only allows scripts, styles, fonts and images
 * from our own origin, so injected markup cannot run. Inline <script>/<style>
 * are blocked unless they carry the per-request nonce — reserved for rare
 * exceptions such as the development error page. Don't use style="" attributes.
 */
final class SecurityHeaders
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= base64_encode(random_bytes(16));
    }

    public static function all(): array
    {
        $nonce = self::nonce();

        $headers = [
            'Content-Security-Policy' => implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "img-src 'self' data:",
                "font-src 'self'",
                "style-src 'self' 'nonce-{$nonce}'",
                "script-src 'self' 'nonce-{$nonce}'",
                "connect-src 'self'",
            ]),
            'X-Content-Type-Options'     => 'nosniff',
            'X-Frame-Options'            => 'DENY',
            'Referrer-Policy'            => 'strict-origin-when-cross-origin',
            'Permissions-Policy'         => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        if (is_https()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }
}
