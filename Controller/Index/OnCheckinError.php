<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\View\Result\PageFactory;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Core\Logger\Logger;

class OnCheckinError extends Action
{
    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var JsonFactory $resultJsonFactory */
    protected $resultJsonFactory;

    /** @var Session|object $customerSession */
    protected $customerSession;

    /** @var EventManager $eventManager  */
    protected $eventManager;

    /** @var Logger $logger */
    protected $logger;

    /** @var Config $config */
    protected $config;

    /**
     * OnConsumerIdentifiedController constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param Session $customerSession
     * @param EventManager $eventManager
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        Session $customerSession,
        EventManager $eventManager,
        Logger $logger,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        if (!$this->config->isActive()) {
            $result = $this->resultJsonFactory->create();
            $result->setData(['data' => 'Forbidden! Module is not enabled']);
            $result->setHttpResponseCode(403);

            return $result;
        }

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $requestBody = json_decode($request->getContent());

        $this->eventManager->dispatch('swedbank_pay_checkout_before_error', (array) $requestBody);

        $details = $requestBody->details;

        $this->logger->Error($details, $requestBody);

        $result = $this->resultJsonFactory->create();
        $result->setData(['data' => $details]);
        $result->setHttpResponseCode(400);
        return $result;
    }
}
