<?php
declare(strict_types=1);

namespace Sekulich\OrderAttributes\Plugin;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Sekulich\OrderAttributes\Model\Config\Source\OrderType;

/**
 * Class FrontendOrderPlaceAfter
 */
class FrontendOrderPlaceAfter
{
    /** @var string  */
    public const XML_PATH_DEFAULT_ORDER_TYPE = 'order/order_type/default_order_type';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * BackOfficeOrderPlaceAfter constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Add order type for backend orders
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
            $isGuest = $order->getCustomerIsGuest();
            $orderType = $isGuest ? OrderType::ORDER_TYPE_WEBSITE_GUEST : OrderType::ORDER_TYPE_WEBSITE_USER;
            $customerId = $order->getCustomerId();
            if (!$isGuest && !$customerId) {
                $orderType = (string) $this->scopeConfig->getValue(self::XML_PATH_DEFAULT_ORDER_TYPE);
            }
            $order->setOrderType($orderType);
            try {
                $this->orderRepository->save($order);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $order;
    }

}
