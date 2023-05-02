<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 * @author Natalia Sekulich <sekulich.n@gmail.com>
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
class View extends Template
{
    /** @var string */
    protected $_template = 'Sekulich_MaropostOrder::order/view.phtml';

    /**
     * @param InitParams $stripeHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        private readonly InitParams $stripeHelper,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Order %1', $this->_request->getParam('order_id')));
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
