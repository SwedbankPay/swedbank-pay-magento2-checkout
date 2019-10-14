<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\View\Result\PageFactory;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Model\ConsumerSession;

class OnConsumerIdentified extends Action
{
    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var JsonFactory $resultJsonFactory */
    protected $resultJsonFactory;

    /** @var ConsumerSession $consumerSession */
    protected $consumerSession;

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
     * @param ConsumerSession $consumerSession
     * @param EventManager $eventManager
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        ConsumerSession $consumerSession,
        EventManager $eventManager,
        Logger $logger,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->consumerSession = $consumerSession;
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

        $this->eventManager->dispatch('swedbank_pay_checkout_before_consumer_identified', (array)$requestBody);

        try {
            $actionType = $requestBody->actionType;
            switch ($actionType) {
                case 'OnConsumerIdentified':
//                    $this->consumerSession->isIdentified(true);
//                    $this->consumerSession->setConsumerProfileRef($requestBody->consumerProfileRef);
//                    $this->consumerSession->setActionType($actionType);
                    $this->eventManager->dispatch(
                        'swedbank_pay_checkout_identified',
                        ['consumerProfileRef' => $requestBody->consumerProfileRef]
                    );
                    break;
                default:
//                    $this->consumerSession->setCheckinUrl($requestBody->url);
//                    $this->consumerSession->setActionType($actionType);
                    $this->eventManager->dispatch(
                        'swedbank_pay_checkout_complete',
                        ['url' => $requestBody->url]
                    );
                    break;
            }
        } catch (\Exception $exception) {
            $result = $this->resultJsonFactory->create();
            $result->setData(['data' => 'Wrong request body']);
            $result->setHttpResponseCode(400);
            $this->logger->Error('Wrong request body passed to OnConsumerIdentifiedController', (array)$requestBody);
            return $result;
        }

        $result = $this->resultJsonFactory->create();
        $result->setData(['data' => 'Status changed']);
        return $result;
    }
}
