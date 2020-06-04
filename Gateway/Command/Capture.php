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
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config as TaxConfig;
use SwedbankPay\Api\Client\Exception;
use SwedbankPay\Api\Service\Data\ResponseInterface;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Collection\Item\DescriptionItem;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Collection\Item\VatSummaryItem;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Collection\ItemDescriptionCollection;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Collection\VatSummaryCollection;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\Transaction;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\TransactionObject;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Model\Service as ClientRequestService;
use SwedbankPay\Core\Exception\SwedbankPayException;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Helper\PaymentData;

/**
 * Class Capture
 *
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

        $invoices = $order->getInvoiceCollection();

        /**
         * The latest invoice will contain only the selected items(and quantities) for the (partial) capture
         * @var Invoice $invoice
         */
        $invoice = $invoices->getLastItem();

        $paymentOrder = $this->paymentData->getByOrder($order);

        $this->checkRemainingAmount('capture', $amount, $order, $paymentOrder);

        if ($paymentOrder->getIntent() == 'Sale') {
            /* Intent 'Sale' means 1-phase payment, no capture necessary */
            $this->paymentData->updateRemainingAmounts('capture', $amount, $paymentOrder);
            return null;
        }

        $itemDescriptions = new ItemDescriptionCollection();
        $vatSummaryRateAmounts = [];

        /** @var InvoiceItem $item */
        foreach ($invoice->getItemsCollection() as $item) {
            $itemTotal = ($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()) * 100;

            $description = (string)$item->getName();
            if ($item->getBaseDiscountAmount()) {
                $formattedDiscountAmount = $this->priceCurrency->format(
                    $item->getBaseDiscountAmount(),
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    $order->getStoreId()
                );
                $formattedDiscountAmount = $this->removeInvalidCharacters($formattedDiscountAmount);
                $description .= ' - ' . __('Including') . ' ' . $formattedDiscountAmount . ' ' . __('discount');
            }

            $descriptionItem = new DescriptionItem();
            $descriptionItem->setAmount($itemTotal)
                ->setDescription($description);
            $itemDescriptions->addItem($descriptionItem);

            $rate = (int)$item->getOrderItem()->getTaxPercent() * 100;

            if (!isset($vatSummaryRateAmounts[$rate])) {
                $vatSummaryRateAmounts[$rate] = ['amount' => 0, 'vat_amount' => 0];
            }

            $vatSummaryRateAmounts[$rate]['amount'] += $itemTotal;
            $vatSummaryRateAmounts[$rate]['vat_amount'] += $item->getBaseTaxAmount() * 100;
        }

        if (!$order->getIsVirtual() && $order->getBaseShippingInclTax() > 0) {
            $shippingTotal = ($order->getBaseShippingInclTax() - $order->getBaseShippingDiscountAmount()) * 100;

            $description = (string)$order->getShippingDescription();
            if ($order->getBaseShippingDiscountAmount()) {
                $formattedDiscountAmount = $this->priceCurrency->format(
                    $order->getBaseShippingDiscountAmount(),
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    $order->getStoreId()
                );
                $formattedDiscountAmount = $this->removeInvalidCharacters($formattedDiscountAmount);
                $description .= ' - ' . __('Including') . ' ' . $formattedDiscountAmount . ' ' . __('discount');
            }

            $descriptionItem = new DescriptionItem();
            $descriptionItem->setAmount($shippingTotal)
                ->setDescription($description);
            $itemDescriptions->addItem($descriptionItem);

            $rate = (int)$this->getTaxRate($order) * 100;

            if (!isset($vatSummaryRateAmounts[$rate])) {
                $vatSummaryRateAmounts[$rate] = ['amount' => 0, 'vat_amount' => 0];
            }

            $vatSummaryRateAmounts[$rate]['amount'] += $shippingTotal;
            $vatSummaryRateAmounts[$rate]['vat_amount'] += $order->getBaseShippingTaxAmount() * 100;
        }

        $vatSummaries = new VatSummaryCollection();

        foreach ($vatSummaryRateAmounts as $rate => $amounts) {
            $vatSummary = new VatSummaryItem();
            $vatSummary->setAmount($amounts['amount'])
                ->setVatAmount($amounts['vat_amount'])
                ->setVatPercent($rate);
            $vatSummaries->addItem($vatSummary);
        }

        $transaction = new Transaction();
        $transaction->setDescription("Capturing the authorized payment")
            ->setAmount($amount * 100)
            ->setVatAmount($order->getBaseTaxAmount() * 100)
            ->setPayeeReference($this->generateRandomString(30))
            ->setItemDescriptions($itemDescriptions)
            ->setVatSummary($vatSummaries);

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
