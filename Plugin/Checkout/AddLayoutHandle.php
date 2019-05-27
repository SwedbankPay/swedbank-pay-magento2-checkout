<?php

namespace PayEx\Checkout\Plugin\Checkout;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\Result\Page;
use PayEx\Checkout\Helper\Config;

/**
 * Add layout handle class.
 */
class AddLayoutHandle
{
    /**
     * @var HttpRequest $request
     */
    protected $request;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * AddLayoutHandle constructor.
     * @param HttpRequest $requestInterface
     * @param Config $config
     */
    public function __construct(
        HttpRequest $requestInterface,
        Config $config
    ) {
        $this->request = $requestInterface;
        $this->config = $config;
    }

    public function afterAddDefaultHandle(Page $subject)
    {
        $modulename = $this->request->getModuleName();
        $fullActionName = $modulename .
                            '_' . $this->request->getControllerName() .
                            '_' . $this->request->getActionName();

        if ($fullActionName == 'checkout_index_index' && $this->config->isActive()) {
            $subject->addHandle('payex_checkout_index');
        }

        return $subject;
    }
}
