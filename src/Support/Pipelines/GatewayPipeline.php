<?php

namespace Kaninstein\MultiAquirerCheckout\Support\Pipelines;

use Illuminate\Support\Facades\Log;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentResponse;
use Kaninstein\MultiAquirerCheckout\Domain\Gateway\Contracts\GatewayInterface;
use Kaninstein\MultiAquirerCheckout\Support\Exceptions\AllGatewaysFailedException;
use Kaninstein\MultiAquirerCheckout\Support\Exceptions\CardRejectionException;

class GatewayPipeline
{
    /** @var array<int, GatewayInterface> */
    protected array $gateways = [];

    /** @var array<string, string> */
    protected array $errors = [];

    /**
     * @param  array<int, GatewayInterface>  $gateways
     */
    public function __construct(array $gateways = [])
    {
        $this->gateways = $gateways;
    }

    public function addGateway(GatewayInterface $gateway): self
    {
        $this->gateways[] = $gateway;

        return $this;
    }

    /**
     * @return array<int, GatewayInterface>
     */
    public function getGateways(): array
    {
        return $this->gateways;
    }

    public function sortByPriority(): self
    {
        usort($this->gateways, fn (GatewayInterface $a, GatewayInterface $b) => $a->getPriority() <=> $b->getPriority());

        return $this;
    }

    public function filterByPaymentMethod(string $paymentMethod): self
    {
        $this->gateways = array_values(array_filter(
            $this->gateways,
            fn (GatewayInterface $gateway) => $gateway->supports($paymentMethod) && $gateway->isEnabled()
        ));

        return $this;
    }

    public function process(PaymentRequest $request): PaymentResponse
    {
        $this->errors = [];

        $this->filterByPaymentMethod($request->paymentMethod->value)->sortByPriority();

        if (empty($this->gateways)) {
            throw new AllGatewaysFailedException("No gateways available for payment method: {$request->paymentMethod->value}");
        }

        if ($request->preferredGateway !== '') {
            $this->gateways = $this->prioritizeGateway($request->preferredGateway, $this->gateways);
        }

        $channel = (string) config('multi-acquirer.logging.channel', 'stack');

        Log::channel($channel)->info('Starting payment pipeline', [
            'payment_method' => $request->paymentMethod->value,
            'amount_cents' => $request->amount->amountInCents,
            'currency' => $request->amount->currency,
            'gateways_count' => count($this->gateways),
            'gateways' => array_map(fn (GatewayInterface $g) => $g->getName(), $this->gateways),
        ]);

        foreach ($this->gateways as $gateway) {
            try {
                Log::channel($channel)->info("Attempting payment with gateway: {$gateway->getName()}", [
                    'priority' => $gateway->getPriority(),
                ]);

                $response = $gateway->process($request);

                if ($response->isSuccessful()) {
                    Log::channel($channel)->info("Payment successful with gateway: {$gateway->getName()}", [
                        'transaction_id' => $response->id,
                        'status' => $response->status,
                    ]);

                    return $response;
                }

                $errorMessage = $response->errorMessage ?? 'Payment failed without specific error';
                $this->errors[$gateway->getName()] = $errorMessage;

                Log::channel($channel)->warning("Payment failed with gateway: {$gateway->getName()}", [
                    'error' => $errorMessage,
                    'error_code' => $response->errorCode,
                ]);
            } catch (CardRejectionException $e) {
                Log::channel($channel)->warning("Card rejected by issuer on gateway: {$gateway->getName()}", [
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            } catch (\Throwable $e) {
                $this->errors[$gateway->getName()] = $e->getMessage();

                Log::channel($channel)->error("Exception in gateway: {$gateway->getName()}", [
                    'error' => $e->getMessage(),
                ]);

                if ($this->isCardRejectionError($e->getMessage())) {
                    throw new CardRejectionException($e->getMessage(), previous: $e);
                }

                continue;
            }
        }

        throw new AllGatewaysFailedException('All payment gateways failed', $this->errors);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<int, GatewayInterface>  $gateways
     * @return array<int, GatewayInterface>
     */
    private function prioritizeGateway(string $preferred, array $gateways): array
    {
        usort($gateways, function (GatewayInterface $a, GatewayInterface $b) use ($preferred) {
            if ($a->getName() === $preferred) {
                return -1;
            }
            if ($b->getName() === $preferred) {
                return 1;
            }

            return $a->getPriority() <=> $b->getPriority();
        });

        return $gateways;
    }

    private function isCardRejectionError(string $errorMessage): bool
    {
        $patterns = [
            'card declined',
            'insufficient funds',
            'transaction not authorized',
            'issuer declined',
            'rejected by issuer',
            'cartão rejeitado',
            'cartão bloqueado',
            'limit exceeded',
            'invalid card',
            'expired card',
            'transação recusada',
            'não autorizada',
        ];

        $errorLower = strtolower($errorMessage);

        foreach ($patterns as $pattern) {
            if (str_contains($errorLower, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }
}

