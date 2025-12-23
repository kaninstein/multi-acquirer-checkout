<?php

namespace Kaninstein\MultiAcquirerCheckout\Support\Contracts;

use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentResponse;

interface HookInterface
{
    /**
     * Execute the hook
     *
     * @param  mixed  $context
     * @return mixed Result that can be used to modify behavior
     */
    public function execute(mixed $context): mixed;

    /**
     * Get the hook priority (lower = earlier execution)
     */
    public function priority(): int;

    /**
     * Whether this hook should halt execution on failure
     */
    public function haltOnFailure(): bool;
}
