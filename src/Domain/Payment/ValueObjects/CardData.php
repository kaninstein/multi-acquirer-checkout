<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects;

final readonly class CardData
{
    private function __construct(
        public string $number,
        public string $holderName,
        public int $expMonth,
        public int $expYear,
        public string $cvv,
        public ?string $token = null,
    ) {}

    /**
     * @param  array{number?:string,holder_name?:string,exp_month?:int,exp_year?:int,cvv?:string,token?:string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            number: (string) ($data['number'] ?? ''),
            holderName: (string) ($data['holder_name'] ?? ''),
            expMonth: (int) ($data['exp_month'] ?? 0),
            expYear: (int) ($data['exp_year'] ?? 0),
            cvv: (string) ($data['cvv'] ?? ''),
            token: isset($data['token']) ? (string) $data['token'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'holder_name' => $this->holderName,
            'exp_month' => $this->expMonth,
            'exp_year' => $this->expYear,
            'cvv' => $this->cvv,
            'token' => $this->token,
        ];
    }
}
