<?php

namespace Kaninstein\MultiAcquirerCheckout\Tests\Unit;

use Kaninstein\MultiAcquirerCheckout\Support\Boleto\BoletoLine;
use PHPUnit\Framework\TestCase;

final class BoletoLineTest extends TestCase
{
    public function testReturnsNullForInvalidInput(): void
    {
        self::assertNull(BoletoLine::toBarcodeDigits(''));
        self::assertNull(BoletoLine::toBarcodeDigits('abc'));
        self::assertNull(BoletoLine::toBarcodeDigits('123'));
        self::assertNull(BoletoLine::toBarcodeDigits(str_repeat('1', 46)));
        self::assertNull(BoletoLine::toBarcodeDigits(str_repeat('1', 48)));
    }

    public function testReturnsSameWhenAlreadyBarcodeDigits(): void
    {
        $digits = str_repeat('1', 44);
        self::assertSame($digits, BoletoLine::toBarcodeDigits($digits));
    }

    public function testConvertsLinhaDigitavelToBarcodeDigits(): void
    {
        // Artificial 47-digit line with stable segments.
        // bank=001, currency=9,
        // free1=12345 dv1=6,
        // free2=1234567890 dv2=1,
        // free3=0987654321 dv3=2,
        // generalDv=3,
        // dueFactor=1234,
        // value=0000014700
        $linha = '0019'
            .'12345'.'6'
            .'1234567890'.'1'
            .'0987654321'.'2'
            .'3'
            .'1234'
            .'0000014700';

        self::assertSame(47, strlen($linha));

        $expectedBarcode = '0019'
            .'3'
            .'1234'
            .'0000014700'
            .'12345'
            .'1234567890'
            .'0987654321';

        self::assertSame(44, strlen($expectedBarcode));
        self::assertSame($expectedBarcode, BoletoLine::toBarcodeDigits($linha));
    }
}

