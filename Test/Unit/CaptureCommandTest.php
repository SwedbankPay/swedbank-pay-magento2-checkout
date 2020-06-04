<?php

namespace SwedbankPay\Checkout\Test\Unit;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Tax\Model\Calculation;
use PHPUnit\Framework\TestCase;
use SwedbankPay\Checkout\Gateway\Command\Capture;
use SwedbankPay\Checkout\Helper\PaymentData;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Core\Model\Service;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class CaptureCommandTest extends TestCase
{
    /**
     * @var Capture
     */
    protected $captureCommand;

    public function setUp()
    {
        /** @var Service $clientRequestService */
        $clientRequestService = $this->createMock(Service::class);

        /** @var ScopeConfigInterface $scopeConfigInterface */
        $scopeConfigInterface = $this->createMock(ScopeConfigInterface::class);

        /** @var RequestInterface $requestInterface */
        $requestInterface = $this->createMock(RequestInterface::class);

        /** @var PaymentData $paymentData */
        $paymentData = $this->createMock(PaymentData::class);

        /** @var GroupRepositoryInterface $groupRepository */
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        /** @var Calculation $calculator */
        $calculator = $this->createMock(Calculation::class);

        /** @var PriceCurrencyInterface $priceCurrency */
        $priceCurrency = $this->createMock(PriceCurrencyInterface::class);

        /** @var OrderRepositoryInterface $mageOrderRepo */
        $mageOrderRepo = $this->createMock(OrderRepositoryInterface::class);

        /** @var Logger $logger */
        $logger = $this->createMock(Logger::class);

        $this->captureCommand = new Capture(
            $clientRequestService,
            $scopeConfigInterface,
            $requestInterface,
            $paymentData,
            $groupRepository,
            $calculator,
            $priceCurrency,
            $mageOrderRepo,
            $logger,
            []
        );
    }

    public function testDoesNotRemoveAnyValidCharacter()
    {
        $stringWithoutInvalidCharacter = "0,00 kr";
        $stringExpected = "0,00 kr";

        $stringWithoutInvalidCharacter = $this->captureCommand->removeInvalidCharacters($stringWithoutInvalidCharacter);

        $this->assertEquals($stringExpected, $stringWithoutInvalidCharacter);
    }

    public function testRemovesNonBreakingSpace()
    {
        $stringWithInvalidCharacter = utf8_encode("0,00\xa0kr");
        $stringExpected = "0,00 kr";

        $stringWithInvalidCharacter = $this->captureCommand->removeInvalidCharacters($stringWithInvalidCharacter);

        $this->assertEquals($stringExpected, $stringWithInvalidCharacter);
    }

    public function testRemovesNullCharacter()
    {
        $stringWithInvalidCharacter = utf8_encode("0,00 \x00kr");
        $stringExpected = "0,00 kr";

        $stringWithInvalidCharacter = $this->captureCommand->removeInvalidCharacters($stringWithInvalidCharacter);

        $this->assertEquals($stringExpected, $stringWithInvalidCharacter);
    }

    public function testRemovesBackspaceCharacter()
    {
        $stringWithInvalidCharacter = utf8_encode("0,00 \x08kr");
        $stringExpected = "0,00 kr";

        $stringWithInvalidCharacter = $this->captureCommand->removeInvalidCharacters($stringWithInvalidCharacter);

        $this->assertEquals($stringExpected, $stringWithInvalidCharacter);
    }

    public function testRemovesHorizontalTabCharacter()
    {
        $stringWithInvalidCharacter = utf8_encode("0,00 \x09kr");
        $stringExpected = "0,00 kr";

        $stringWithInvalidCharacter = $this->captureCommand->removeInvalidCharacters($stringWithInvalidCharacter);

        $this->assertEquals($stringExpected, $stringWithInvalidCharacter);
    }

    public function testRemovesVerticalTabCharacter()
    {
        $stringWithInvalidCharacter = utf8_encode("0,00 \x0Bkr");
        $stringExpected = "0,00 kr";

        $stringWithInvalidCharacter = $this->captureCommand->removeInvalidCharacters($stringWithInvalidCharacter);

        $this->assertEquals($stringExpected, $stringWithInvalidCharacter);
    }

    public function testRemovesDeleteCharacter()
    {
        $stringWithInvalidCharacter = utf8_encode("0,00 \x7Fkr");
        $stringExpected = "0,00 kr";

        $stringWithInvalidCharacter = $this->captureCommand->removeInvalidCharacters($stringWithInvalidCharacter);

        $this->assertEquals($stringExpected, $stringWithInvalidCharacter);
    }
}
