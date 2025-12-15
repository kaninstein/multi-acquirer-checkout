<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects;

enum PaymentMethod: string
{
    case CARD = 'card';
    case PIX = 'pix';
    case BOLETO = 'boleto';
}

