<?php

/**
 *  Sekulich_OrderStatus
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\OrderStatus\Model;

use Magento\Framework\App\Area;
use Magento\Contact\Model\MailInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Mail for Catalogue requestForm extends Contact Us Mail
 */
class Mail implements MailInterface
{
    /**
     * @param Config $config
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface  $inlineTranslation,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Send email from contact form
     *
     * @param string $replyTo
     * @param array $variables
     *
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     * @return void
     */
    public function send($replyTo, array $variables): void
    {
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($variables['template'])
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId()
                    ]
                )
                ->setTemplateVars($variables)
                ->setFromByScope($this->config->emailSender())
                ->addTo($variables['customer_email'])
                ->setReplyTo($replyTo)
                ->getTransport();

            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
