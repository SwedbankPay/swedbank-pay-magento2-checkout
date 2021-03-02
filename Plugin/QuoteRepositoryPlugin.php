<?php

namespace SwedbankPay\Checkout\Plugin;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote as MageQuote;
use Magento\Quote\Model\QuoteRepository as MagentoQuoteRepository;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Checkout\Helper\Paymentorder;
use SwedbankPay\Checkout\Model\Quote as SwedbankPayQuote;
use SwedbankPay\Checkout\Model\ResourceModel\OrderRepository;
use SwedbankPay\Checkout\Model\ResourceModel\QuoteRepository;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Core\Model\Service;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteRepositoryPlugin
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var Paymentorder
     */
    protected $paymentorder;

    /**
     * @var Service
     */
    protected $service;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * QuoteRepositoryPlugin constructor.
     * @param QuoteRepository $quoteRepository
     * @param OrderRepository $orderRepository
     * @param Paymentorder $paymentorder
     * @param Service $service
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        Paymentorder $paymentorder,
        Service $service,
        Config $config,
        Logger $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentorder = $paymentorder;
        $this->service = $service;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param MagentoQuoteRepository $subject
     * @param callable $proceed
     * @param MageQuote $quote
     * @return callable|null
     * @throws AlreadyExistsException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        MagentoQuoteRepository $subject,
        callable $proceed,
        MageQuote $quote
    ) {
        if (!$this->config->isActive()) {
            return $proceed($quote);
        }

        $returnValue = $proceed($quote);

        try {
            $swedbankPayQuote = $this->quoteRepository->getByQuoteId($quote->getId());

            try {
                $this->updatePaymentorder($quote, $swedbankPayQuote);
            } catch (Exception $e) {
                $this->logger->error(sprintf('UpdateOrder operation failed. Exception: %s', $e->getMessage()));

                return null;
            }

            $totalAmount = (int) round($quote->getGrandTotal() * 100);

            $swedbankPayQuote->setIsUpdated(1);
            $swedbankPayQuote->setAmount($totalAmount);
            $swedbankPayQuote->setVatAmount($this->paymentorder->getPaymentorderVatAmount($quote));
            $swedbankPayQuote->setRemainingCapturingAmount($totalAmount);
            $swedbankPayQuote->setRemainingCancellationAmount($totalAmount);
            $swedbankPayQuote->setRemainingReversalAmount(0);

            $this->quoteRepository->save($swedbankPayQuote);
        } catch (NoSuchEntityException $e) {
            $this->logger->debug(sprintf(
                'No SwedbankPay Quote record has been created yet with ID # %s',
                $quote->getId()
            ));

            $this->logger->debug(sprintf('SwedbankPay Quote update skipped!'));
        }

        return $returnValue;
    }

    /**
     * @param MageQuote $mageQuote
     * @param SwedbankPayQuote $swedbankPayQuote
     * @throws Exception
     */
    public function updatePaymentorder(MageQuote $mageQuote, SwedbankPayQuote $swedbankPayQuote)
    {
        $quoteTotal = (int) round($mageQuote->getGrandTotal() * 100);

        $this->logger->debug('UpdateOrder request is called');
        $this->logger->debug('SwedbankPayQuote Total: ' . $swedbankPayQuote->getAmount());
        $this->logger->debug('Quote Grand Total: ' . $quoteTotal);

        if ($swedbankPayQuote->getAmount() == $quoteTotal) {
            $this->logger->debug('UpdateOrder operation is skipped as the amount is unchanged');
            return;
        }

        if ($this->isOrderCreated($swedbankPayQuote->getPaymentOrderId())) {
            $this->logger->debug('UpdateOrder operation is skipped as Order is created');
            return;
        }

        $paymentOrderObject = $this->paymentorder->createPaymentorderUpdateObject($mageQuote);

        $updateRequest = $this->service->init('Paymentorder', 'updateOrder', $paymentOrderObject);
        $updateRequest->setPaymentOrderId($swedbankPayQuote->getPaymentIdPath());
        $updateRequest->send();
    }

    /**
     * @param string $paymentOrderId
     * @return bool
     */
    public function isOrderCreated(string $paymentOrderId)
    {
        try {
            $this->orderRepository->getByPaymentOrderId($paymentOrderId);

            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
}
