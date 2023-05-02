<?php

/**
 *  Sekulich_OrderStatus
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\OrderStatus\Block\Adminhtml\System\Form\Field\Column;

use Magento\Shipping\Model\Config;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

/**
 * Class ShippingMethods implements shipping method column
 */
class OrderStatus extends Select
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $shippingConfig
     * @param CollectionFactory $statusCollectionFactory
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly Config $shippingConfig,
        protected readonly CollectionFactory $statusCollectionFactory,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Set input name
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setInputName(mixed $value): static
    {
        return $this->setName($value);
    }

    /**
     * Set input ID
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setInputId(mixed $value): static
    {
        return $this->setId($value);
    }

    /**
     * Options to HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * Get order status options
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $options = $this->statusCollectionFactory->create()->toOptionArray();

        return $options;
    }
}
