<?php

namespace Kaninstein\MultiAcquirerCheckout\Tests\Unit\Infrastructure\Gateways;

use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\CardData;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Gateways\Pagarme\PagarmeGateway;
use Kaninstein\MultiAcquirerCheckout\Tests\TestCase;

final class PagarmeGatewayCardTokenTest extends TestCase
{
    public function test_builds_credit_card_payload_with_card_id_when_token_is_present(): void
    {
        $gateway = new PagarmeGateway([
            'enabled' => true,
            'sandbox' => true,
            'secret_key' => 'sk_test',
            'public_key' => 'pk_test',
        ]);

        $request = new PaymentRequest(
            amount: Money::fromCents(10000, 'BRL'),
            paymentMethod: PaymentMethod::CARD,
            installments: 3,
            customer: Customer::fromArray(['name' => 'John', 'email' => 'john@example.com']),
            cardData: CardData::fromArray(['token' => 'card_tok_123']),
        );

        $method = new \ReflectionMethod($gateway, 'buildCardPaymentData');
        $method->setAccessible(true);

        /** @var array<string, mixed> $payload */
        $payload = $method->invoke($gateway, $request);

        self::assertSame('credit_card', $payload['payment_method'] ?? null);
        self::assertSame('card_tok_123', $payload['credit_card']['card_id'] ?? null);
        self::assertArrayNotHasKey('card', (array) ($payload['credit_card'] ?? []));
    }
}

