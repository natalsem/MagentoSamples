<?php

/**
 *  Sekulich_OrderStatus
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\OrderStatus\Model\Adminhtml\System\Config;

use Magento\Framework\Exception\CouldNotSaveException;

/** Backend model for config */
class OrderStatusMapping extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{
    /**
     * Validate unique values
     *
     * @throws CouldNotSaveException
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            unset($value['__empty']);
            $statuses = [];
            foreach ($value as $item) {
                if (in_array($item['order_status'], $statuses)) {
                    throw new CouldNotSaveException(__('Order status value should be unique'));
                } else {
                    $statuses[] = $item['order_status'];
                }
            }
        }
        return parent::beforeSave();
    }
}
