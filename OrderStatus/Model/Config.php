<?php

/**
 *  Sekulich_OrderStatus
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\OrderStatus\Model;

use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Magento\Store\Model\ScopeInterface;
use Magento\Contact\Model\ConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Sekulich\OrderStatus\Setup\Patch\Data\AddEmailTemplate;
use Sekulich\OrderStatus\Block\Adminhtml\System\Form\Field\OrderStatusRow;

/**
 * Class Configs
 */
class Config
{
    /** @var string Config path email template */
    public const XML_PATH_EMAIL_TEMPLATE = 'catalog_request/email/email_template';

    /** @var string Config path for mapping order status with email templates */
    public const XML_PATH_STATUS_MAPPING = 'sales_email/order_status_notification/status_mapping';

    /** @var string Config path for backend mail notification address */
    public const XML_PATH_BACKEND_MAIL_RECIPIENT = 'sales_email/order_status_notification/recipient_email';

    /** @var string Config path for enable emails */
    public const XML_PATH_EMAILS_ENABLED = 'sales_email/order_status_notification/enabled';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Get template for the order status
     *
     * @param string $orderStatus
     *
     * @return string|null
     */
    public function getTemplateForStatus(string $orderStatus): ?string
    {
        $result = null;
        $statusMappingArray = [];
        $statusMapping = $this->scopeConfig->getValue(self::XML_PATH_STATUS_MAPPING);
        try {
            $statusMappingArray = $this->serializer->unserialize($statusMapping);
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
        }
        foreach ($statusMappingArray as $statusConfig) {
            if ($statusConfig[OrderStatusRow::ORDER_STATUS] == $orderStatus) {
                $result = $statusConfig[OrderStatusRow::EMAIL_TEMPLATE];
                break;
            }
        }

        return $result;
    }

    /**
     * Get email sender
     *
     * @return string
     */
    public function emailSender(): string
    {
        return (string) $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get email address for backend mails
     *
     * @return string|null
     */
    public function emailRecipient(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BACKEND_MAIL_RECIPIENT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if emails are enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EMAILS_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get email template for backend mail
     *
     * @return string|null
     */
    public function getBackendTemplate(): ?string
    {
        return $this->scopeConfig->getValue(AddEmailTemplate::XML_PATH_QUOTE_APPROVED_MANAGER_EMAIL_TEMPLATE);
    }
}
