<?php

namespace SwedbankPay\Checkout\Helper;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote as MageQuote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo as HeaderLogo;
use SwedbankPay\Api\Client\Client as ApiClient;
use SwedbankPay\Api\Service\Paymentorder\Resource\Collection\OrderItemsCollection;
use SwedbankPay\Api\Service\Paymentorder\Resource\Collection\PaymentorderItemsCollection;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderCampaignInvoice;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderCreditCard;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderInvoice;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderObject;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderPayeeInfo;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderPayer;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderSwish;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderUrl;
use SwedbankPay\Api\Service\Paymentorder\Resource\Request\Paymentorder as PaymentorderRequestResource;
use SwedbankPay\Checkout\Helper\Config as PaymentMenuConfig;
use SwedbankPay\Checkout\Helper\Factory\OrderItemsFactory;
use SwedbankPay\Checkout\Model\QuoteFactory;
use SwedbankPay\Checkout\Model\ResourceModel\QuoteRepository;
use SwedbankPay\Core\Helper\Config as ClientConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Paymentorder
{
    /**
     * @var CheckoutSession $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var PageHelper
     */
    protected $pageHelper;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var HeaderLogo
     */
    protected $headerLogo;

    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @var ClientConfig $clientConfig
     */
    protected $clientConfig;

    /**
     * @var PaymentMenuConfig $paymentMenuConfig
     */
    protected $paymentMenuConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Resolver $localeResolver
     */
    protected $localeResolver;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteRepository $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var OrderItemsFactory
     */
    protected $orderItemsFactory;

    public function __construct(
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        PageHelper $pageHelper,
        UrlInterface $urlInterface,
        HeaderLogo $headerLogo,
        ApiClient $apiClient,
        ClientConfig $clientConfig,
        PaymentMenuConfig $paymentMenuConfig,
        ScopeConfigInterface $scopeConfig,
        Resolver $localeResolver,
        QuoteFactory $quoteFactory,
        QuoteRepository $quoteRepository,
        OrderItemsFactory $orderItemsFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->pageHelper = $pageHelper;
        $this->urlInterface = $urlInterface;
        $this->headerLogo = $headerLogo;
        $this->apiClient = $apiClient;
        $this->clientConfig = $clientConfig;
        $this->paymentMenuConfig = $paymentMenuConfig;
        $this->scopeConfig = $scopeConfig;
        $this->localeResolver = $localeResolver;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->orderItemsFactory = $orderItemsFactory;
    }

    /**
     * Creates Paymentorder Object
     *
     * @param string|null $consumerProfileRef
     * @return PaymentorderObject
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function createPaymentorderObject($consumerProfileRef = null)
    {
        /** @var MageQuote $mageQuote */
        $mageQuote = $this->checkoutSession->getQuote();

        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $currency = $store->getCurrentCurrency()->getCode();

        $totalAmount = $mageQuote->getGrandTotal() * 100;
        $vatAmount = $this->getPaymentorderVatAmount($mageQuote);

        $urlData = $this->createUrlObject();
        $payeeInfo = $this->createPayeeInfoObject();
        $orderItems = $this->createOrderItemsObject($mageQuote);

        /**
         * Optional payment method specific stuff
         *
         * $paymentOrderItems = $this->createItemsObject();
         */

        $storeName = $this->scopeConfig->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE
        );

        $paymentOrder = new PaymentorderRequestResource();
        $paymentOrder->setOperation('Purchase')
            ->setCurrency($currency)
            ->setAmount($totalAmount)
            ->setVatAmount($vatAmount)
            ->setDescription($storeName . ' ' . __('Purchase'))
            ->setUserAgent($this->apiClient->getUserAgent())
            ->setLanguage($this->getLanguage())
            ->setGeneratePaymentToken(false)
            ->setDisablePaymentMenu(false)
            ->setUrls($urlData)
            ->setPayeeInfo($payeeInfo)
            ->setOrderItems($orderItems);

        if (isset($paymentorderItems) && ($paymentorderItems instanceof PaymentorderItemsCollection)) {
            $paymentOrder->setItems($paymentorderItems);
        }

        if ($consumerProfileRef) {
            $payer = new PaymentorderPayer();
            $payer->setConsumerProfileRef($consumerProfileRef);
            $paymentOrder->setPayer($payer);
        }

        $paymentOrderObject = new PaymentorderObject();
        $paymentOrderObject->setPaymentorder($paymentOrder);

        return $paymentOrderObject;
    }

    /**
     * @param MageQuote $mageQuote
     * @return PaymentorderObject
     */
    public function createPaymentorderUpdateObject(MageQuote $mageQuote)
    {
        $totalAmount = $mageQuote->getGrandTotal() * 100;
        $vatAmount = $this->getPaymentorderVatAmount($mageQuote);
        $orderItems = $this->createOrderItemsObject($mageQuote);

        $paymentOrder = new PaymentorderRequestResource();
        $paymentOrder->setOperation('UpdateOrder')
            ->setAmount($totalAmount)
            ->setVatAmount($vatAmount)
            ->setOrderItems($orderItems);

        $paymentOrderObject = new PaymentorderObject();
        $paymentOrderObject->setPaymentorder($paymentOrder);

        return $paymentOrderObject;
    }

    /**
     * @return PaymentorderUrl
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    public function createUrlObject()
    {
        $mageBaseUrl = $this->urlInterface->getBaseUrl();
        $mageCompleteUrl = $this->urlInterface->getUrl('checkout/onepage/success');
        $magePaymentUrl = $this->urlInterface->getUrl('checkout') . '/?state=redirected';
        $mageCancelUrl = $this->urlInterface->getUrl('checkout/cart');
        $mageCallbackUrl = $this->urlInterface->getUrl('SwedbankPayCheckout/Index/Callback');

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $baseUrlParts = parse_url($mageBaseUrl);

        $urlData = new PaymentorderUrl();
        $urlData->setHostUrls([$baseUrlParts['scheme'] . '://' . $baseUrlParts['host']])
            ->setCompleteUrl($mageCompleteUrl)
            ->setPaymentUrl($magePaymentUrl)
            ->setCancelUrl($mageCancelUrl)
            ->setCallbackUrl($mageCallbackUrl);

        if ($tosPageId = $this->paymentMenuConfig->getValue('tos_page')) {
            $urlData->setTermsOfService($this->pageHelper->getPageUrl($tosPageId));
        }

        if ($logoSrcUrl = $this->headerLogo->getLogoSrc()) {
            $urlData->setLogoUrl($logoSrcUrl);
        }

        return $urlData;
    }

    /**
     * @return PaymentorderPayeeInfo
     */
    public function createPayeeInfoObject()
    {
        $payeeInfo = new PaymentorderPayeeInfo();
        $payeeInfo->setPayeeId($this->clientConfig->getValue('payee_id'))
            ->setPayeeReference($this->generateRandomString(30));

        return $payeeInfo;
    }

    /**
     * @param MageQuote $quote
     * @return OrderItemsCollection
     */
    public function createOrderItemsObject(MageQuote $quote)
    {
        return $this->orderItemsFactory->create($quote);
    }

    /**
     * @return PaymentorderItemsCollection|null
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    public function createItemsObject()
    {
        $item = [];

        if ($creditCard = $this->createCreditCardObject()) {
            $item['credit_card'] = $creditCard;
        }

        if ($invoice = $this->createinvoiceObject()) {
            $item['invoice'] = $invoice;
        }

        if ($campaignInvoice = $this->createCampaignInvoiceObject()) {
            $item['campaign_invoice'] = $campaignInvoice;
        }

        if ($swish = $this->createSwishObject()) {
            $item['swish'] = $swish;
        }

        if (count($item) == 0) {
            return null;
        }

        $paymentorderItems = new PaymentorderItemsCollection();
        $paymentorderItems->addItem($item);

        return $paymentorderItems;
    }

    /**
     * @return PaymentorderCreditCard
     */
    public function createCreditCardObject()
    {
        $creditCard = new PaymentorderCreditCard();
        $creditCard->setNo3DSecure(false)
            ->setNo3DSecureForStoredCard(false)
            ->setRejectCardNot3DSecureEnrolled(false)
            ->setRejectCreditCards(false)
            ->setRejectDebitCards(false)
            ->setRejectConsumerCards(false)
            ->setRejectCorporateCards(false)
            ->setRejectAuthenticationStatusA(false)
            ->setRejectAuthenticationStatusU(false);

        return $creditCard;
    }

    /**
     * @return PaymentorderInvoice
     */
    public function createInvoiceObject()
    {
        $invoice = new PaymentorderInvoice();
        $invoice->setFeeAmount(1900);

        return $invoice;
    }

    /**
     * @return PaymentorderCampaignInvoice
     */
    public function createCampaignInvoiceObject()
    {
        $campaignInvoice = new PaymentorderCampaignInvoice();
        $campaignInvoice->setCampaignCode('Campaign1')
            ->setFeeAmount(2900);

        return $campaignInvoice;
    }

    public function createSwishObject()
    {
        $swish = new PaymentorderSwish();
        $swish->setEnableEcomOnly(false);

        return $swish;
    }

    /**
     * @param MageQuote $mageQuote
     * @return float|int
     */
    public function getPaymentorderVatAmount(MageQuote $mageQuote)
    {
        if ($mageQuote->isVirtual()) {
            return $mageQuote->getBillingAddress()->getTaxAmount() * 100;
        }

        return $mageQuote->getShippingAddress()->getTaxAmount() * 100;
    }

    /**
     * @param $response
     * @throws AlreadyExistsException
     * @throws Exception
     */
    public function saveQuoteToDB($response)
    {
        /** @var MageQuote $mageQuote */
        $mageQuote = $this->checkoutSession->getQuote();

        // Gets row from swedbank_pay_quotes by matching quote_id
        // Otherwise, Creates a new record
        try {
            $quote = $this->quoteRepository->getByQuoteId($mageQuote->getId());

            // If is_updated field is 0,
            // Then it doesn't update
            if ($response['payment_order']['id'] == $quote->getPaymentOrderId()
                && $quote->getIsUpdated() != 0) {
                return;
            }
        } catch (NoSuchEntityException $e) {
            $quote = $this->quoteFactory->create();
        }

        $quote->setPaymentOrderId($this->getSwedbankPayPaymentorderId($response['payment_order']['id']));
        $quote->setDescription($response['payment_order']['description']);
        $quote->setOperation($response['payment_order']['operation']);
        $quote->setState($response['payment_order']['state']);
        $quote->setCurrency($response['payment_order']['currency']);
        $quote->setAmount($response['payment_order']['amount']);
        $quote->setVatAmount($response['payment_order']['vat_amount']);
        $quote->setRemainingCapturingAmount($response['payment_order']['amount']);
        $quote->setRemainingCancellationAmount($response['payment_order']['amount']);
        $quote->setRemainingReversalAmount(0);
        $quote->setPayerToken('');
        $quote->setQuoteId($mageQuote->getId());
        $quote->setIsUpdated(0);
        $quote->setCreatedAt($response['payment_order']['created']);
        $quote->setUpdatedAt($response['payment_order']['updated']);
        $this->quoteRepository->save($quote);
    }

    /**
     * Extracts Id from SwedbankPay Paymentorder Id, ex: 5adc265f-f87f-4313-577e-08d3dca1a26c
     *
     * @param $paymentorderId
     * @return string
     */
    public function getSwedbankPayPaymentorderId($paymentorderId)
    {
        // TODO: Code like this should not exist, as it is a clear violation of one of the
        //       most important design principles of Swedbank Pay's APIs:
        //       https://developer.swedbankpay.com/#uri-usage
        return str_replace('/psp/paymentorders/', '', $paymentorderId);
    }

    /**
     * Generates a random string
     *
     * @param $length
     * @return bool|string
     */
    protected function generateRandomString($length = 12)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Gets language in SwedbankPay supported format, ex: nb-No
     *
     * @return string
     */
    protected function getLanguage()
    {
        return str_replace('_', '-', $this->localeResolver->getLocale());
    }
}
