<?php

namespace SwedbankPay\Checkout\Gateway\Command;

use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use PayEx\Api\Client\Exception;
use PayEx\Api\Service\Data\RequestInterface;
use PayEx\Api\Service\Data\ResponseInterface;
use PayEx\Api\Service\Paymentorder\Request\GetPaymentorder as GetPaymentorderRequest;
use PayEx\Api\Service\Resource\Collection\Item\OperationsItem;
use SwedbankPay\Core\Exception\ServiceException;
use PayEx\Framework\AbstractDataTransferObject;
use PayEx\Api\Service\Resource\Data\ResponseInterface as ResponseResourceInterface;
use SwedbankPay\Core\Model\Service as ClientRequestService;
use SwedbankPay\Core\Exception\SwedbankPayException;
use SwedbankPay\Core\Logger\Logger;
use PayEx\Framework\Data\DataObjectCollectionInterface;
use SwedbankPay\Checkout\Api\Data\OrderInterface as PaymentOrderInterface;
use SwedbankPay\Checkout\Api\Data\QuoteInterface as PaymentQuoteInterface;
use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Class AbstractCommand
 *
 * @package SwedbankPay\Checkout\Gateway\Command
 */
abstract class AbstractCommand extends DataObject implements CommandInterface
{
    const GATEWAY_COMMAND_CAPTURE = 'capture';
    const GATEWAY_COMMAND_CANCEL = 'cancel';
    const GATEWAY_COMMAND_REFUND = 'refund';

    const TRANSACTION_ACTION_CAPTURE = 'capture';
    const TRANSACTION_ACTION_CANCEL = 'cancellation';
    const TRANSACTION_ACTION_REFUND = 'reversal';

    /**
     * @var ClientRequestService
     */
    protected $requestService;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $cmdTransActionMap = [];

    /**
     * AbstractCommand constructor.
     *
     * @param ClientRequestService $requestService
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        ClientRequestService $requestService,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($data);
        $this->requestService = $requestService;
        $this->logger = $logger;

        $this->cmdTransActionMap = [
            self::GATEWAY_COMMAND_CAPTURE => self::TRANSACTION_ACTION_CAPTURE,
            self::GATEWAY_COMMAND_CANCEL => self::TRANSACTION_ACTION_CANCEL,
            self::GATEWAY_COMMAND_REFUND => self::TRANSACTION_ACTION_REFUND
        ];
    }

    /**
     * AbstractCommand command
     *
     * @param array $commandSubject
     *
     * @return null|Command\ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    abstract public function execute(array $commandSubject);

    /**
     * Get available SwedbankPay payment order operations
     *
     * @param string $paymentOrderId
     *
     * @return DataObjectCollectionInterface
     * @throws ServiceException
     * @throws Exception
     */
    protected function getSwedbankPayPaymentOperations($paymentOrderId)
    {
        /** @var GetPaymentorderRequest $requestService */
        $requestService = $this->requestService->init('paymentorder', 'getPaymentorder');
        $requestService->setRequestEndpointVars($paymentOrderId);

        /** @var ResponseInterface $responseService */
        $responseService = $requestService->send();

        return $responseService->getResponseResource()->getOperations();
    }

    /**
     * Get SwedbankPay payment order resource id
     *
     * @param string $paymentOrderId
     *
     * @return string
     * @throws ServiceException
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.IfStatementAssignments)
     */
    protected function getSwedbankPayPaymentResourceId($paymentOrderId)
    {
        $paymentOperations = $this->getSwedbankPayPaymentOperations($paymentOrderId);
        $paymentResourceId = $paymentOrderId;

        /** @var OperationsItem $operation */
        foreach ($paymentOperations as $operation) {
            if ($pos = strpos('/psp/paymentorders/', $operation->getHref()) !== false) {
                $paymentResourceId = substr($operation->getHref(), $pos + count($operation->getHref()));
                break;
            }
        }

        return $paymentResourceId;
    }

