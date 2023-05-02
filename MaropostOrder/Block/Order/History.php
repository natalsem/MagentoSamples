<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

namespace Sekulich\MaropostOrder\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use StripeIntegration\Payments\Helper\InitParams;

/**
 * Class History implement block for customer orders history
 *
 * @SuppressWarnings(PHPMD)
 */
class History extends Template
{
    /** @var string */
    protected $_template = 'Sekulich_MaropostOrder::order/history.phtml';

    /**
     * @param Context $context
     * @param InitParams $stripeHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly InitParams $stripeHelper,
        array $data = []
    ){
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Orders'));
    }

    /**
     * Get json with stripe initialization configs
     *
     * @return string
     */
    public function getStripeConfig()
    {
        return $this->stripeHelper->getCheckoutParams();
    }
}
