<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestContentInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use SwedbankPay\Api\Client\Exception as ClientException;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\Data\TransactionObjectInterface;
use SwedbankPay\Api\Service\Payment\Transaction\Response\Data\TransactionInterface;
use SwedbankPay\Api\Service\Paymentorder\Request\GetCurrentPayment;
use SwedbankPay\Api\Service\Paymentorder\Resource\Response\Data\GetCurrentPaymentInterface;
use SwedbankPay\Api\Service\Request;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Model\Service;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Core\Helper\Order as OrderHelper;
use SwedbankPay\Checkout\Api\Data\OrderInterface;
use SwedbankPay\Checkout\Api\Data\QuoteInterface;
use SwedbankPay\Checkout\Helper\Config as ConfigHelper;
use SwedbankPay\Checkout\Helper\PaymentData as PaymentDataHelper;
use SwedbankPay\Checkout\Helper\Paymentorder as PaymentorderHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Callback extends PaymentActionAbstract
{
    /**
     * @var Service $service
     */
    protected $service;

    /**
     * @var PaymentorderHelper $paymentorderHelper
     */
    protected $paymentorderHelper;

    /**
     * @var PaymentDataHelper $paymentDataHelper
     */
    protected $paymentDataHelper;

    /**
     * @var RequestContentInterface;
     */
    protected $requestContent;

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $magentoOrderRepo;

    /**
     * Callback constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param EventManager $eventManager
     * @param ConfigHelper $configHelper
     * @param Logger $logger
     * @param Service $service
     * @param PaymentorderHelper $paymentorderHelper
     * @param PaymentDataHelper $paymentDataHelper
     * @param JsonFactory $jsonResultFactory
     * @param OrderHelper $orderHelper
     * @param OrderRepositoryInterface $magentoOrderRepo
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        EventManager $eventManager,
        ConfigHelper $configHelper,
        Logger $logger,
        Service $service,
        PaymentorderHelper $paymentorderHelper,
        PaymentDataHelper $paymentDataHelper,
        JsonFactory $jsonResultFactory,
        OrderHelper $orderHelper,
        OrderRepositoryInterface $magentoOrderRepo
    ) {
        parent::__construct($context, $resultJsonFactory, $eventManager, $configHelper, $logger);

        $this->service = $service;
        $this->paymentorderHelper = $paymentorderHelper;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->orderHelper = $orderHelper;
        $this->paymentDataHelper = $paymentDataHelper;
        $this->magentoOrderRepo = $magentoOrderRepo;

        $this->setEventName('callback');
        $this->setEventMethod([$this, 'updatePaymentData']);
    }

    /**
     * @return array|bool|ResponseInterface|ResultInterface|string
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function updatePaymentData()
    {
        $requestData = json_decode($this->requestContent->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->createResult(
                'error',
                'Failed to JSON decode callback request data',
                json_last_error() . "\nRequest Data:\n" . $this->requestContent->getContent()
            );
        }

        $paymentOrderData = null;
        $transactionData = null;

        foreach ($requestData as $requestKey => $requestValue) {
            switch ($requestKey) {
                case 'paymentOrder':
                    $paymentOrderData = $this->getPaymentOrderData($requestValue['id'], $requestValue['instrument']);
                    break;
                case 'transaction':
                    $transactionData = $this->getTransactionData($requestValue['id'], $requestValue['number']);
                    break;
            }
        }

        if (!($transactionData instanceof TransactionInterface)) {
            return $this->createResult(
                'error',
                'Failed to retrieve transaction data',
                "Request Data:\n" . $this->requestContent->getContent()
            );
        }

        /** @var $order MagentoOrder */
        $order = $this->magentoOrderRepo->get($paymentOrderData->getOrderId());

        switch ($transactionData->getState()) {
            case 'Initialized':
                $this->orderHelper->setStatus($order, OrderHelper::STATUS_PENDING);
                break;
            case 'Completed':
                if ($order->getStatus() == OrderHelper::STATUS_PENDING) {
                    $order->setState(MagentoOrder::STATE_PROCESSING);
                    $order->setStatus(MagentoOrder::STATE_PROCESSING);
                }

                $order->addStatusToHistory($order->getStatus(), 'SwedbankPay payment processed successfully.');
                $this->magentoOrderRepo->save($order);

                if (($paymentOrderData instanceof OrderInterface) && $paymentOrderData->getIntent() == 'Sale') {
                    $this->orderHelper->createInvoice($order);
                }
                break;
            case 'Failed':
                $this->orderHelper->cancelOrder($order, 'SwedbankPay payment failed, cancelled order.');
                break;
        }

        return $this->createResult(
            'success',
            'Order was updated successfully'
        );
    }

    /**
     * @param string $paymentOrderUri
     * @param string $instrument
     * @return false|OrderInterface|QuoteInterface
     * @throws ClientException
     * @throws ServiceException
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getPaymentOrderData($paymentOrderUri, $instrument)
    {
        /**
         * $paymentOrderParams[0] Payment Order URI
         * $paymentOrderParams[1] Payment Order ID
         */
        $validUri = preg_match('|/psp/paymentorders/([^/]+)|', $paymentOrderUri, $paymentOrderParams);
        if (!$validUri) {
            return false;
        }

        list($paymentOrderUri, $paymentOrderId) = $paymentOrderParams;

        $paymentOrderData = $this->paymentDataHelper->getByPaymentOrderId($paymentOrderId);

        /** @var GetCurrentPayment $serviceRequest */
        $serviceRequest = $this->service->init('Paymentorder', 'CurrentPayment');
        $serviceRequest->setRequestEndpoint($paymentOrderUri);

        /** @var GetCurrentPaymentInterface $currentPayment */
        $currentPayment = $serviceRequest->send()->getResponseResource();
        $paymentOrderData->setIntent($currentPayment->getPayment()->getIntent());
        $this->paymentDataHelper->update($paymentOrderData);

        return $paymentOrderData;
    }

    /**
     * @param string $transactionUri
     * @param string $number
     * @return TransactionInterface|false
     * @throws ClientException
     * @throws ServiceException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function getTransactionData($transactionUri, $number)
    {
        /**
         * $transactionParams[0] Transaction URI
         * $transactionParams[1] Payment Instrument
         * $transactionParams[2] Payment Resource ID
         * $transactionParams[3] Transaction Type
         * $transactionParams[3] Transaction ID
         */
        $validUri = preg_match('|/psp/([^/]+)/payments/([^/]+)/([^/]+)/([^/]+)|', $transactionUri, $transactionParams);
        if (!$validUri) {
            return false;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        list($transactionUri, $instrument, $resourceId, $transactionType, $transactionId) = $transactionParams;

        /** @var Request $serviceRequest */
        $serviceRequest = $this->service->init(ucfirst($instrument), 'GetTransaction');
        $serviceRequest->setRequestEndpoint($transactionUri);

        /** @var TransactionObjectInterface $transactionObject */
        $transactionObject = $serviceRequest->send()->getResponseResource();

        return $transactionObject->getTransaction();
    }

    /**
     * @param $code
     * @param $message
     * @param string $debugInfo
     * @return Json
     */
    protected function createResult($code, $message, $debugInfo = '')
    {
        $result = $this->jsonResultFactory->create([
            "code" => $code,
            "message" => $message
        ]);

        if ($code == 'error') {
            $message = ($debugInfo) ? $message . "\nDebug Info:\n" . $debugInfo : $message;
            $this->logger->error(
                $message . ":\n" . $debugInfo
            );
        }

        return $result;
    }
}
