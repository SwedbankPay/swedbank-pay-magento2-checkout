<?php

namespace SwedbankPay\Checkout\Block\Widget;

use Magento\Customer\Model\ResourceModel\AddressRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Widget\Block\BlockInterface;
use SwedbankPay\Api\Service\Consumer\Resource\Request\InitiateConsumerSession as ConsumerSessionResource;
use SwedbankPay\Api\Service\Data\RequestInterface;
use SwedbankPay\Api\Service\Data\ResponseInterface;
use SwedbankPay\Api\Service\Resource\Data\ResponseInterface as ResponseResourceInterface;
use SwedbankPay\Checkout\Helper\Config;
use SwedbankPay\Checkout\Model\ConsumerSession as SwedbankPayConsumerSession;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Core\Model\Service;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CheckinWidget extends Template implements BlockInterface
{
    // phpcs:disable
    protected $_template = 'SwedbankPay_Checkout::widget/checkin-widget.phtml';
    // phpcs:enable

    /**
     * @var SwedbankPayConsumerSession $consumerSession
     */
    protected $consumerSession;

    /**
     * @var CustomerSession|object $customerSession
     */
    protected $customerSession;

    /**
     * @var CustomerRepository $customerRepository
     */
    protected $customerRepository;

    /**
     * @var AddressRepository $addressRepository
     */
    protected $addressRepository;

    /**
     * @var EventManager $eventManager
     */
    protected $eventManager;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var Service $service
     */
    protected $service;

    /**
     * @var Config $config
     */
    protected $config;

    public function __construct(
        Template\Context $context,
        SwedbankPayConsumerSession $consumerSession,
        CustomerSession $customerSession,
        CustomerRepository $customerRepository,
        AddressRepository $addressRepository,
        EventManager $eventManager,
        Service $service,
        Logger $logger,
        Config $config,
        array $data = []
    ) {
        $this->consumerSession = $consumerSession;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->eventManager = $eventManager;
        $this->service = $service;
        $this->logger = $logger;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    public function isActive()
    {
        return $this->config->isActive();
    }

    public function isConsumerIdentified()
    {
        return $this->consumerSession->isIdentified();
    }

    /**
     * Get default country
     *
     * @return string
     */
    public function getDefaultCountry()
    {
        $configPath = 'general/country/default';
        $scope = ScopeInterface::SCOPE_STORE;
        return $this->_scopeConfig->getValue($configPath, $scope);
    }

    /**
     * @param ConsumerSessionResource $consumerSessionData
     * @return ResponseInterface|false
     * @throws ServiceException
     * @throws \SwedbankPay\Api\Client\Exception
     */
    public function initiateConsumerSession(ConsumerSessionResource $consumerSessionData)
    {
        /** @var RequestInterface $consumerSession */
        $consumerSession = $this->service->init('Consumer', 'InitiateConsumerSession', $consumerSessionData);

        /** @var ResponseInterface $response */
        $response = $consumerSession->send();

        if (!($response instanceof ResponseInterface) ||
            !($response->getResponseResource() instanceof ResponseResourceInterface)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->logger->error(sprintf('Invalid InitiateConsumerSession response: %s', print_r($response, true)));
            return false;
        }

        $this->consumerSession->isInitiated(true);

        return $response;
    }

    /**
     * @return string|false
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \SwedbankPay\Api\Client\Exception
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function getCheckinScript()
    {
        if ($this->consumerSession->getViewOperation()) {
            $viewOperation = $this->consumerSession->getViewOperation();
            return $viewOperation['href'];
        }

        $consumerSessionData = new ConsumerSessionResource();
        $consumerSessionData->setConsumerCountryCode($this->getDefaultCountry());

        /** @var ResponseInterface|false $response */
        try {
            $response = $this->initiateConsumerSession($consumerSessionData);
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        if ($response instanceof ResponseInterface) {
            $viewOperation = $response->getOperationByRel('view-consumer-identification');
            $this->consumerSession->setViewOperation($viewOperation);
            return $viewOperation['href'];
        }

        return false;
    }

    // phpcs:disable
    protected function _toHtml()
    {
    // phpcs:enable
        if (!$this->isActive()) {
            return '';
        }

        return parent::_toHtml();
    }
}