    /**
     * Get client request service class
     *
     * @param string $service
     * @param string $operation
     * @param AbstractDataTransferObject|null $dataTransferObject
     * @return RequestInterface|string
     * @throws ServiceException
     */
    protected function getRequestService($service, $operation, $dataTransferObject = null)
    {
        return $this->requestService->init($service, $operation, $dataTransferObject);
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
     * @param string $command
     * @param string|int|float $amount
     * @param OrderInterface $mageOrder
     * @param PaymentOrderInterface|PaymentQuoteInterface $swedbankPayOrder
     * @throws SwedbankPayException
     */
    protected function checkRemainingAmount($command, $amount, $mageOrder, $swedbankPayOrder)
    {
        $getMethod = 'getRemaining' . ucfirst($this->cmdTransActionMap[$command]) . 'Amount';
        $remainingAmount = (int)call_user_func([$swedbankPayOrder, $getMethod]);

        if ($remainingAmount >= ($amount * 100)) {
            return;
        }

        $this->logger->error(
            sprintf(
                "Failed to %s order %s with SwedbankPay payment order id %s:" .
                "The amount of %s exceeds the remaining %s.",
                $command,
                $mageOrder->getEntityId(),
                $swedbankPayOrder->getPaymentOrderId(),
                $amount,
                $remainingAmount
            )
        );

        throw new SwedbankPayException(
            new Phrase(
                sprintf(
                    "SwedbankPay %s Error: The amount of %s exceeds the remaining %s.",
                    ucfirst($command),
                    $amount,
                    $remainingAmount
                )
            )
        );
    }

    /**
     * @param string $command
     * @param ResponseResourceInterface $responseResource
     * @param OrderInterface $mageOrder
     * @param PaymentOrderInterface|PaymentQuoteInterface $swedbankPayOrder
     * @throws SwedbankPayException
     */
    protected function checkResponseResource($command, $responseResource, $mageOrder, $swedbankPayOrder)
    {
        if ($responseResource instanceof ResponseResourceInterface) {
            return;
        }

        $this->logger->error(
            sprintf(
                "Failed to %s order %s with SwedbankPay payment order id %s, response resource:\n%s",
                $command,
                $mageOrder->getEntityId(),
                $swedbankPayOrder->getPaymentOrderId(),
                print_r($responseResource, true)
            )
        );

        throw new SwedbankPayException(
            new Phrase(
                sprintf(
                    "SwedbankPay %s Error: Failed to parse response for SwedbankPay payment order %s.",
                    ucfirst($command),
                    $swedbankPayOrder->getPaymentOrderId()
                )
            )
        );
    }

    /**
     * @param string $command
     * @param array $responseData
     * @param OrderInterface $mageOrder
     * @param PaymentOrderInterface|PaymentQuoteInterface $swedbankPayOrder
     * @return string
     * @throws SwedbankPayException
     */
    protected function getTransactionResult($command, $responseData, $mageOrder, $swedbankPayOrder)
    {
        $state = isset($responseData[$this->cmdTransActionMap[$command]]['transaction']['state']) ?
            $responseData[$this->cmdTransActionMap[$command]]['transaction']['state'] : 'Failed';

        switch ($state) {
            case "Initialized":
            case "AwaitingActivity":
                $status = "pending";
                break;
            case "Completed":
                $status = "complete";
                break;
            case "Failed":
            default:
                $this->logger->error(
                    sprintf(
                        "Failed to %s order %s with SwedbankPay payment order id %s, response data:\n%s",
                        $command,
                        $mageOrder->getEntityId(),
                        $swedbankPayOrder->getPaymentOrderId(),
                        print_r($responseData, true)
                    )
                );
                throw new SwedbankPayException(
                    new Phrase(
                        sprintf(
                            "SwedbankPay %s Error: Failed to %s SwedbankPay payment order %s.",
                            ucfirst($command),
                            $command,
                            $swedbankPayOrder->getPaymentOrderId()
                        )
                    )
                );
                break;
        }

        return $status;
    }
}
