<?php

namespace Kaninstein\MultiAquirerCheckout\Application\DTOs;

final readonly class PaymentResponse
{
    public function __construct(
        public string $gatewayName,
        public string $status,
        public ?string $id = null,
        public ?string $originalStatus = null,
        public int $amountCents = 0,
        public string $currency = 'BRL',
        public string $paymentMethod = '',
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public ?array $pixData = null,
        public ?array $boletoData = null,
        public ?array $cardData = null,
        public array $metadata = [],
    ) {}

    public static function success(string $gatewayName, array $data = []): self
    {
        return new self(
            gatewayName: $gatewayName,
            status: 'paid',
            id: $data['id'] ?? null,
            originalStatus: $data['original_status'] ?? null,
            amountCents: (int) ($data['amount_cents'] ?? 0),
            currency: (string) ($data['currency'] ?? 'BRL'),
            paymentMethod: (string) ($data['payment_method'] ?? ''),
            pixData: $data['pix'] ?? null,
            boletoData: $data['boleto'] ?? null,
            cardData: $data['card'] ?? null,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public static function pending(string $gatewayName, array $data = []): self
    {
        return new self(
            gatewayName: $gatewayName,
            status: 'pending',
            id: $data['id'] ?? null,
            originalStatus: $data['original_status'] ?? null,
            amountCents: (int) ($data['amount_cents'] ?? 0),
            currency: (string) ($data['currency'] ?? 'BRL'),
            paymentMethod: (string) ($data['payment_method'] ?? ''),
            pixData: $data['pix'] ?? null,
            boletoData: $data['boleto'] ?? null,
            cardData: $data['card'] ?? null,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public static function failed(string $gatewayName, string $errorMessage, ?string $errorCode = null, array $data = []): self
    {
        return new self(
            gatewayName: $gatewayName,
            status: 'failed',
            id: $data['id'] ?? null,
            originalStatus: $data['original_status'] ?? null,
            amountCents: (int) ($data['amount_cents'] ?? 0),
            currency: (string) ($data['currency'] ?? 'BRL'),
            paymentMethod: (string) ($data['payment_method'] ?? ''),
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, ['paid', 'pending'], true) && $this->errorMessage === null;
    }

    public function toArray(): array
    {
        return [
            'gateway_name' => $this->gatewayName,
            'status' => $this->status,
            'id' => $this->id,
            'original_status' => $this->originalStatus,
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
            'pix' => $this->pixData,
            'boleto' => $this->boletoData,
            'card' => $this->cardData,
            'metadata' => $this->metadata,
        ];
    }
}

