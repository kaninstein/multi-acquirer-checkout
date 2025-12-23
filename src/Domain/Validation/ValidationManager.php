<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Validation;

use Illuminate\Support\Facades\Log;
use Kaninstein\MultiAcquirerCheckout\Domain\Validation\Contracts\ValidatorInterface;

class ValidationManager
{
    /** @var array<string, array<int, ValidatorInterface>> */
    protected array $validators = [];

    /**
     * Register a validator for a specific context
     */
    public function register(string $context, ValidatorInterface $validator): void
    {
        if (!isset($this->validators[$context])) {
            $this->validators[$context] = [];
        }

        $this->validators[$context][] = $validator;
    }

    /**
     * Validate data for a given context
     *
     * @param  string  $context
     * @param  mixed  $data
     * @return ValidationResult
     */
    public function validate(string $context, mixed $data): ValidationResult
    {
        if (!isset($this->validators[$context]) || empty($this->validators[$context])) {
            return ValidationResult::success();
        }

        $channel = (string) config('multi-acquirer.logging.channel', 'stack');
        $allErrors = [];
        $allMetadata = [];

        Log::channel($channel)->debug("Running validators for context: {$context}", [
            'validators_count' => count($this->validators[$context]),
        ]);

        foreach ($this->validators[$context] as $validator) {
            try {
                $result = $validator->validate($data);

                if (!$result->isValid) {
                    $allErrors = array_merge($allErrors, $result->errors);

                    Log::channel($channel)->warning("Validation failed: {$validator->getName()}", [
                        'context' => $context,
                        'errors' => $result->errors,
                    ]);

                    if ($validator->stopOnFailure()) {
                        return ValidationResult::failure($allErrors, $allMetadata);
                    }
                }

                $allMetadata = array_merge($allMetadata, $result->metadata);
            } catch (\Throwable $e) {
                Log::channel($channel)->error("Validator exception: {$validator->getName()}", [
                    'context' => $context,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($validator->stopOnFailure()) {
                    throw $e;
                }
            }
        }

        if (!empty($allErrors)) {
            return ValidationResult::failure($allErrors, $allMetadata);
        }

        return ValidationResult::success($allMetadata);
    }

    /**
     * Check if any validators are registered for a context
     */
    public function hasValidators(string $context): bool
    {
        return isset($this->validators[$context]) && !empty($this->validators[$context]);
    }

    /**
     * Get all validators for a context
     *
     * @return array<int, ValidatorInterface>
     */
    public function getValidators(string $context): array
    {
        return $this->validators[$context] ?? [];
    }

    /**
     * Clear validators for a context
     */
    public function clear(string $context): void
    {
        unset($this->validators[$context]);
    }

    /**
     * Clear all validators
     */
    public function clearAll(): void
    {
        $this->validators = [];
    }
}
