<?php
declare(strict_types=1);

namespace Sekulich\OrderAttributes\Model;

use Exception;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\Listing\Columns\Column;
use Psr\Log\LoggerInterface;

/**
 * Class OrderTypeColumn
 */
class OrderTypeColumn extends Column
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param OrderRepository $orderRepository
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param LoggerInterface $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        OrderRepository $orderRepository,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    /**
     * Prepare data source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        foreach ($dataSource['data']['items'] as &$item) {
            $orderType = '-';
            try {
                $order = $this->orderRepository->get((int) $item['entity_id']);
                $orderType = $order->getOrderType();
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
            $item[$this->getData('name')] = $orderType;
        }

        return $dataSource;
    }
}
