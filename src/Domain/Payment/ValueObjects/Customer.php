<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects;

use InvalidArgumentException;

final readonly class Customer
{
    /**
     * @param array<string, mixed> $address
     */
    private function __construct(
        public string $name,
        public string $email,
        public ?string $document = null,
        public ?string $phone = null,
        public array $address = [],
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Customer name is required.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Customer email is invalid.');
        }
    }

    /**
     * @param array{name: string, email: string, document?: string|null, phone?: string|null, address?: array<string, mixed>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            document: $data['document'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'document' => $this->document,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }
}

