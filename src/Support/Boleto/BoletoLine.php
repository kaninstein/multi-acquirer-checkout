<?php

namespace Kaninstein\MultiAcquirerCheckout\Support\Boleto;

final class BoletoLine
{
    /**
     * Convert boleto "linha digitável" (47 digits) to barcode digits (44 digits).
     *
     * Accepts:
     * - 44 digits: returns as-is (already barcode digits)
     * - 47 digits: converts to 44 digits
     *
     * Returns null when input is not supported.
     */
    public static function toBarcodeDigits(string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 44) {
            return $digits;
        }

        if (strlen($digits) !== 47) {
            return null;
        }

        // Layout (1-based positions):
        // 1-3 bank, 4 currency, 5-9 free field (part 1), 10 dv1,
        // 11-20 free field (part 2), 21 dv2,
        // 22-31 free field (part 3), 32 dv3,
        // 33 general dv (barcode), 34-37 due date factor, 38-47 value.
        //
        // Barcode:
        // 1-3 bank, 4 currency, 5 general dv, 6-9 due factor, 10-19 value, 20-44 free field (25 digits).
        $bank = substr($digits, 0, 3);
        $currency = substr($digits, 3, 1);
        $generalDv = substr($digits, 32, 1);
        $dueFactor = substr($digits, 33, 4);
        $value = substr($digits, 37, 10);

        $freeField = substr($digits, 4, 5) // pos 5-9
            .substr($digits, 10, 10) // pos 11-20
            .substr($digits, 21, 10); // pos 22-31

        return $bank.$currency.$generalDv.$dueFactor.$value.$freeField;
    }
}

