<?php

declare(strict_types=1);

namespace App\Core;

/**
 * An HTTP response. Controllers return one; index.php sends it.
 * Security headers are added to every response in send().
 */
class Response
{
    public function __construct(
        protected string $body = '',
        protected int $status = 200,
        protected array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): static
    {
        return new static($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** $url must already be a full internal URL — use the redirect() helper for app paths. */
    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = (string) $value;
        }

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            http_response_code($this->status);

            $headers = array_merge(
                SecurityHeaders::all(),
                ['Cache-Control' => 'private, no-cache'],
                $this->headers,
            );

            foreach ($headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        echo $this->body;
    }
}
