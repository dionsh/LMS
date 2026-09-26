<?php

declare(strict_types=1);

namespace App\Core;

/**
 * The current HTTP request, with the path already relative to the app
 * (e.g. '/nxenesi/detyrat', never '/lms-system/nxenesi/detyrat').
 */
final class Request
{
    private static ?self $current = null;

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly bool $trailingSlash,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $server,
    ) {
    }

    /** The request being handled by this PHP process (also available to layouts via current_path()). */
    public static function capture(): self
    {
        return self::$current = self::create(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'),
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
        );
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    /** Also used by tests and CLI scripts to build a request by hand. */
    public static function create(
        string $method,
        string $uriPath,
        array $query = [],
        array $body = [],
        array $files = [],
        array $server = [],
    ): self {
        $path = rawurldecode($uriPath);
        $base = Config::basePath();

        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
        }

        $normalized = '/' . trim($path, '/');

        return new self(
            strtoupper($method),
            $normalized,
            $normalized !== '/' && str_ends_with($path, '/'),
            $query,
            $body,
            $files,
            $server,
        );
    }

    public function isGet(): bool
    {
        return $this->method === 'GET' || $this->method === 'HEAD';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /** '/lajme/' was requested instead of '/lajme'. */
    public function hasTrailingSlash(): bool
    {
        return $this->trailingSlash;
    }

    /** A value from the query string (?key=value). */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function queryString(): string
    {
        return $this->query === [] ? '' : http_build_query($this->query);
    }

    /** A value from the POST body. */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /** A POST field as a trimmed string ('' when missing or not a string). */
    public function string(string $key): string
    {
        $value = $this->body[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    public function all(): array
    {
        return $this->body;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    /**
     * True when a POST body was larger than post_max_size — PHP then silently
     * drops $_POST and $_FILES, which would otherwise look like a missing CSRF token.
     */
    public function exceedsPostMaxSize(): bool
    {
        $length = (int) ($this->server['CONTENT_LENGTH'] ?? 0);
        $limit = self::iniBytes((string) ini_get('post_max_size'));

        return $this->isPost() && $limit > 0 && $length > $limit;
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g'     => $number * 1024 ** 3,
            'm'     => $number * 1024 ** 2,
            'k'     => $number * 1024,
            default => $number,
        };
    }
}
