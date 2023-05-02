<?php

/**
 *  Sekulich_OrderStatus
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\OrderStatus\Block\Adminhtml\System\Form\Field;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Sekulich\OrderStatus\Block\Adminhtml\System\Form\Field\Column\OrderStatus;
use Sekulich\OrderStatus\Block\Adminhtml\System\Form\Field\Column\EmailTemplates;

/** Order status config mapping */
class OrderStatusRow extends AbstractFieldArray
{
    /** @var string Order email template key */
    public const EMAIL_TEMPLATE = 'email_template';

    /** @var string Order status key */
    public const ORDER_STATUS = 'order_status';

    /** @var OrderStatus|null */
    protected ?OrderStatus $orderStatusRenderer = null;

    /** @var EmailTemplates|null */
    protected ?EmailTemplates $templatesRenderer = null;

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(self::ORDER_STATUS, [
            'label' => __('Order status'),
            'renderer' => $this->getOrderStatusRenderer(),
            'style' => 'width:150px'
        ]);
        $this->addColumn(self::EMAIL_TEMPLATE, [
                'label' => __('Email template'),
                'renderer' => $this->getTemplateRenderer(),
                'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Email For Status');
    }

    /**
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $orderStatus = $row->getOrderStatus();
        if ($orderStatus !== null) {
            $options['option_' . $this->getOrderStatusRenderer()->calcOptionHash($orderStatus)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Get shipping template renderer
     *
     * @return OrderStatus
     * @throws LocalizedException
     */
    private function getOrderStatusRenderer(): OrderStatus
    {
        if (!$this->orderStatusRenderer) {
            $this->orderStatusRenderer = $this->getLayout()->createBlock(
                OrderStatus::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->orderStatusRenderer->setExtraParams('style="width:120px"');
        }

        return $this->orderStatusRenderer;
    }

    /**
     * Get template renderer
     *
     * @return EmailTemplates
     * @throws LocalizedException
     */
    private function getTemplateRenderer(): EmailTemplates
    {
        if (!$this->templatesRenderer) {
            $this->templatesRenderer = $this->getLayout()->createBlock(
                EmailTemplates::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->templatesRenderer->setExtraParams('style="width:200px"');
        }

        return $this->templatesRenderer;
    }
}
