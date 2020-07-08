<?php


namespace SwedbankPay\Checkout\Test\Unit;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo as HeaderLogo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SwedbankPay\Api\Client\Client as ApiClient;
use SwedbankPay\Api\Service\Paymentorder\Resource\Collection\ItemsCollection;
use SwedbankPay\Api\Service\Paymentorder\Resource\Collection\PaymentorderItemsCollection;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderCampaignInvoice;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderInvoice;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderObject;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderPayeeInfo;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderSwish;
use SwedbankPay\Checkout\Helper\Config as PaymentMenuConfig;
use SwedbankPay\Checkout\Helper\Paymentorder;
use SwedbankPay\Checkout\Model\QuoteFactory;
use SwedbankPay\Checkout\Model\ResourceModel\QuoteRepository;
use SwedbankPay\Core\Helper\Config as ClientConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class PaymentorderTest extends TestCase
{
    /**
     * @var Paymentorder
     */
    protected $paymentorder;

    /**
     * @var Quote|MockObject
     */
    protected $quote;

    /**
     * @var CheckoutSession|MockObject
     */
    protected $checkoutSession;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var PageHelper|MockObject
     */
    protected $pageHelper;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlInterface;

    /**
     * @var HeaderLogo|MockObject
     */
    protected $headerLogo;

    /**
     * @var ApiClient|MockObject
     */
    protected $apiClient;

    /**
     * @var ClientConfig|MockObject
     */
    protected $clientConfig;

    /**
     * @var PaymentMenuConfig|MockObject
     */
    protected $paymentMenuConfig;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfig;

    /**
     * @var Resolver|MockObject
     */
    protected $localeResolver;

    /**
     * @var QuoteFactory|MockObject
     */
    protected $quoteFactory;

    /**
     * @var QuoteRepository|MockObject
     */
    protected $quoteRepository;

    public function setUp()
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGrandTotal', 'getBillingAddress', 'getShippingAddress', 'isVirtual'])
            ->getMock();

        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->checkoutSession->method('getQuote')->will($this->returnValue($this->quote));

        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->pageHelper = $this->createMock(PageHelper::class);
        $this->urlInterface = $this->createMock(UrlInterface::class);
        $this->headerLogo = $this->createMock(HeaderLogo::class);
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->clientConfig = $this->createMock(ClientConfig::class);
        $this->paymentMenuConfig = $this->createMock(PaymentMenuConfig::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->localeResolver = $this->createMock(Resolver::class);
        $this->quoteFactory = $this->getMockBuilder('SwedbankPay\Checkout\Model\QuoteFactory')->getMock();
        $this->quoteRepository = $this->createMock(QuoteRepository::class);

        $this->paymentorder = new Paymentorder(
            $this->checkoutSession,
            $this->storeManager,
            $this->pageHelper,
            $this->urlInterface,
            $this->headerLogo,
            $this->apiClient,
            $this->clientConfig,
            $this->paymentMenuConfig,
            $this->scopeConfig,
            $this->localeResolver,
            $this->quoteFactory,
            $this->quoteRepository
        );
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testCreatePaymentorderObject()
    {
        $billingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTaxAmount'])
            ->getMock();
        $billingAddress->method('getTaxAmount')->will($this->returnValue(25));

        $shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTaxAmount'])
            ->getMock();
        $shippingAddress->method('getTaxAmount')->will($this->returnValue(0));

        $this->quote->method('getGrandTotal')->will($this->returnValue(100));
        $this->quote->method('getBillingAddress')->will($this->returnValue($billingAddress));
        $this->quote->method('getShippingAddress')->will($this->returnValue($shippingAddress));
        $this->quote->method('isVirtual')->will($this->returnValue(false));

        $currencyCode = 'SEK';
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency->method('getCode')->will($this->returnValue($currencyCode));

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getCurrentCurrency')->will($this->returnValue($currency));

        $this->storeManager->method('getStore')->will($this->returnValue($store));
        $this->urlInterface->method('getBaseUrl')->willReturn('https://swedbankpay.com/');

        $this->assertInstanceOf(PaymentorderObject::class, $this->paymentorder->createPaymentorderObject());
    }

    public function testCreatePayeeInfoObjectInstance()
    {
        $payeeInfo = $this->paymentorder->createPayeeInfoObject();

        $this->assertInstanceOf(PaymentorderPayeeInfo::class, $payeeInfo);
    }

    public function testCreateItemsObjectInstance()
    {
        $paymentorderItems = $this->paymentorder->createItemsObject();

        $this->assertInstanceOf(PaymentorderItemsCollection::class, $paymentorderItems);
    }

    public function testCreateInvoiceObjectInstance()
    {
        $invoice = $this->paymentorder->createInvoiceObject();

        $this->assertInstanceOf(PaymentorderInvoice::class, $invoice);
    }

    public function testCreateCampaignInvoiceObjectInstance()
    {
        $campaignInvoice = $this->paymentorder->createCampaignInvoiceObject();

        $this->assertInstanceOf(PaymentorderCampaignInvoice::class, $campaignInvoice);
    }

    public function testCreateSwishObjectInstance()
    {
        $swish = $this->paymentorder->createSwishObject();

        $this->assertInstanceOf(PaymentorderSwish::class, $swish);
    }
}
