<?php

/**
 * Sekulich_MaropostOrder
 *
 * @copyright   Copyright (c) 2023 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\Model;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Sekulich\MaropostOrder\Api\OrderManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sekulich\MaropostSyncLog\Api\Data\SyncStatusInterface;
use Sekulich\MaropostSync\Api\Data\MaropostOrderInterface;
use Sekulich\MaropostSyncLog\Api\SyncStatusRepositoryInterface;
use Sekulich\MaropostSync\Exception\ConfigurationErrorException;
use Sekulich\MaropostSync\Exception\IncorrectRequestException;
use Sekulich\MaropostSync\Model\OrderSyncManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Sekulich\MaropostSync\Model\Collection\MaropostOrder as MaropostOrderCollection;

/**
 * Class OrderManagement
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class OrderManagement implements OrderManagementInterface
{
    /**#@+
     * Additional order fields
     */
    public const IS_CANCELLED = 'isCancelled';
    public const IS_SYNCED = 'isSynced';

    /** @var array */
    private array $orderIncrementIds = [];

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderSyncManagement $orderSyncManagement
     * @param LoggerInterface $logger
     * @param InvoiceEmailTrigger $emailTrigger
     * @param MessageManagerInterface $messageManager
     * @param SyncStatusRepositoryInterface $maropostSyncRepository
     * @param EavConfig $eavConfig
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilder
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly OrderSyncManagement $orderSyncManagement,
        private readonly LoggerInterface $logger,
        private readonly InvoiceEmailTrigger $emailTrigger,
        private readonly MessageManagerInterface $messageManager,
        private readonly SyncStatusRepositoryInterface $maropostSyncRepository,
        private readonly EavConfig $eavConfig,
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilder
    ) {
    }

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
    public function getMaropostOrders(int $customerId): array
    {
        $orderArray = [];
        $username = $this->getUsername($customerId);
        if ($username) {
            $orders = $this->orderSyncManagement->getOrdersByCustomerUsername($username)->toArray()['items'];
            usort($orders, function ($orderItem1, $orderItem2) {
                return strtotime($orderItem1['DatePlaced']) - strtotime($orderItem2['DatePlaced']);
            });
            $orders = array_reverse($orders);
            if ($this->collectIncrementIds($orders)) {
                $magentoOrders = $this->getMagentoOrders();
                $logRecords = $this->getSyncRecords();
                foreach ($orders as $order) {
                    $orderArray[] = $this->addOrderData(
                        maropostOrder: $order,
                        logRecords: $logRecords,
                        isMagentoOrder: isset($magentoOrders[$order['OrderID']])
                    );
                }
            }
        }

        return $orderArray;
    }

    /**
     * Collect all increment IDS
     *
     * @param array $orders
     *
     * @return array
     */
    private function collectIncrementIds(array $orders): array
    {
        $this->orderIncrementIds = [];
        /** @var OrderInterface $order */
        foreach ($orders as $order) {
            $this->orderIncrementIds[] = $order['OrderID'];
        }

        return $this->orderIncrementIds;
    }

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
     *
     * @return MaropostOrderCollection|array
     */
    public function getMaropostOrder(int $customerId, string $incrementId, bool $getArray = true): MaropostOrderCollection|array
    {
        $result = [];
        if ($customerId) {
            $username = $this->getUsername($customerId);
            $maropostOrderCollection = $this->orderSyncManagement->getOrdersByIncrementId($incrementId, $username);
            if ($getArray) {
                $result = $this->getMaropostOrderArray($maropostOrderCollection);
            } else {
                $result = $maropostOrderCollection;
            }
        }

        return $result;
    }

    /**
     * @param MaropostOrderCollection $maropostOrderCollection
     * @return array
     * @throws LocalizedException
     */
    private function getMaropostOrderArray(MaropostOrderCollection $maropostOrderCollection): array
    {
        $result = $maropostOrderCollection->toArray()['items'] ?? [];
        $order = $result[0] ?? [];
        if ($order) {
            $this->orderIncrementIds[] = $order['OrderID'];
            $magentoOrder = $this->getMagentoOrders();
            $logRecords = $this->getSyncRecords();
            $order = $this->addOrderData($order, $logRecords, isset($magentoOrder[$order['OrderID']]));
            $order = [$order];
        } else {
            throw new LocalizedException(__('Unable to fetch order'));
        }

        return $order;
    }

    /**
     * Add fields to order
     *
     * @param array $maropostOrder
     * @param array $logRecords
     * @param bool $isMagentoOrder
     *
     * @return array
     */
    private function addOrderData(array $maropostOrder, array $logRecords, bool $isMagentoOrder): array
    {
        $maropostOrder[self::IS_SYNCED] = true;
        $maropostOrder['amountDue'] = $this->calculateAmountDue($maropostOrder);
        if ($maropostOrder['amountDue'] > 0) {
            $maropostOrder[self::IS_SYNCED] = $this->isOrderSynced($logRecords, $maropostOrder['OrderID'], $isMagentoOrder);
        }
        $maropostOrder[self::IS_CANCELLED] =
            $maropostOrder[MaropostOrderInterface::ORDER_STATUS] == MaropostOrderInterface::ORDER_STATUS_CANCELLED;

        return $maropostOrder;
    }

    /**
     * @param array $maropostOrder
     *
     * @return string
     */
    private function calculateAmountDue(array $maropostOrder): string
    {
        $amountDue = (float) $maropostOrder['GrandTotal'];
        foreach ($maropostOrder['OrderPayment'] as $payment) {
            $amountDue = $amountDue - (float) $payment['Amount'];
        }
        $amountDue = round($amountDue, PriceCurrencyInterface::DEFAULT_PRECISION);

        return (string) ($amountDue > 0 ? $amountDue : 0);
    }

    /**
     * Get username
     *
     * @param int $customerId
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function getUsername(int $customerId): string
    {
        $customer = $this->customerRepository->getById($customerId);
        $username = $customer?->getCustomAttribute('username')?->getValue();
        if (!$username) {
            throw new LocalizedException(__('User not found'));
        }

        return $username;
    }

    /**
     * Check if order is synced to Maropost
     *
     * @param array $logRecords
     * @param string $incrementId
     * @param bool $isMagentoOrder
     *
     * @return bool
     */
    public function isOrderSynced(array $logRecords, string $incrementId, bool $isMagentoOrder): bool
    {
        return isset($logRecords[$incrementId]) ? $logRecords[$incrementId] == SyncStatusInterface::STATUS_SUCCESS
            : !$isMagentoOrder;
    }

    /**
     * Get maropost sync records
     *
     * @return array
     */
    private function getSyncRecords(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->addFilter(SyncStatusInterface::DIRECTION, SyncStatusInterface::SYNC_DIRECTION_EXPORT);
        $searchCriteria->addFilter(SyncStatusInterface::ENTITY_TYPE_ID, $this->getEntityTypeId());
        $searchCriteria->addFilter(SyncStatusInterface::MAROPOST_ENTITY_ID, $this->orderIncrementIds, 'in');
        $logRecords = $this->maropostSyncRepository
            ->getList($searchCriteria->create())
            ->getItems();
        $array = [];
        /** @var SyncStatusInterface $logRecord */
        foreach ($logRecords as $logRecord) {
            $array[$logRecord->getMaropostEntityId()] = $logRecord->getStatus();
        }

        return $array;
    }

    /**
     * Load Magento order collection
     *
     * @return array
     */
    private function getMagentoOrders(): array
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->addAttributeToSelect(OrderInterface::INCREMENT_ID)
            ->addFieldToFilter(OrderInterface::INCREMENT_ID, $this->orderIncrementIds);
        $orders = $collection->getItems();
        $array = [];
        /** @var OrderInterface $order */
        foreach ($orders as $order) {
            $array[$order->getIncrementId()] = $order;
        }

        return $array;
    }

    /**
     * Get order entity type ID
     *
     * @return int|null
     */
    private function getEntityTypeId(): ?int
    {
        $entityTypeId = null;
        try {
            $type = $this->eavConfig->getEntityType(\Magento\Sales\Model\Order::ENTITY);
            $entityTypeId = (int) $type->getEntityTypeId();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $entityTypeId;
    }
}
