<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects;

use InvalidArgumentException;

final readonly class Customer
{
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
     * @param  array{name:string,email:string,document?:string|null,phone?:string|null,address?:array}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            document: isset($data['document']) ? (string) $data['document'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            address: is_array($data['address'] ?? null) ? $data['address'] : [],
        );
    }

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

