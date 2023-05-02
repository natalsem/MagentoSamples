<?php

/**
 * Sekulich_MaropostOrder
 *
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\Observer;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\DateTime;
use Sekulich\MaropostOrder\Model\MailSender;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\MailException;
use Sekulich\MaropostOrder\Model\EmailConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Sekulich\MaropostOrder\Model\OrderPaymentProcessor;
use Sekulich\MaropostSync\Api\Data\MaropostOrderInterface;

/**
 * PaymentEmail observer to trigger email after My Account payment
 */
class PaymentEmail implements ObserverInterface
{
    /**
     * @param LoggerInterface $logger
     * @param MailSender $mailSender
     * @param EmailConfig $emailConfig
     * @param TimezoneInterface $timezone
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly LoggerInterface        $logger,
        private readonly MailSender             $mailSender,
        private readonly EmailConfig            $emailConfig,
        private readonly TimezoneInterface      $timezone,
        private readonly PriceCurrencyInterface $priceCurrency,
    ) {
    }

    /**
     * Send email notification for My Account order payment
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ($this->emailConfig->isEnabled()) {
            $event = $observer->getEvent();
            $maropostOrder = $event->getData('maropostOrderData');
            $maropostOrder[OrderPaymentProcessor::DATE_PAID] = $this->timezone
                ->date($maropostOrder[OrderPaymentProcessor::DATE_PAID])
                ->format(DateTime::DATETIME_PHP_FORMAT);
            $maropostOrder[OrderPaymentProcessor::AMOUNT_PAID] = $this->priceCurrency->format(
                $maropostOrder[OrderPaymentProcessor::AMOUNT_PAID] / 100, // convert cents to dollars
                false
            );
            try {
                $this->validateParams($maropostOrder);
                $this->sendEmail($maropostOrder);
            } catch (Exception $e) {
                $this->logger->error('My Account payment email error: '. $e->getMessage());
            }
        }
    }

    /**
     * Method to send email
     *
     * @param array $variables
     *
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     * @return void
     */
    private function sendEmail(array $variables): void
    {
        $this->mailSender->send($variables);
    }

    /**
     * Method to validated params
     *
     * @param array $variables
     *
     * @throws LocalizedException
     * @return void
     */
    private function validateParams(array $variables): void
    {
        $requiredFields = [
            MaropostOrderInterface::BILL_COMPANY,
            MaropostOrderInterface::ORDER_ID,
            MaropostOrderInterface::EMAIL,
            OrderPaymentProcessor::AMOUNT_PAID,
            OrderPaymentProcessor::DATE_PAID,
        ];

        foreach ($requiredFields as $field) {
            if (!isset($variables[$field])) {
                throw new LocalizedException(__('Missing required param %1', $field));
            }
            if ($field == MaropostOrderInterface::EMAIL) {
                if (!str_contains($variables[$field], '@')) {
                    throw new LocalizedException(__('The email address is invalid. Verify the email address and try again.'));
                }
            }
        }
    }
}
