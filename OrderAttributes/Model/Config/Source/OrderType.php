<?php
declare(strict_types=1);

namespace Sekulich\OrderAttributes\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class OrderType
 */
class OrderType implements OptionSourceInterface
{
    public const ORDER_TYPE_WEBAPI = 'webapi';
    public const ORDER_TYPE_BACKOFFICE = 'backoffice';
    public const ORDER_TYPE_WEBSITE_USER = 'website_user';
    public const ORDER_TYPE_WEBSITE_GUEST = 'website_guest';

    /**
     * Get order type options array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => ''],
            ['value' => self::ORDER_TYPE_WEBSITE_USER, 'label' => __('Website User')],
            ['value' => self::ORDER_TYPE_WEBSITE_GUEST, 'label' => __('Website Guest')],
            ['value' => self::ORDER_TYPE_BACKOFFICE, 'label' => __('BackOffice')],
            ['value' => self::ORDER_TYPE_WEBAPI, 'label' => __('Web API')],
        ];
    }
}
