<?php

namespace SwedbankPay\Checkout\Gateway\Command;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MageOrder;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config as TaxConfig;
use SwedbankPay\Api\Client\Exception;
use SwedbankPay\Api\Service\Data\ResponseInterface;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\Transaction;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\TransactionObject;
use SwedbankPay\Checkout\Helper\Factory\OrderItemsFactory;
use SwedbankPay\Checkout\Helper\PaymentData;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Exception\SwedbankPayException;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Core\Model\Service as ClientRequestService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Capture extends AbstractCommand
{
    /**
     * @var RequestInterface|object
     */
    protected $request;

    /**
     * @var PaymentData
     */
    protected $paymentData;

    /**
     * @var Calculation
     */
    protected $calculator;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var OrderRepositoryInterface
     */
    protected $mageOrderRepo;

    /**
     * @var QuoteRe
     */
    protected $mageQuoteRepo;

    /**
     * @var OrderItemsFactory
     */
    protected $orderItemsFactory;

    /**
     * Capture constructor.
     *
     * @param ClientRequestService $requestService
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param PaymentData $paymentData
     * @param GroupRepositoryInterface $groupRepository
     * @param Calculation $calculator
     * @param PriceCurrencyInterface $priceCurrency
     * @param OrderRepositoryInterface $mageOrderRepo
     * @param OrderItemsFactory $orderItemsFactory
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        ClientRequestService $requestService,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        PaymentData $paymentData,
        GroupRepositoryInterface $groupRepository,
        Calculation $calculator,
        PriceCurrencyInterface $priceCurrency,
        OrderRepositoryInterface $mageOrderRepo,
        OrderItemsFactory $orderItemsFactory,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct(
            $requestService,
            $logger,
            $data
        );

        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->paymentData = $paymentData;
        $this->groupRepository = $groupRepository;
        $this->calculator = $calculator;
        $this->priceCurrency = $priceCurrency;
        $this->mageOrderRepo = $mageOrderRepo;
        $this->orderItemsFactory = $orderItemsFactory;
    }

    /**
     * Capture command
     *
     * @param array $commandSubject
     *
     * @return null|Command\ResultInterface
     * @throws NoSuchEntityException
     * @throws SwedbankPayException
     * @throws LocalizedException
     * @throws ServiceException
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function execute(array $commandSubject)
    {
        /** @var InfoInterface|object $payment */
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'] + 0;

        /** @var MageOrder $order */
        $order = $payment->getOrder();

        $paymentOrder = $this->paymentData->getByOrder($order);

        $this->checkRemainingAmount('capture', $amount, $order, $paymentOrder);

        if ($paymentOrder->getIntent() == 'Sale') {
            /* Intent 'Sale' means 1-phase payment, no capture necessary */
            $this->paymentData->updateRemainingAmounts('capture', $amount, $paymentOrder);
            return null;
        }

        $orderItems = $this->orderItemsFactory->createByOrder($order);

        $transaction = new Transaction();
        $transaction->setDescription("Capturing the authorized payment")
            ->setAmount($amount * 100)
            ->setVatAmount($order->getBaseTaxAmount() * 100)
            ->setPayeeReference($this->generateRandomString(30))
            ->setOrderItems($orderItems);

        $transactionObject = new TransactionObject();
        $transactionObject->setTransaction($transaction);

        $captureRequest = $this->getRequestService(
            'Paymentorder/Transaction',
            'TransactionCapture',
            $transactionObject
        );
        $captureRequest->setRequestEndpointVars(
            $this->getSwedbankPayPaymentResourceId($paymentOrder->getPaymentOrderId())
        );

        /** @var ResponseInterface $captureResponse */
        $captureResponse = $captureRequest->send();

        $this->checkResponseResource('capture', $captureResponse->getResponseResource(), $order, $paymentOrder);

        /** @var array $captureResponseData */
        $captureResponseData = $captureResponse->getResponseData();

        $transactionResult = $this->getTransactionResult('capture', $captureResponseData, $order, $paymentOrder);

        if ($transactionResult != 'complete') {
            $order->setStatus('swedbank_pay_pending');
            $this->mageOrderRepo->save($order);
            return null;
        }

        $this->paymentData->updateRemainingAmounts('capture', $amount, $paymentOrder);

        $this->logger->debug('Saving sale transaction number to order grid!');
        $order->setData('swedbank_pay_transaction_number', $captureResponseData['capture']['transaction']['number']);
        $this->mageOrderRepo->save($order);

        return null;
    }

    /**
     * Getting back the tax rate
     *
     * @param MageOrder $order
     * @return float
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getTaxRate(MageOrder $order)
    {
        $store = $order->getStore();
        $taxClassId = null;

        $groupId = $order->getCustomerGroupId();
        if ($groupId !== null) {
            $taxClassId = $this->groupRepository->getById($groupId)->getTaxClassId();
        }

        /** @var DataObject|object $request */
        $request = $this->calculator->getRateRequest(
            $order->getShippingAddress(),
            $order->getBillingAddress(),
            $taxClassId,
            $store
        );

        $taxRateId = $this->scopeConfig->getValue(
            TaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
            ScopeInterface::SCOPE_STORES,
            $store
        );

        return $this->calculator->getRate($request->setProductClassId($taxRateId));
    }

    /**
     * @param string $str
     * @return string
     */
    public function removeInvalidCharacters($str)
    {
        // Removes invalid characters
        $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);

        // Removes non-breaking spaces
        $str = preg_replace('/[\xa0]/u', ' ', $str);

        return $str;
    }
}
