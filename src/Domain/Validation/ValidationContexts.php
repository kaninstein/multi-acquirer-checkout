<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Validation;

/**
 * Available validation contexts
 */
final class ValidationContexts
{
    /** Validate payment request before processing */
    public const PAYMENT_REQUEST = 'payment.request';

    /** Validate card data */
    public const CARD_DATA = 'card.data';

    /** Validate customer data */
    public const CUSTOMER_DATA = 'customer.data';

    /** Validate PIX payment data */
    public const PIX_DATA = 'pix.data';

    /** Validate boleto payment data */
    public const BOLETO_DATA = 'boleto.data';

    /** Validate webhook payload */
    public const WEBHOOK_PAYLOAD = 'webhook.payload';

    /** Validate refund request */
    public const REFUND_REQUEST = 'refund.request';

    /** Validate amount/monetary values */
    public const AMOUNT_VALIDATION = 'amount.validation';

    /** Validate installments */
    public const INSTALLMENTS = 'installments';

    /** Custom validation context */
    public const CUSTOM = 'custom';
}
