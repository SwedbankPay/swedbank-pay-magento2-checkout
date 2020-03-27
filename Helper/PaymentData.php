<?php

namespace SwedbankPay\Checkout\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface as MagentoOrderInterface;

use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Api\OrderRepositoryInterface as PaymentOrderRepository;
use SwedbankPay\Checkout\Api\QuoteRepositoryInterface as PaymentQuoteRepository;

use SwedbankPay\Checkout\Api\Data\OrderInterface as PaymentOrderInterface;
use SwedbankPay\Checkout\Api\Data\QuoteInterface as PaymentQuoteInterface;

class PaymentData
{
    /**
     * @var PaymentOrderRepository
     */
    protected $paymentOrderRepo;

    /**
     * @var PaymentQuoteRepository
     */
    protected $paymentQuoteRepo;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * PaymentData constructor.
     * @param PaymentOrderRepository $paymentOrderRepo
     * @param PaymentQuoteRepository $paymentQuoteRepo
     * @param Logger $logger
     */
    public function __construct(
        PaymentOrderRepository $paymentOrderRepo,
        PaymentQuoteRepository $paymentQuoteRepo,
        Logger $logger
    ) {
        $this->paymentOrderRepo = $paymentOrderRepo;
        $this->paymentQuoteRepo = $paymentQuoteRepo;
        $this->logger = $logger;
    }

    /**
     * Get SwedbankPay payment data by order
     *
     * @param MagentoOrderInterface|int|string $order
     *
     * @return PaymentOrderInterface|PaymentQuoteInterface|false
     * @throws NoSuchEntityException
     */
    public function getByOrder($order)
    {
        if (is_numeric($order)) {
            return $this->paymentOrderRepo->getByOrderId($order);
        }

        if ($order instanceof MagentoOrderInterface) {
            if ($order->getEntityId()) {
                return $this->paymentOrderRepo->getByOrderId($order->getEntityId());
            }
            return $this->paymentQuoteRepo->getByQuoteId($order->getQuoteId());
        }

        $this->logger->error(
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            sprintf("Unable to find a SwedbankPay payment matching order:\n%s", print_r($order, true))
        );

        throw new NoSuchEntityException(
            new Phrase(sprintf("Unable to find a SwedbankPay payment matching order %s", $order->getIncrementId()))
        );
    }

    /**
     * Get SwedbankPay payment data by payment order id
     *
     * @param string $paymentOrderId
     *
     * @return PaymentOrderInterface|PaymentQuoteInterface|false
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function getByPaymentOrderId($paymentOrderId)
    {
        $paymentData = null;

        try {
            $paymentData = $this->paymentOrderRepo->getByPaymentOrderId($paymentOrderId);
        } catch (NoSuchEntityException $e) {
            $this->logger->debug(sprintf('SwedbankPay Order not found with ID # %s', $paymentOrderId));
        }

        if ($paymentData instanceof PaymentOrderInterface) {
            return $paymentData;
        }

        try {
            $paymentData = $this->paymentQuoteRepo->getByPaymentOrderId($paymentOrderId);
        } catch (NoSuchEntityException $e) {
            $this->logger->debug(sprintf('SwedbankPay Quote not found with ID # %s', $paymentOrderId));
        }

        if ($paymentData instanceof PaymentQuoteInterface) {
            return $paymentData;
        }

        $errorMessage = sprintf("Unable to find a SwedbankPay payment matching Payment ID:\n%s", $paymentOrderId);

        $this->logger->error(
            $errorMessage
        );

        throw new NoSuchEntityException(
            new Phrase($errorMessage)
        );
    }

    /**
     * @param PaymentOrderInterface|PaymentQuoteInterface $payment
     * @return bool
     */
    public function update($payment)
    {
        $this->logger->debug('Saving payment instance: ' . get_class($payment));
        $this->logger->debug('Intent: ' . $payment->getIntent());

        if ($payment instanceof PaymentOrderInterface) {
            $this->logger->debug('Saving!');
            $this->paymentOrderRepo->save($payment);
            return true;
        }

        if ($payment instanceof PaymentQuoteInterface) {
            $this->paymentQuoteRepo->save($payment);
            return true;
        }

        return false;
    }

    /**
     * @param string $command
     * @param string|int|float $amount
     * @param PaymentOrderInterface|PaymentQuoteInterface $order
     */
    public function updateRemainingAmounts($command, $amount, $order)
    {
        switch ($command) {
            case 'capture':
                $order->setRemainingCapturingAmount($order->getRemainingCapturingAmount() - ($amount * 100));
                $order->setRemainingCancellationAmount($order->getRemainingCapturingAmount());
                $order->setRemainingReversalAmount($order->getRemainingReversalAmount() + ($amount * 100));
                break;
            case 'cancel':
                $order->setRemainingCancellationAmount($order->getRemainingCancellationAmount() - ($amount * 100));
                $order->setRemainingCapturingAmount($order->getRemainingCancellationAmount());
                break;
            case 'refund':
                $order->setRemainingReversalAmount($order->getRemainingReversalAmount() - ($amount * 100));
                break;
        }

        $this->paymentOrderRepo->save($order);
    }
}
