<?php

namespace SwedbankPay\Checkout\Gateway\Command;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use SwedbankPay\Api\Client\Exception;
use SwedbankPay\Api\Service\Data\ResponseInterface;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\Transaction;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\TransactionObject;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Model\Service as ClientRequestService;
use SwedbankPay\Core\Exception\SwedbankPayException;
use SwedbankPay\Core\Logger\Logger;

use Magento\Sales\Model\Order as MageOrder;
use Magento\Payment\Gateway\Command;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\App\RequestInterface;
use SwedbankPay\Checkout\Helper\PaymentData;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Cancel extends AbstractCommand
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
     * @var OrderRepositoryInterface
     */
    protected $mageOrderRepo;

    /**
     * Cancel constructor.
     *
     * @param RequestInterface $request
     * @param PaymentData $paymentData
     * @param ClientRequestService $requestService
     * @param OrderRepositoryInterface $mageOrderRepo
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        RequestInterface $request,
        PaymentData $paymentData,
        ClientRequestService $requestService,
        OrderRepositoryInterface $mageOrderRepo,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct(
            $requestService,
            $logger,
            $data
        );

        $this->request = $request;
        $this->paymentData = $paymentData;
        $this->mageOrderRepo = $mageOrderRepo;
    }

    /**
     * Cancel command
     *
     * @param array $commandSubject
     *
     * @return null|Command\ResultInterface
     * @throws NoSuchEntityException
     * @throws SwedbankPayException
     * @throws ServiceException
     * @throws Exception
     */
    public function execute(array $commandSubject)
    {
        /** @var InfoInterface|object $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var MageOrder $order */
        $order = $payment->getOrder();

        $paymentOrder = $this->paymentData->getByOrder($order);

        $amount = round(
            $paymentOrder->getRemainingCancellationAmount() / 100,
            PriceCurrencyInterface::DEFAULT_PRECISION
        );

        $this->checkRemainingAmount('cancel', $amount, $order, $paymentOrder);

        $transaction = new Transaction();
        $transaction->setDescription("Cancelling the authorized payment")
            ->setPayeeReference($this->generateRandomString(30));

        $transactionObject = new TransactionObject();
        $transactionObject->setTransaction($transaction);

        $cancelRequest = $this->getRequestService(
            'Paymentorder/Transaction',
            'TransactionCancel',
            $transactionObject
        );
        $cancelRequest->setPaymentOrderId($paymentOrder->getPaymentIdPath());

        /** @var ResponseInterface $cancelResponse */
        $cancelResponse = $cancelRequest->send();

        $this->checkResponseResource('cancel', $cancelResponse->getResponseResource(), $order, $paymentOrder);

        $cancelResponseData = $cancelResponse->getResponseData();

        $transactionResult = $this->getTransactionResult('cancel', $cancelResponseData, $order, $paymentOrder);

        if ($transactionResult != 'complete') {
            $order->setStatus('swedbank_pay_pending');
            $this->mageOrderRepo->save($order);
            return null;
        }

        $this->paymentData->updateRemainingAmounts('cancel', $amount, $paymentOrder);

        return null;
    }
}
