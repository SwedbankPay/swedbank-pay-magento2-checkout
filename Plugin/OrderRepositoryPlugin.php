<?php

namespace SwedbankPay\Checkout\Plugin;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository as MagentoOrderRepository;
use SwedbankPay\Core\Model\Service;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Checkout\Model\OrderFactory;
use SwedbankPay\Checkout\Model\ResourceModel\OrderRepository;
use SwedbankPay\Checkout\Model\ResourceModel\QuoteRepository;

class OrderRepositoryPlugin
{
    /**
     * @var QuoteRepository $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var OrderRepository $orderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderFactory $orderFactory
     */
    protected $orderFactory;

    /**
     * @var Service $service
     */
    protected $service;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * OrderRepositoryPlugin constructor.
     * @param QuoteRepository $quoteRepository
     * @param OrderRepository $orderRepository
     * @param OrderFactory $orderFactory
     * @param Service $service
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        OrderFactory $orderFactory,
        Service $service,
        Config $config,
        Logger $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->service = $service;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param MagentoOrderRepository $subject
     * @param OrderInterface $mageOrder
     * @return OrderInterface
     * @throws NoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function afterSave(
        /** @noinspection PhpUnusedParameterInspection */ MagentoOrderRepository $subject,
        OrderInterface $mageOrder
    ) {
        if (!$this->config->isActive()) {
            return $mageOrder;
        }

        $swedbankPayQuote = $this->quoteRepository->getByQuoteId($mageOrder->getQuoteId());

        try {
            if ($this->orderRepository->getByOrderId($mageOrder->getEntityId())) {
                return $mageOrder;
            }
        } catch (Exception $e) {
        }

        /** @var \SwedbankPay\Checkout\Model\Order $swedbankPayOrder */
        $swedbankPayOrder = $this->orderFactory->create();

        $swedbankPayOrder->setState($swedbankPayQuote->getState());
        $swedbankPayOrder->setPaymentOrderId($swedbankPayQuote->getPaymentOrderId());
        $swedbankPayOrder->setCreatedAt($swedbankPayQuote->getCreatedAt());
        $swedbankPayOrder->setUpdatedAt($swedbankPayQuote->getUpdatedAt());
        $swedbankPayOrder->setOperation($swedbankPayQuote->getOperation());
        $swedbankPayOrder->setCurrency($swedbankPayQuote->getCurrency());
        $swedbankPayOrder->setAmount($swedbankPayQuote->getAmount());
        $swedbankPayOrder->setVatAmount($swedbankPayQuote->getVatAmount());
        $swedbankPayOrder->setRemainingCapturingAmount($swedbankPayQuote->getRemainingCapturingAmount());
        $swedbankPayOrder->setRemainingCancellationAmount($swedbankPayQuote->getRemainingCancellationAmount());
        $swedbankPayOrder->setRemainingReversalAmount($swedbankPayQuote->getRemainingReversalAmount());
        $swedbankPayOrder->setDescription($swedbankPayQuote->getDescription());
        $swedbankPayOrder->setInitiatingSystemUserAgent($_SERVER['HTTP_USER_AGENT']);
        $swedbankPayOrder->setOrderId($mageOrder->getEntityId());

        try {
            $this->orderRepository->save($swedbankPayOrder);
        } catch (AlreadyExistsException $e) {
            $this->logger->Error('OrderRepositoryPlugin - AlreadyExistsException: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->Error('OrderRepositoryPlugin - Exception: ' . $e->getMessage());
        }

        return $mageOrder;
    }
}
