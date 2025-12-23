<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Validation\Validators;

use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\CardData;
use Kaninstein\MultiAcquirerCheckout\Domain\Validation\AbstractValidator;
use Kaninstein\MultiAcquirerCheckout\Domain\Validation\ValidationResult;

class CardDataValidator extends AbstractValidator
{
    public function validate(mixed $data): ValidationResult
    {
        if (!$data instanceof CardData) {
            return $this->failure(['card' => 'Invalid card data type']);
        }

        $errors = [];

        // Validate card number (if not using token)
        if (!$data->token && !$this->isValidCardNumber($data->number)) {
            $errors['card.number'] = 'Invalid card number';
        }

        // Validate expiration
        if ($data->exp_month < 1 || $data->exp_month > 12) {
            $errors['card.exp_month'] = 'Invalid expiration month';
        }

        if ($data->exp_year < date('Y')) {
            $errors['card.exp_year'] = 'Card is expired';
        }

        // Validate CVV
        if (!$data->token && (!$data->cvv || strlen($data->cvv) < 3 || strlen($data->cvv) > 4)) {
            $errors['card.cvv'] = 'Invalid CVV';
        }

        if (!empty($errors)) {
            return $this->failure($errors);
        }

        return $this->success();
    }

    public function getName(): string
    {
        return 'card_data';
    }

    public function stopOnFailure(): bool
    {
        return true; // Stop if card data is invalid
    }

    private function isValidCardNumber(?string $number): bool
    {
        if (!$number) {
            return false;
        }

        // Remove spaces and dashes
        $number = preg_replace('/[\s\-]/', '', $number);

        // Must be digits only
        if (!preg_match('/^\d+$/', $number)) {
            return false;
        }

        // Luhn algorithm
        $sum = 0;
        $length = strlen($number);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
