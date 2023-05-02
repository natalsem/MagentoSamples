<?php

/**
 * Sekulich_MaropostOrder
 *
 * @copyright   Copyright (c) 2023 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author Natalia Sekulich <sekulich.n@gmail.com>
 */

declare(strict_types=1);

namespace Sekulich\MaropostOrder\Model;

use Exception;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Magento\Sales\Model\Order as OrderModel;
use StripeIntegration\Payments\Model\Config;
use StripeIntegration\Payments\Helper\Generic;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Sekulich\MaropostSyncLog\Model\LogManagement;
use StripeIntegration\Payments\Model\StripeCustomer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Sekulich\MaropostOrder\Api\PaymentIntentInterface;
use StripeIntegration\Payments\Model\PaymentIntentFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Sekulich\MaropostSyncLog\Api\Data\SyncStatusInterface;
use Sekulich\MaropostSync\Api\Data\MaropostOrderInterface;
use Sekulich\MaropostSync\Model\Queue\Payment\Export\Publisher;
use StripeIntegration\Payments\Model\ResourceModel\PaymentIntent as PaymentIntentModel;
use Sekulich\MaropostOrder\Model\PaymentIntentFactory as TradesquarePaymentIntentFactory;
use Sekulich\MaropostSync\Exception\ConfigurationErrorException;
use Sekulich\MaropostSync\Exception\IncorrectRequestException;

/**
 * Class OrderPaymentProcessor
 */
