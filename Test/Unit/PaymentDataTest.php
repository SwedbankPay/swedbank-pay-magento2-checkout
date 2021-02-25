<?php

namespace SwedbankPay\Checkout\Test\Unit;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SwedbankPay\Checkout\Api\Data\OrderInterface as PaymentOrderInterface;
use SwedbankPay\Checkout\Api\Data\QuoteInterface as PaymentQuoteInterface;
use SwedbankPay\Checkout\Api\OrderRepositoryInterface as PaymentOrderRepository;
use SwedbankPay\Checkout\Api\QuoteRepositoryInterface as PaymentQuoteRepository;
use SwedbankPay\Checkout\Helper\PaymentData;
use SwedbankPay\Core\Logger\Logger;

class PaymentDataTest extends TestCase
{
    /**
     * @var PaymentData
     */
    protected $paymentData;

    /**
     * @var PaymentOrderRepository|MockObject
     */
    protected $paymentOrderRepo;

    /**
     * @var PaymentQuoteRepository|MockObject
     */
    protected $paymentQuoteRepo;

    /**
     * @var Logger|MockObject
     */
    protected $logger;

    public function setUp()
    {
        $this->paymentOrderRepo = $this->getMockBuilder(PaymentOrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentQuoteRepo = $this->getMockBuilder(PaymentQuoteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentData = new PaymentData(
            $this->paymentOrderRepo,
            $this->paymentQuoteRepo,
            $this->logger
        );
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testGetByPaymentOrderIdThrowsException()
    {
        $paymentOrderId = "5adc265f-f87f-4313-577e-08d3dca1a26c";

        $this->paymentOrderRepo->method('getByPaymentOrderId')->willThrowException(new NoSuchEntityException());
        $this->paymentQuoteRepo->method('getByPaymentOrderId')->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(
            sprintf("Unable to find a SwedbankPay payment matching Payment Order ID:\n%s", $paymentOrderId)
        );

        $this->paymentData->getByPaymentOrderId($paymentOrderId);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testGetByPaymentOrderIdReturnsOrderInstance()
    {
        $paymentOrderId = "5adc265f-f87f-4313-577e-08d3dca1a26c";

        $order = $this->createMock(PaymentOrderInterface::class);

        $this->paymentOrderRepo->method('getByPaymentOrderId')->will($this->returnValue($order));
        $this->paymentQuoteRepo->method('getByPaymentOrderId')->willThrowException(new NoSuchEntityException());

        $this->assertInstanceOf(
            PaymentOrderInterface::class,
            $this->paymentData->getByPaymentOrderId($paymentOrderId)
        );

        $this->assertNotInstanceOf(
            PaymentQuoteInterface::class,
            $this->paymentData->getByPaymentOrderId($paymentOrderId)
        );
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testGetByPaymentOrderIdReturnsQuoteInstance()
    {
        $paymentOrderId = "5adc265f-f87f-4313-577e-08d3dca1a26c";

        $quote = $this->createMock(PaymentQuoteInterface::class);

        $this->paymentOrderRepo->method('getByPaymentOrderId')->willThrowException(new NoSuchEntityException());
        $this->paymentQuoteRepo->method('getByPaymentOrderId')->will($this->returnValue($quote));

        $this->assertNotInstanceOf(
            PaymentOrderInterface::class,
            $this->paymentData->getByPaymentOrderId($paymentOrderId)
        );

        $this->assertInstanceOf(
            PaymentQuoteInterface::class,
            $this->paymentData->getByPaymentOrderId($paymentOrderId)
        );
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testGetByOrderThrowsException()
    {
        $order = $this->createMock(PaymentOrderInterface::class);

        $this->paymentOrderRepo->method('getByOrderId')->will($this->returnValue($order));

        $this->expectException(NoSuchEntityException::class);
        $this->paymentData->getByOrder(null);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testGetByOrderWithOrderIdReturnsOrder()
    {
        $orderId = 100;

        $order = $this->createMock(PaymentOrderInterface::class);
        $order->method('getId')->will($this->returnValue($orderId));

        $this->paymentOrderRepo->method('getByOrderId')->will($this->returnValue($order));

        $this->assertInstanceOf(PaymentOrderInterface::class, $this->paymentData->getByOrder($orderId));
        $this->assertEquals($orderId, $this->paymentData->getByOrder($orderId)->getId());
    }
}
