<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Validation;

use Kaninstein\MultiAcquirerCheckout\Domain\Validation\Contracts\ValidatorInterface;

abstract class AbstractValidator implements ValidatorInterface
{
    public function stopOnFailure(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return static::class;
    }

    /**
     * Helper to create success result
     */
    protected function success(array $metadata = []): ValidationResult
    {
        return ValidationResult::success($metadata);
    }

    /**
     * Helper to create failure result
     */
    protected function failure(array $errors, array $metadata = []): ValidationResult
    {
        return ValidationResult::failure($errors, $metadata);
    }
}
