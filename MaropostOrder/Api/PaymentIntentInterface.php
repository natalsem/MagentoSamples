<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\Api;

/**
 * Interface PaymentIntentInterface
 */
interface PaymentIntentInterface
{
    /**
     * Get client secret
     *
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * Set client secret
     *
     * @param string $secret
     *
     * @return PaymentIntentInterface
     */
    public function setClientSecret(string $secret): PaymentIntentInterface;

    /**
     * Set amount paid
     *
     * @param string $amount
     *
     * @return PaymentIntentInterface
     */
    public function setAmountPaid(string $amount): PaymentIntentInterface;

    /**
     * Get payment type
     *
     * @return string
     */
    public function getPaymentType(): string;

    /**
     * Set payment type
     *
     * @param string $paymentType
     *
     * @return PaymentIntentInterface
     */
    public function setPaymentType(string $paymentType): PaymentIntentInterface;

    /**
     * Get amount paid
     *
     * @return string
     */
    public function getAmountPaid(): string;

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId(): string;

    /**
     * Set order ID
     *
     * @param string $incrementId
     *
     * @return PaymentIntentInterface
     */
    public function setOrderId(string $incrementId): PaymentIntentInterface;
}
