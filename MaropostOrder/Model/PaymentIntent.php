<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\Model;

use Sekulich\MaropostOrder\Api\PaymentIntentInterface;

/**
 * Class PaymentIntent
 */
class PaymentIntent extends \Magento\Framework\Model\AbstractModel implements PaymentIntentInterface
{
    /**
     * Get client secret
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return (string) $this->getData('client_secret');
    }

    /**
     * Set client secret
     *
     * @param string $secret
     *
     * @return PaymentIntentInterface
     */
    public function setClientSecret(string $secret): PaymentIntentInterface
    {
        $this->setData('client_secret', $secret);
        return $this;
    }

    /**
     * Set order ID
     *
     * @param string $incrementId
     *
     * @return PaymentIntentInterface
     */
    public function setOrderId(string $incrementId): PaymentIntentInterface
    {
        $this->setData('orderID', $incrementId);

        return $this;
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return (string) $this->getData('orderID');
    }

    /**
     * Get amount paid
     *
     * @return string
     */
    public function getAmountPaid(): string
    {
        return (string) $this->getData('amount_paid');
    }

    /**
     * Get payment type
     *
     * @return string
     */
    public function getPaymentType(): string
    {
        return (string) $this->getData('payment_type');
    }

    /**
     * Set payment type
     *
     * @param string $paymentType
     *
     * @return PaymentIntentInterface
     */
    public function setPaymentType(string $paymentType): PaymentIntentInterface
    {
        $this->setData('payment_type', $paymentType);

        return $this;
    }

    /**
     * Set amount paid
     *
     * @param string $amount
     *
     * @return PaymentIntentInterface
     */
    public function setAmountPaid(string $amount): PaymentIntentInterface
    {
        $this->setData('amount_paid', $amount);

        return $this;
    }
}
