<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

namespace Sekulich\MaropostOrder\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sekulich\MaropostSync\Model\Collection\MaropostOrder as MaropostOrderCollection;
use Sekulich\MaropostSync\Exception\IncorrectRequestException;
use Sekulich\MaropostSync\Exception\ConfigurationErrorException;

/**
 * Interface OrderManagementInterface implements webApi model
 */
interface OrderManagementInterface
{
    /**
     * Get Maropost orders for customer
     *
     * @param int $customerId
     *
     * @return array
     * @throws ConfigurationErrorException
     * @throws IncorrectRequestException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getMaropostOrders(int $customerId): array;

    /**
     * Get Maropost order
     *
     * @param int $customerId
     * @param string $incrementId
     * @param bool $getArray
     *
     * @throws ConfigurationErrorException
     * @throws IncorrectRequestException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @return MaropostOrderCollection|array
     */
    public function getMaropostOrder(int $customerId, string $incrementId, bool $getArray = true): MaropostOrderCollection|array;

}
