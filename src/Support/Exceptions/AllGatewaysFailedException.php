<?php

namespace Kaninstein\MultiAquirerCheckout\Support\Exceptions;

use RuntimeException;

class AllGatewaysFailedException extends RuntimeException
{
    /**
     * @param  array<string, string>  $errors
     */
    public function __construct(
        string $message = 'All payment gateways failed.',
        public readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}

