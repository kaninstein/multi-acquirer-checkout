<?php

namespace Kaninstein\MultiAcquirerCheckout\Support\Hooks;

/**
 * Available hook points in the checkout flow
 */
final class HookPoints
{
    /** Before payment processing starts */
    public const BEFORE_PAYMENT = 'payment.before';

    /** After payment processing completes (success or failure) */
    public const AFTER_PAYMENT = 'payment.after';

    /** Before gateway processing */
    public const BEFORE_GATEWAY = 'gateway.before';

    /** After gateway processing */
    public const AFTER_GATEWAY = 'gateway.after';

    /** When gateway switches during fallback */
    public const ON_GATEWAY_SWITCH = 'gateway.switch';

    /** Before fee calculation */
    public const BEFORE_FEE_CALCULATION = 'fee.before';

    /** After fee calculation */
    public const AFTER_FEE_CALCULATION = 'fee.after';

    /** Before payment validation */
    public const BEFORE_VALIDATION = 'validation.before';

    /** After payment validation */
    public const AFTER_VALIDATION = 'validation.after';

    /** When payment is successful */
    public const ON_PAYMENT_SUCCESS = 'payment.success';

    /** When payment fails */
    public const ON_PAYMENT_FAILURE = 'payment.failure';

    /** Before webhook processing */
    public const BEFORE_WEBHOOK = 'webhook.before';

    /** After webhook processing */
    public const AFTER_WEBHOOK = 'webhook.after';
}
