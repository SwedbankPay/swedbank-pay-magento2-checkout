<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Closure;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\Manager as EventManager;

use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Api\Data\PaymentActionInterface;
use SwedbankPay\Checkout\Helper\Config as ConfigHelper;

/**
 * Abstract class PaymentActionAbstract
 */
abstract class PaymentActionAbstract extends Action implements PaymentActionInterface
{
    const EVENT_NAME_PREFIX = 'swedbank_pay_checkout_';

    /** @var JsonFactory $resultJsonFactory */
    protected $resultJsonFactory;

    /** @var EventManager $eventManager  */
    protected $eventManager;

    /** @var ConfigHelper $configHelper */
    protected $configHelper;

    /** @var Logger $logger */
    protected $logger;

    /** @var array $eventData */
    protected $eventData = [];

    /**
     * OnPaymentCancel constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param EventManager $eventManager
     * @param ConfigHelper $configHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        EventManager $eventManager,
        ConfigHelper $configHelper,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eventManager = $eventManager;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * @param string $eventName
     * @return $this
     */
    public function setEventName($eventName)
    {
        $this->eventData[self::EVENT_NAME] = $eventName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return isset($this->eventData[self::EVENT_NAME]) ? $this->eventData[self::EVENT_NAME] : '';
    }

    /**
     * @param string $type
     * @return string
     */
    public function getFullEventName($type = '')
    {
        $eventName = $this->getEventName();

        if (strpos($eventName, self::EVENT_NAME_PREFIX) !== false) {
            $eventName = str_replace(self::EVENT_NAME_PREFIX, '', $eventName);
        }

        if (strpos($eventName, 'before_') !== false) {
            $eventName = str_replace('before_', '', $eventName);
        }

        if (strpos($eventName, 'after_') !== false) {
            $eventName = str_replace('after_', '', $eventName);
        }

        if ($type == 'before' || $type == 'after') {
            $eventName = $type . '_' . $eventName;
        }

        return self::EVENT_NAME_PREFIX . '_' . $eventName;
    }

    /**
     * @param array $eventArgs
     * @return $this
     */
    public function setEventArgs($eventArgs)
    {
        $eventArgs = (array)$eventArgs;

        if ([] === $eventArgs) {
            return $this;
        }

        if (array_keys($eventArgs) === range(0, count($eventArgs) - 1)) {
            $eventArgs = array_flip($eventArgs);
        }

        $this->eventData[self::EVENT_ARGS] = $eventArgs;

        return $this;
    }

    /**
     * @return array
     */
    public function getEventArgs()
    {
        return isset($this->eventData[self::EVENT_ARGS]) ? $this->eventData[self::EVENT_ARGS] : [];
    }

    /**
     * @param callable $eventMethod
     * @return $this
     */
    public function setEventMethod($eventMethod)
    {
        $this->eventData[self::EVENT_METHOD] = $eventMethod;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getEventMethod()
    {
        return isset($this->eventData[self::EVENT_METHOD]) ? $this->eventData[self::EVENT_METHOD] : null;
    }

    /**
     * @return string
     */
    protected function getEventMethodString()
    {
        $eventMethodString = '';

        if (is_callable($this->getEventMethod())) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            switch (gettype($this->getEventMethod())) {
                case 'string':
                    $eventMethodString = $this->getEventMethod();
                    break;
                case 'array':
                    list($object, $method) = $this->getEventMethod();
                    $eventMethodString = get_class($object) . '->' . $method;
                    break;
                case 'object':
                    if ($this->getEventMethod() instanceof Closure) {
                        $eventMethodString = 'anonymous function';
                        break;
                    }
                    $eventMethodString = get_class($this->getEventMethod());
                    break;
            }
        }

        return $eventMethodString;
    }

    /**
     * Sets JSON result
     *
     * @param string $message
     * @param int $httpCode
     * @return JsonResult
     */
    protected function setResult($message = '', $httpCode = 200)
    {
        $result = $this->resultJsonFactory->create();
        $result->setData(array_merge($this->getEventArgs(), ['result' => $message]));
        $result->setHttpResponseCode($httpCode);

        return $result;
    }

    /**
     * @return ResponseInterface|JsonResult|ResultInterface
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        if (!$this->configHelper->isActive()) {
            $this->logger->error(
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                basename(get_class($this)) . ' trigger error: Module is not active'
            );
            return $this->setResult(
                __('Not Found: The required SwedbankPay Checkout resources does not seem to be available'),
                404
            );
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $requestBody = json_decode($this->getRequest()->getContent());

        $this->eventManager->dispatch(
            $this->getFullEventName('before'),
            ['requestBody' => $requestBody]
        );

        try {
            $eventArgs = [];
            foreach (array_keys($this->getEventArgs()) as $arg) {
                $eventArgs[$arg] = $requestBody->{$arg};
            }
            $this->setEventArgs($eventArgs);
            $this->logger->debug(
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                basename(get_class($this)) . ' triggered',
                ['eventArgs' => $eventArgs, 'requestBody' => (array)$requestBody]
            );
        } catch (Exception $exception) {
            $this->logger->error(
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                basename(get_class($this)) . ' trigger error: Missing arguments in request body',
                [
                    'eventArgs' => (array) $this->getEventArgs(),
                    'requestBody' => (array) $requestBody
                ]
            );
            return $this->setResult(__('Bad Request: Missing argument(s)'), 400);
        }

        if (is_callable($this->getEventMethod())) {
            $this->logger->debug(
                'Calling event method: ' . $this->getEventMethodString(),
                ['methodArgs' => $eventArgs]
            );

            $returnValue = null;
            $errorMessage = '';

            try {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $result = $returnValue = call_user_func_array($this->getEventMethod(), $eventArgs);
                if (is_scalar($result) && !is_string($result)) {
                    $result = $result ? 'success' : 'error';
                }
            } catch (Exception $exception) {
                $result = 'error';
                $errorMessage = $exception->getMessage();
            }

            if ($result == 'error') {
                $this->logger->error(
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    basename(get_class($this)) . ' trigger error: Failed to execute event method',
                    [
                        'eventMethod' => $this->getEventMethodString(),
                        'eventArgs' => $this->getEventArgs(),
                        'errorMessage' => $errorMessage
                    ]
                );
                return $this->setResult(__('Server error: Failed to execute event method'), 500);
            }

            $resultData = $returnValue ?: $result;
            $result = $this->setResult($resultData);

            $this->logger->debug($this->getEventMethodString() . ' result: ' . var_export($resultData, true));
        }

        if (!isset($result)) {
            $result = $this->setResult('success');
        }

        $this->eventManager->dispatch(
            $this->getFullEventName('after'),
            ['requestBody' => $requestBody, 'result' => $result]
        );

        return $result;
    }
}
