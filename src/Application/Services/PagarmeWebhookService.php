<?php

namespace Kaninstein\MultiAcquirerCheckout\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Kaninstein\MultiAcquirerCheckout\Domain\Webhooks\Events\GatewayWebhookReceived;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts\PaymentRepositoryInterface;

final readonly class PagarmeWebhookService
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private Dispatcher $events,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string}
     */
    public function handle(array $payload): array
    {
        $eventType = is_string($payload['type'] ?? null) ? (string) $payload['type'] : null;
        /** @var array<string,mixed> $data */
        $data = is_array($payload['data'] ?? null) ? (array) $payload['data'] : $payload;

        if (! $eventType) {
            return ['status' => 'ignored'];
        }

        $gatewayTransactionId = $this->extractGatewayTransactionId($eventType, $data);
        if ($gatewayTransactionId === null) {
            return ['status' => 'ignored'];
        }

        $status = $this->normalizeStatusForEvent($eventType);
        if ($status !== null) {
            $this->events->dispatch(new GatewayWebhookReceived(
                gatewayName: 'pagarme',
                gatewayTransactionId: $gatewayTransactionId,
                status: $status,
                eventType: $eventType,
                payload: $payload,
            ));
        }

        $payment = $this->payments->findByGatewayTransactionId($gatewayTransactionId);
        if ($payment === null) {
            return ['status' => 'ignored'];
        }

        if ($payment->status->isFinal()) {
            return ['status' => 'ignored'];
        }

        $webhookMeta = [
            'last_event' => $eventType,
            'received_at' => now()->toISOString(),
        ];

        $payment->metadata = [
            ...$payment->metadata,
            'webhook' => $webhookMeta,
        ];

        if ($eventType === 'charge.paid' || $eventType === 'charge.payment_succeeded' || $eventType === 'order.paid') {
            $payment->markPaid();
        } elseif ($eventType === 'charge.pending' || $eventType === 'charge.waiting_payment' || $eventType === 'order.pending') {
            // Keep pending; only record metadata.
        } elseif ($eventType === 'charge.failed' || $eventType === 'charge.payment_failed') {
            $payment->fail($this->extractFailureReason($data) ?? 'Payment failed');
        } elseif ($eventType === 'charge.refunded') {
            $payment->refund();
        } elseif ($eventType === 'charge.canceled' || $eventType === 'order.canceled') {
            $payment->cancel();
        } else {
            return ['status' => 'ignored'];
        }

        $this->payments->save($payment);

        if (config('multi-acquirer.events.dispatch_domain_events', true)) {
            foreach ($payment->releaseEvents() as $event) {
                $this->events->dispatch($event);
            }
        }

        return ['status' => 'success'];
    }

    /**
     * @param array<string,mixed> $data
     */
    private function extractFailureReason(array $data): ?string
    {
        $last = is_array($data['last_transaction'] ?? null) ? (array) $data['last_transaction'] : null;
        $gatewayResponse = $last && is_array($last['gateway_response'] ?? null) ? (array) $last['gateway_response'] : null;
        $errors = $gatewayResponse && is_array($gatewayResponse['errors'] ?? null) ? (array) $gatewayResponse['errors'] : [];
        $first = is_array($errors[0] ?? null) ? (array) $errors[0] : null;

        $message = $first['message'] ?? null;

        return is_string($message) && $message !== '' ? $message : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function extractGatewayTransactionId(string $eventType, array $data): ?string
    {
        if (str_starts_with($eventType, 'charge.')) {
            $id = $data['id'] ?? null;
            return is_string($id) && $id !== '' ? $id : null;
        }

        if (str_starts_with($eventType, 'order.')) {
            $charges = is_array($data['charges'] ?? null) ? (array) $data['charges'] : [];
            $firstCharge = is_array($charges[0] ?? null) ? (array) $charges[0] : [];
            $id = $firstCharge['id'] ?? null;

            return is_string($id) && $id !== '' ? $id : null;
        }

        return null;
    }

    private function normalizeStatusForEvent(string $eventType): ?string
    {
        return match ($eventType) {
            'charge.paid', 'charge.payment_succeeded', 'order.paid' => 'paid',
            'charge.pending', 'charge.waiting_payment', 'order.pending' => 'pending',
            'charge.failed', 'charge.payment_failed' => 'failed',
            'charge.refunded' => 'refunded',
            'charge.canceled', 'order.canceled' => 'canceled',
            default => null,
        };
    }
}
