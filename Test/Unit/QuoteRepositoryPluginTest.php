<?php

namespace SwedbankPay\Checkout\Test\Unit;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote as MageQuote;
use Magento\Sales\Model\Order as MageOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SwedbankPay\Api\Service\Data\RequestInterface;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Checkout\Helper\Paymentorder;
use SwedbankPay\Checkout\Model\Quote as SwedbankPayQuote;
use SwedbankPay\Checkout\Model\ResourceModel\OrderRepository;
use SwedbankPay\Checkout\Model\ResourceModel\QuoteRepository;
use SwedbankPay\Checkout\Plugin\QuoteRepositoryPlugin;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Core\Model\Service;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteRepositoryPluginTest extends TestCase
{
    /**
     * @var QuoteRepositoryPlugin
     */
    protected $quoteRepositoryPlugin;

    /**
     * @var QuoteRepository|MockObject
     */
    protected $quoteRepository;

    /**
     * @var OrderRepository|MockObject
     */
    protected $orderRepository;

    /**
     * @var Paymentorder|MockObject
     */
    protected $paymentorder;

    /**
     * @var Service|MockObject
     */
    protected $service;

    /**
     * @var Config|MockObject
     */
    protected $config;

    /**
     * @var Logger|MockObject
     */
    protected $logger;

    /**
     * @var MageQuote|MockObject
     */
    protected $mageQuote;

    /**
     * @var SwedbankPayQuote|MockObject
     */
    protected $swedbankPayQuote;

    /**
     * @var RequestInterface|MockObject
     */
    protected $updateRequest;

    public function setUp()
    {
        $this->mageQuote = $this->getMockBuilder(MageQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGrandTotal'])
            ->getMock();

        $this->swedbankPayQuote = $this->createMock(SwedbankPayQuote::class);
        $this->updateRequest = $this->createMock(RequestInterface::class);
        $this->quoteRepository = $this->createMock(QuoteRepository::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->paymentorder = $this->createMock(Paymentorder::class);
        $this->service = $this->createMock(Service::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);

        $this->quoteRepositoryPlugin = new QuoteRepositoryPlugin(
            $this->quoteRepository,
            $this->orderRepository,
            $this->paymentorder,
            $this->service,
            $this->config,
            $this->logger
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateOrderRequestWasNotSentWhenAmountWsUnchanged()
    {
        $quoteGrandTotal = 149.00;
        $swedbankPayQuoteAmount = 14900;

        $this->logger->expects($this->exactly(4))->method('debug');
        $this->mageQuote->expects($this->atLeastOnce())->method('getGrandTotal')->willReturn($quoteGrandTotal);

        $this->swedbankPayQuote
            ->expects($this->atLeastOnce())
            ->method('getAmount')
            ->willReturn($swedbankPayQuoteAmount);
        $this->updateRequest->expects($this->never())->method('send');

        $this->quoteRepositoryPlugin->updatePaymentorder($this->mageQuote, $this->swedbankPayQuote);
    }

    /**
     * @throws Exception
     */
    public function testUpdateOrderRequestWasNotSentWhenOrderIsAlreadyCreated()
    {
        $quoteGrandTotal = 199.00;
        $swedbankPayQuoteAmount = 14900;
        $paymentOrderId = 'ec9bc9ea-a862-4b1a-d973-08d8223a6b0f';

        $order = $this->createMock(MageOrder::class);

        $this->orderRepository->expects($this->once())->method('getByPaymentOrderId')->willReturn($order);
        $this->logger->expects($this->exactly(4))->method('debug');
        $this->mageQuote->expects($this->atLeastOnce())->method('getGrandTotal')->willReturn($quoteGrandTotal);

        $this->swedbankPayQuote
            ->expects($this->atLeastOnce())
            ->method('getAmount')
            ->willReturn($swedbankPayQuoteAmount);

        $this->swedbankPayQuote
            ->expects($this->atLeastOnce())
            ->method('getPaymentOrderId')
            ->willReturn($paymentOrderId);

        $this->updateRequest->expects($this->never())->method('send');

        $this->quoteRepositoryPlugin->updatePaymentorder($this->mageQuote, $this->swedbankPayQuote);
    }

    /**
     * @throws Exception
     */
    public function testUpdateOrderRequestWasSent()
    {
        $quoteGrandTotal = 199.00;
        $swedbankPayQuoteAmount = 14900;
        $paymentOrderId = 'ec9bc9ea-a862-4b1a-d973-08d8223a6b0f';

        $this->logger->expects($this->exactly(3))->method('debug');
        $this->mageQuote->expects($this->atLeastOnce())->method('getGrandTotal')->willReturn($quoteGrandTotal);

        $this->orderRepository
            ->expects($this->once())
            ->method('getByPaymentOrderId')
            ->willThrowException(new NoSuchEntityException());

        $this->swedbankPayQuote
            ->expects($this->atLeastOnce())
            ->method('getAmount')
            ->willReturn($swedbankPayQuoteAmount);

        $this->swedbankPayQuote
            ->expects($this->atLeastOnce())
            ->method('getPaymentOrderId')
            ->willReturn($paymentOrderId);

        $this->service->expects($this->once())->method('init')->willReturn($this->updateRequest);
        $this->updateRequest->expects($this->once())->method('setRequestEndpointVars');
        $this->updateRequest->expects($this->once())->method('send');

        $this->quoteRepositoryPlugin->updatePaymentorder($this->mageQuote, $this->swedbankPayQuote);
    }
}
