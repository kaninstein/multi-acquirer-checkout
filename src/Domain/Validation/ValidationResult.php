<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Validation;

final readonly class ValidationResult
{
    /**
     * @param  bool  $isValid
     * @param  array<string, string|array<string>>  $errors
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $metadata = [],
    ) {}

    public static function success(array $metadata = []): self
    {
        return new self(true, [], $metadata);
    }

    public static function failure(array $errors, array $metadata = []): self
    {
        return new self(false, $errors, $metadata);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstError = reset($this->errors);

        if (is_array($firstError)) {
            return reset($firstError) ?: null;
        }

        return $firstError;
    }

    /**
     * @return array<string>
     */
    public function getAllErrors(): array
    {
        $allErrors = [];

        foreach ($this->errors as $field => $error) {
            if (is_array($error)) {
                $allErrors = array_merge($allErrors, $error);
            } else {
                $allErrors[] = $error;
            }
        }

        return $allErrors;
    }
}
