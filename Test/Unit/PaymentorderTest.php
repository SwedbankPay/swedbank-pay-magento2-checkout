<?php


namespace SwedbankPay\Checkout\Test\Unit;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
use SwedbankPay\Api\Service\Paymentorder\Resource\Collection\OrderItemsCollection;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderCampaignInvoice;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderInvoice;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderObject;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderPayeeInfo;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderSwish;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderUrl;
use SwedbankPay\Checkout\Helper\Config as PaymentMenuConfig;
use SwedbankPay\Checkout\Helper\Factory\OrderItemsFactory;
use SwedbankPay\Checkout\Helper\Factory\PayerFactory;
use SwedbankPay\Checkout\Helper\Factory\RiskIndicatorFactory;
use SwedbankPay\Checkout\Helper\Paymentorder;
use SwedbankPay\Checkout\Model\QuoteFactory;
use SwedbankPay\Checkout\Model\ResourceModel\QuoteRepository;
use SwedbankPay\Core\Helper\Config as ClientConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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

    /**
     * @var OrderItemsFactory|MockObject
     */
    protected $orderItemsFactory;

    /**
     * @var PayerFactory|MockObject
     */
    protected $payerFactory;

    /**
     * @var RiskIndicatorFactory|MockObject
     */
    protected $riskIndicatorFactory;

    public function setUp()
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getGrandTotal', 'getBillingAddress', 'getShippingAddress', 'isVirtual', 'getAllVisibleItems'
            ])
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
        $this->orderItemsFactory = $this->createMock(OrderItemsFactory::class);
        $this->payerFactory = $this->createMock(PayerFactory::class);
        $this->riskIndicatorFactory = $this->createMock(RiskIndicatorFactory::class);

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
            $this->quoteRepository,
            $this->orderItemsFactory,
            $this->payerFactory,
            $this->riskIndicatorFactory
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
        $this->quote->method('getAllVisibleItems')->willReturn([]);

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

        $orderItemsCollection = $this->createMock(OrderItemsCollection::class);
        $this->orderItemsFactory->method('create')->willReturn($orderItemsCollection);

        $paymentOrderObject = $this->paymentorder->createPaymentorderObject();

        $this->assertInstanceOf(PaymentorderObject::class, $paymentOrderObject);
        $this->assertEquals('Purchase', $paymentOrderObject->getPaymentorder()->getOperation());
    }

    public function testCreatePaymentorderUpdateObject()
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

        $orderItemsCollection = $this->createMock(OrderItemsCollection::class);
        $this->orderItemsFactory->method('create')->willReturn($orderItemsCollection);

        $paymentOrderObject = $this->paymentorder->createPaymentorderUpdateObject($this->quote);

        $this->assertInstanceOf(PaymentorderObject::class, $paymentOrderObject);
        $this->assertEquals('UpdateOrder', $paymentOrderObject->getPaymentorder()->getOperation());
    }

    public function testCreatePayeeInfoObjectInstance()
    {
        $payeeInfo = $this->paymentorder->createPayeeInfoObject();

        $this->assertInstanceOf(PaymentorderPayeeInfo::class, $payeeInfo);
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

    public function testCreateUrlObjectInstance()
    {
        $this->urlInterface
            ->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://swedbankpay.com/');

        $urlData = $this->paymentorder->createUrlObject();

        $this->assertInstanceOf(PaymentorderUrl::class, $urlData);
    }

    public function testUrlObjectHasPaymentUrl()
    {
        $baseUrl = 'https://swedbankpay.com/';

        $this->urlInterface
            ->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $this->urlInterface
            ->expects($this->atLeastOnce())
            ->method('getUrl')
            ->will($this->returnArgument(0));

        $urlData = $this->paymentorder->createUrlObject();

        $this->stringContains($urlData->getPaymentUrl(), 'state');
        $this->assertNotNull($urlData->getPaymentUrl());
    }
}
