<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Checkout\Model\ConsumerSession;
use SwedbankPay\Core\Logger\Logger;

class OnConsumerReidentified extends Action
{

    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var JsonFactory $resultJsonFactory */
    protected $resultJsonFactory;

    /** @var EventManager $eventManager  */
    protected $eventManager;

    /** @var Logger $logger */
    protected $logger;

    /** @var Config $config */
    protected $config;

    /** @var ConsumerSession $consumerSession */
    protected $consumerSession;

    /** @var CookieManagerInterface $cookieManager */
    protected $cookieManager;

    /**
     * OnConsumerIdentifiedController constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param EventManager $eventManager
     * @param Logger $logger
     * @param Config $config
     * @param ConsumerSession $consumerSession
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        EventManager $eventManager,
        Logger $logger,
        Config $config,
        ConsumerSession $consumerSession,
        CookieManagerInterface $cookieManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->config = $config;
        $this->consumerSession = $consumerSession;
        $this->cookieManager = $cookieManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->config->isActive()) {
            $result->setData(['data' => 'Forbidden! Module is not enabled']);
            $result->setHttpResponseCode(403);

            return $result;
        }

        $result->setData(
            [
                'billing_details' => json_decode($this->cookieManager->getCookie('billingDetails')),
                'shipping_details' => json_decode($this->cookieManager->getCookie('shippingDetails'))
            ]
        );
        $result->setHttpResponseCode(200);
        return $result;
    }
}
