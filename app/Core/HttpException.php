<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Throw to stop the request with an HTTP error page:
 *   throw new HttpException(404);   // not found (also used for "not yours")
 *   throw new HttpException(403);   // wrong role
 */
final class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly array $headers = [],
    ) {
        parent::__construct($message !== '' ? $message : 'HTTP ' . $status, $status);
    }
}