class OrderPaymentProcessor implements \Sekulich\MaropostOrder\Api\OrderPaymentProcessorInterface
{
    /**#@+
     * Additional fields for order data
     */
    public const AMOUNT_PAID = 'AmountPayed';
    public const DATE_PAID = 'DatePayed';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderManagement $orderManagement
     * @param LoggerInterface $logger
     * @param TradesquarePaymentIntentFactory $intentFactory
     * @param Config $config
     * @param PaymentIntentFactory $paymentIntentModelFactory
     * @param PaymentIntentModel $paymentIntentResource
     * @param Publisher $publisher
     * @param Generic $paymentHelper
     * @param LogManagement $maropostLogManager
     * @param EventManager $eventManager
     * @param Json $serializer
     * @param StripeCustomer $stripeCustomerModel
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly OrderManagement $orderManagement,
        private readonly LoggerInterface $logger,
        private readonly TradesquarePaymentIntentFactory $intentFactory,
        private readonly Config $config,
        private readonly PaymentIntentFactory $paymentIntentModelFactory,
        private readonly PaymentIntentModel $paymentIntentResource,
        private readonly Publisher $publisher,
        private readonly Generic $paymentHelper,
        private readonly LogManagement $maropostLogManager,
        private readonly EventManager $eventManager,
        private readonly Json $serializer,
        private readonly StripeCustomer $stripeCustomerModel,
    ) {
    }

    /**
     * Process Stripe payment
     *
     * @param string $incrementId
     * @param int $customerId
     * @param string $amount
     *
     * @throws ConfigurationErrorException
     * @throws IncorrectRequestException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @return PaymentIntentInterface
     */
    public function payAmountDue(string $incrementId, int $customerId, string $amount): PaymentIntentInterface
    {
        /** @var  PaymentIntentInterface $tsIntent */
        $tsIntent = $this->intentFactory->create();
        $maropostOrders = $this->orderManagement->getMaropostOrder((int) $customerId, $incrementId);
        $maropostOrder = array_shift($maropostOrders);
        $currency = $this->scopeConfig->getValue('currency/options/base');
        if (!$maropostOrder) {
            throw new LocalizedException(__('Could not get Maropost order for payment'));
        }
        $currentAmountDue = $this->calculateAmountDue($maropostOrder) * 100; //convert float value to cents
        if ($amount > $currentAmountDue) {
            $amount = $currentAmountDue;
        }
        if ($amount <= 0) {
            throw new LocalizedException(__('Payment amount due should be greater than 0.'));
        }

        $params['amount'] = $amount;
        $params['description'] =__(
            'Payment Via My Order: #%1 by %2 %3',
            $incrementId,
            $maropostOrder[MaropostOrderInterface::BILL_FIRST_NAME],
            $maropostOrder[MaropostOrderInterface::BILL_LAST_NAME]
        );
        $params['currency'] = $currency;
        try {
            /** @var \Stripe\StripeClient $stripeClient */
            $stripeClient = $this->config->getStripeClient();
            $stripeCustomer = $this->paymentHelper->getCustomerModel();
            $stripeCustomerId = $stripeCustomer->getStripeId();
            if (!$stripeCustomerId) {
                $stripeCustomerParams['magento_customer_id'] = $customerId;
                $stripeCustomerParams['email'] = $maropostOrder[MaropostOrderInterface::EMAIL];
                $newStripeCustomer = $this->stripeCustomerModel->createNewStripeCustomer($stripeCustomerParams);
                $stripeCustomerId = $newStripeCustomer->id;
            }
            $params['customer'] = $stripeCustomerId;
            $paymentIntent = $stripeClient->paymentIntents->create($params);

            $tsIntent->setClientSecret($paymentIntent->client_secret);
            $tsIntent->setPaymentType((string) $paymentIntent->payment_method);
            $tsIntent->setAmountPaid((string) $paymentIntent->amount);
            $tsIntent->setOrderId($incrementId);
            $this->savePaymentIntent($incrementId, (int) $customerId, $paymentIntent);
        } catch (ApiErrorException $e) {
            $this->logger->error($e->getMessage());
        }

        return $tsIntent;
    }

    /**
     * @param array $maropostOrder
     *
     * @return string
     */
    private function calculateAmountDue(array $maropostOrder): string
    {
        $amountDue = $maropostOrder[MaropostOrderInterface::GRAND_TOTAL];
        foreach ($maropostOrder[MaropostOrderInterface::ORDER_PAYMENT] as $payment) {
            $amountDue = $amountDue - (float) $payment['Amount'];
        }

        return (string) $amountDue;
    }

    /**
     * Save payment intent to DB
     *
     * @param string $incrementId
     * @param int $customerId
     * @param \Stripe\PaymentIntent $paymentIntent
     */
    private function savePaymentIntent(string $incrementId, int $customerId, \Stripe\PaymentIntent $paymentIntent): void
    {
        try {
            $paymentIntentModel = $this->paymentIntentModelFactory->create();
            $paymentIntentModel->setData([
                'pi_id' => $paymentIntent->id,
                'quote_id' => 0,
                'order_increment_id' => $incrementId,
                'customer_id' => $customerId
            ]);
            $this->paymentIntentResource->save($paymentIntentModel);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Add payment to Maropost (add message to tradesquare.maropost.payment.export queue)
     *
     * @param string $paymentId
     * @param string $orderData
     * @param int $customerId
     *
     * @return array
     */
    public function addPaymentToMaropost(string $paymentId, string $orderData, int $customerId): array
    {
        try {
            $orderData = $this->serializer->unserialize($orderData);
            $this->maropostLogManager->startSyncStatus(
                entityTypeCode: OrderModel::ENTITY,
                maropostEntityId: $orderData[MaropostOrderInterface::ORDER_ID],
                direction: SyncStatusInterface::SYNC_DIRECTION_EXPORT,
                status: SyncStatusInterface::STATUS_IN_PROGRESS,
            );
            /** @var \Stripe\StripeClient $stripeClient */
            $stripeClient = $this->config->getStripeClient();
            $paymentData = $stripeClient->paymentIntents->retrieve($paymentId);
            $orderData[self::DATE_PAID] = $paymentData->created;
            $orderData[self::AMOUNT_PAID] = $paymentData->amount;
            $this->eventManager->dispatch(
                'Sekulich_myaccount_payment_send_to_maropost_before',
                ['maropostOrderData' => $orderData]
            );
            $this->publisher->createPaymentMessageFromStripe($paymentData, $orderData[MaropostOrderInterface::ORDER_ID]);
            $result = 'Payment sent';
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $result = $e->getMessage();
        }

        return ['result' => $result];
    }
}
