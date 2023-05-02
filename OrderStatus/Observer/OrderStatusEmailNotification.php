<?php

/**
 *  Sekulich_OrderStatus
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\OrderStatus\Observer;

use Exception;
use Psr\Log\LoggerInterface;
use Sekulich\OrderStatus\Model\Config;
use Magento\Sales\Api\Data\OrderInterface;
use Sekulich\OrderStatus\Model\Mail;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Notifier for order status change
 */
class OrderStatusEmailNotification implements ObserverInterface
{
    /**
     * @param Config $config
     * @param Mail $emailSender
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly Mail $emailSender,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Execute
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            $order = $observer->getEvent()->getOrder();
            $newStatus = $order->getStatus();
            $originalStatus = $order->getOrigData('status');
            $template = $this->getEmailTemplate($newStatus);
            if ($newStatus != $originalStatus && $template) {
                $this->sendEmail($order, $template);
            }
        }
    }

    /**
     * Get email template for order status
     *
     * @param string $status
     *
     * @return string|null
     */
    private function getEmailTemplate(string $status): ?string
    {
        return $this->config->getTemplateForStatus($status);
    }

    /**
     * Prepare data and send email
     *
     * @param OrderInterface $order
     * @param string $emailTemplate
     *
     * @return void
     */
    public function sendEmail(OrderInterface $order, string $emailTemplate): void
    {
        $variables = [
            'template' => $emailTemplate,
            'order_number' => $order->getIncrementId(),
            'customer_fullname' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
            'customer_email' => $order->getCustomerEmail(),
        ];

        try {
            $this->emailSender->send($this->config->emailSender(), $variables);
            if ($order->getStatus() == 'quote_approval') {
                $emailRecipient = $this->config->emailRecipient();
                if ($emailRecipient) {
                    $variables['template'] = $this->config->getBackendTemplate();
                    $this->emailSender->send($emailRecipient, $variables);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
