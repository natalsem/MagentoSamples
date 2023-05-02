<?php

/**
 *  Sekulich_OrderStatus
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\OrderStatus\Setup\Patch\Data;

use Magento\Email\Model\ResourceModel\Template as EmailTemplateResourceModel;
use Magento\Email\Model\Template;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Mail\TemplateInterface;
use Magento\Framework\Mail\TemplateInterfaceFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddEmailTemplate
 */
class AddEmailTemplate implements DataPatchInterface
{
    /** @var string XML path for quote confirmation email */
    public const XML_PATH_QUOTE_CONFIRMED_EMAIL_TEMPLATE = 'sales_email/order_status_notification/quote_confirmation_email_template';

    /** @var string XML path for quote approval email */
    public const XML_PATH_QUOTE_APPROVED_CUSTOMER_EMAIL_TEMPLATE = 'sales_email/order_status_notification/quote_approved_customer_email_template';

    /** @var string XML path for quote approval backend email for manager */
    public const XML_PATH_QUOTE_APPROVED_MANAGER_EMAIL_TEMPLATE = 'sales_email/order_status_notification/quote_approved_manager_email_template';

    /**
     * Constructor
     *
     * @param TemplateInterfaceFactory $emailTemplateFactory
     * @param EmailTemplateResourceModel $emailTemplateResourceModel
     * @param WriterInterface $configWriter
     */
    public function __construct(
        private readonly TemplateInterfaceFactory $emailTemplateFactory,
        private readonly EmailTemplateResourceModel $emailTemplateResourceModel,
        private readonly WriterInterface $configWriter
    ) {
    }

    /**
     * Apply Patch
     *
     * @throws AlreadyExistsException
     */
    public function apply()
    {
        $emailTemplatesData = $this->getTemplateData();
        foreach ($emailTemplatesData as $templatesData) {
            /** @var Template $emailTemplate */
            $emailTemplate = $this->emailTemplateFactory->create();
            $emailTemplate->setData($templatesData);
            $this->emailTemplateResourceModel->save($emailTemplate);
            $this->configWriter->save($templatesData['xml_path'], $emailTemplate->getId());
        }
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get Patch Aliases
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Get New Template Data
     *
     * @return array
     */
    private function getTemplateData(): array
    {
        return [
            [
                'xml_path' => self::XML_PATH_QUOTE_CONFIRMED_EMAIL_TEMPLATE,
                'template_code' => 'Shipping Quote Confirmation',
                'template_text' => '{{template config_path="design/email/header_template"}}
                    <p>{{trans "Shipping Quote is confirmed for your order #%order_number" order_number=$order_number}}.
                    {{trans "Please accept the quote from"}} 
                    <a href="{{var this.getUrl($store, sales/order/history/)}}">{{trans "My orders page"}}</a> 
                    {{trans "by logging into your account."}}
                    </p>
                 
                    {{template config_path="design/email/footer_template"}}',
                'template_type' => TemplateInterface::TYPE_HTML,
                'template_subject' => '{{trans "Shipping Quote is Confirmed"}}',
                'orig_template_code' => 'shipping_quote_confirmation_email_template',
                'orig_template_variables' => '{"var customer_email":"Customer Email","var customer_fullname":"Customer Name", "var order_number": "Order Number"}',
            ],
            [
                'xml_path' => self::XML_PATH_QUOTE_APPROVED_CUSTOMER_EMAIL_TEMPLATE,
                'template_code' => 'Quote Acceptation',
                'template_text' => '{{template config_path="design/email/header_template"}}
                    <p>{{trans "You have accepted the Shipping Quote for order ID #%order_number." order_number=$order_number}}</p>
                    {{template config_path="design/email/footer_template"}}',
                'template_type' => TemplateInterface::TYPE_HTML,
                'template_subject' => '{{trans "Shipping Quote is accepted"}}',
                'orig_template_code' => 'shipping_quote_approved_customer_email_template', 'orig_template_variables' => '{"var customer_email":"Customer Email","var customer_fullname":"Customer Name", "var order_number": "Order Number"}',
            ],
            [
                'xml_path' => self::XML_PATH_QUOTE_APPROVED_MANAGER_EMAIL_TEMPLATE,
                'template_code' => 'Quote Approved By Customer',
                'template_text' => '{{template config_path="design/email/header_template"}}
                    <p>{{trans "Shipping Quote #%order_number was approved by customer." order_number=$order_number}}</p>
                    {{template config_path="design/email/footer_template"}}',
                'template_type' => TemplateInterface::TYPE_HTML,
                'template_subject' => '{{trans "Quote Approved By Customer"}}',
                'orig_template_code' => '{{template config_path="design/email/header_template"}}
                    <p>{{trans "Shipping Quote #%order_number was approved by customer." order_number=$order_number}}</p>
                    {{template config_path="design/email/footer_template"}}',
                'orig_template_variables' => '{"var customer_email":"Customer Email","var customer_fullname":"Customer Name", "var order_number": "Order Number"}',
            ]
        ];
    }
}
