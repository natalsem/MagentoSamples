<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface OrderPaymentProcessorInterface implements webApi model
 */
interface OrderPaymentProcessorInterface
{
    /**
     * Pay amount due
     *
     * @param string $incrementId
     * @param int $customerId
     * @param string $amount
     *
     * @throws LocalizedException
     * @return PaymentIntentInterface
     */
    public function payAmountDue(string $incrementId, int $customerId, string $amount): PaymentIntentInterface;

    /**
     * Send payment to Maropost
     *
     * @param string $paymentId
     * @param string $orderData
     * @param int $customerId
     *
     * @return array
     */
    public function addPaymentToMaropost(string $paymentId, string $orderData, int $customerId): array;
}

