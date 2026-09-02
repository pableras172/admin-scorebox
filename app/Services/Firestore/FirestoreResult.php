<?php

declare(strict_types=1);

namespace App\Services\Firestore;

final class FirestoreResult
{
    public function __construct(
        private readonly bool $success,
        private readonly mixed $data = null,
        private readonly ?string $error = null,
        private readonly ?string $message = null,
        private readonly array $metadata = [],
    ) {}

    public static function success(mixed $data = null, array $metadata = []): self
    {
        return new self(true, $data, null, null, $metadata);
    }

    public static function failure(string $error, ?string $message = null, array $metadata = []): self
    {
        return new self(false, null, $error, $message ?? $error, $metadata + ['error_code' => $error]);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function data(): mixed
    {
        return $this->data;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }
}
