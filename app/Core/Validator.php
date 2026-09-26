<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Collects form errors (one message per field, the first that applies).
 *
 *   $v = new Validator($request->all());
 *   $v->required('email', 'Shkruani email-in.')
 *     ->email('email', 'Shkruani një adresë të vlefshme.');
 *   if ($v->fails()) { … $v->errors() … }
 *
 * Messages are always passed in, so every form speaks natural Albanian.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    /** Trimmed string value ('' when missing or not a string). */
    public function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** Untrimmed value — for passwords, where spaces count. */
    public function raw(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? $value : '';
    }

    public function required(string $field, string $message): self
    {
        return $this->rule($field, $this->value($field) !== '', $message);
    }

    public function email(string $field, string $message): self
    {
        $value = $this->value($field);

        return $this->rule($field, $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false, $message);
    }

    public function maxLength(string $field, int $max, string $message): self
    {
        return $this->rule($field, mb_strlen($this->value($field)) <= $max, $message);
    }

    /** Any condition: $v->rule('phone', preg_match(...) === 1, 'Numri nuk është i vlefshëm.') */
    public function rule(string $field, bool $valid, string $message): self
    {
        if (!$valid && !isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    /** Merge errors produced elsewhere (e.g. PasswordPolicy). */
    public function addErrors(array $errors): self
    {
        foreach ($errors as $field => $message) {
            $this->rule((string) $field, false, (string) $message);
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
