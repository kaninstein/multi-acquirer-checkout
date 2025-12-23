<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Validation\Contracts;

use Kaninstein\MultiAcquirerCheckout\Domain\Validation\ValidationResult;

interface ValidatorInterface
{
    /**
     * Validate the given data
     *
     * @param  mixed  $data
     * @return ValidationResult
     */
    public function validate(mixed $data): ValidationResult;

    /**
     * Get validator name/identifier
     */
    public function getName(): string;

    /**
     * Whether this validator should stop validation chain on failure
     */
    public function stopOnFailure(): bool;
}
