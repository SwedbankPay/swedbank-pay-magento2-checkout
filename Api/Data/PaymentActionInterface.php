<?php

namespace SwedbankPay\Checkout\Api\Data;

interface PaymentActionInterface
{
    const EVENT_NAME = 'event_name';
    const EVENT_ARGS = 'event_args';
    const EVENT_METHOD = 'event_method';

    /**
     * @param string $eventName
     * @return $this
     */
    public function setEventName($eventName);

    /**
     * @return string
     */
    public function getEventName();

    /**
     * @param array $eventArgs
     * @return $this
     */
    public function setEventArgs($eventArgs);

    /**
     * @return array
     */
    public function getEventArgs();

    /**
     * @param callable $eventMethod
     * @return $this
     */
    public function setEventMethod($eventMethod);

    /**
     * @return callable|null
     */
    public function getEventMethod();
}
