<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Validation\Validators;

use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Domain\Validation\AbstractValidator;
use Kaninstein\MultiAcquirerCheckout\Domain\Validation\ValidationResult;

class PaymentAmountValidator extends AbstractValidator
{
    public function validate(mixed $data): ValidationResult
    {
        if (!$data instanceof PaymentRequest) {
            return $this->failure(['payment' => 'Invalid payment request type']);
        }

        $errors = [];

        // Minimum amount validation (e.g., R$ 1.00 = 100 cents)
        $minAmountCents = (int) config('multi-acquirer.validation.min_amount_cents', 100);
        if ($data->amount->amountInCents < $minAmountCents) {
            $errors['amount'] = "Amount must be at least " . ($minAmountCents / 100);
        }

        // Maximum amount validation (e.g., R$ 100,000.00 = 10,000,000 cents)
        $maxAmountCents = (int) config('multi-acquirer.validation.max_amount_cents', 10000000);
        if ($data->amount->amountInCents > $maxAmountCents) {
            $errors['amount'] = "Amount cannot exceed " . ($maxAmountCents / 100);
        }

        // Installment validation
        if ($data->paymentMethod->value === 'card') {
            $minInstallmentAmount = (int) config('multi-acquirer.payment_methods.credit_card.min_installment_amount', 500);
            $installmentValue = $data->amount->amountInCents / $data->installments;

            if ($installmentValue < $minInstallmentAmount) {
                $errors['installments'] = "Installment value must be at least " . ($minInstallmentAmount / 100);
            }
        }

        if (!empty($errors)) {
            return $this->failure($errors);
        }

        return $this->success();
    }

    public function getName(): string
    {
        return 'payment_amount';
    }

    public function stopOnFailure(): bool
    {
        return true;
    }
}
