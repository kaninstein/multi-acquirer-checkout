<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Webhooks\Events;

final readonly class GatewayWebhookReceived
{
    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public string $gatewayName,
        public string $gatewayTransactionId,
        public string $status,
        public string $eventType,
        public array $payload,
    ) {}
}

