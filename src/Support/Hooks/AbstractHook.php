<?php

namespace Kaninstein\MultiAcquirerCheckout\Support\Hooks;

use Kaninstein\MultiAcquirerCheckout\Support\Contracts\HookInterface;

abstract class AbstractHook implements HookInterface
{
    public function priority(): int
    {
        return 100;
    }

    public function haltOnFailure(): bool
    {
        return false;
    }
}
