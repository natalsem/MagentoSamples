<?php
declare(strict_types=1);

namespace Sekulich\OrderAttributes\Plugin;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Sekulich\OrderAttributes\Model\Config\Source\OrderType;
use Psr\Log\LoggerInterface;

/**
 * Class BackOfficeOrderPlaceAfter
 */
class BackOfficeOrderPlaceAfter
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * BackOfficeOrderPlaceAfter constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
      OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Add order type
     *
     * @param OrderManagementInterface $subject
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    public function afterPlace(OrderManagementInterface $subject, OrderInterface $order): OrderInterface
    {
        $orderType = $order->getOrderType();
        if (!$orderType) {
            $order->setOrderType(OrderType::ORDER_TYPE_BACKOFFICE);
            try {
                $this->orderRepository->save($order);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $order;
    }
}
