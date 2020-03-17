<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Checkout\Model\ConsumerSession;
use SwedbankPay\Core\Model\Service;
use SwedbankPay\Core\Logger\Logger;

/**
 * Class OnBillingDetailsAvailable
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OnBillingDetailsAvailable extends Action
{
    const COOKIE_DURATION = 86400; // One day (86400 seconds)

    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var JsonFactory $resultJsonFactory */
    protected $resultJsonFactory;

    /** @var Session|object $customerSession */
    protected $customerSession;

    /** @var ConsumerSession $consumerSession */
    protected $consumerSession;

    /** @var EventManager $eventManager  */
    protected $eventManager;

    /** @var Logger $logger */
    protected $logger;

    /** @var Service $service */
    protected $service;

    /** @var Config $config */
    protected $config;

    /** @var CookieManagerInterface $cookieManager */
    protected $cookieManager;

    /** @var CookieMetadataFactory $cookieMetadataFactory */
    protected $cookieMetadataFactory;

    /**
     * OnConsumerIdentifiedController constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param Session $customerSession
     * @param ConsumerSession $consumerSession
     * @param EventManager $eventManager
     * @param Config $config
     * @param Logger $logger
     * @param Service $service
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        Session $customerSession,
        ConsumerSession $consumerSession,
        EventManager $eventManager,
        Config $config,
        Logger $logger,
        Service $service,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->service = $service;
        $this->config = $config;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->consumerSession = $consumerSession;
        $this->eventManager = $eventManager;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
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

        $this->eventManager->dispatch('swedbank_pay_checkout_before_billing_details_available', (array) $requestBody);

        try {
            $url = $requestBody->url;

            /** @var \SwedbankPay\Api\Service\Data\RequestInterface $session */
            $session = $this->service->init('Consumer', 'GetBillingDetails');
            $session->setRequestMethod('GET');
            $session->setRequestEndpoint($url);
            $response = $session->send()->getResponseData();

            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(self::COOKIE_DURATION)
                ->setPath($this->consumerSession->getCookiePath())
                ->setDomain($this->consumerSession->getCookieDomain());

            $this->cookieManager
                ->setPublicCookie('billingDetails', json_encode($response), $metadata);

            $result = $this->resultJsonFactory->create();
            $result->setData(['data' => $response]);
            $result->setHttpResponseCode(200);
        } catch (\Exception $exception) {
            $this->logger->Error($exception->getMessage());
            $result = $this->resultJsonFactory->create();
            $result->setHttpResponseCode(400);
        }

        return $result;
    }
}
