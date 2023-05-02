<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\Controller\Order;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Element\Html\Links;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Class View
 */
class View implements OrderInterface, HttpGetActionInterface
{

    /**
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param Session $customerSession
     */
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly Session $customerSession,
    ) {
    }

    /**
     * Order view page
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');

            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();
        /** @var Links $navigationBlock */
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        $navigationBlock?->setActive('sales/order/history');

        return $resultPage;
    }
}
