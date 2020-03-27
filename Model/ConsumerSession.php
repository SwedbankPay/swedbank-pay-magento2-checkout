<?php

namespace SwedbankPay\Checkout\Model;

class ConsumerSession extends \Magento\Framework\Session\SessionManager
{
    const IS_INITIATED = 'is_initiated';
    const IS_IDENTIFIED = 'is_identified';
    const VIEW_OPERATION = 'view_operation';
    const BILLING_DETAILS = 'billing_details';
    const SHIPPING_DETAILS = 'shipping_details';
    const ACTION_TYPE = 'action_type';
    const CONSUMER_PROFILE_REF = 'customer_profile_ref';
    const CHECKIN_URL = 'checkin_url';

    /**
     * @param bool|null $flag
     * @return bool|ConsumerSession
     */
    public function isInitiated($flag = null)
    {
        if ($flag) {
            $this->storage->setData(self::IS_INITIATED, $flag);

            return $this;
        }

        return (bool) $this->getData(self::IS_INITIATED);
    }

    /**
     * @param bool|null $flag
     * @return bool|ConsumerSession
     */
    public function isIdentified($flag = null)
    {
        if ($flag) {
            $this->storage->setData(self::IS_IDENTIFIED, $flag);

            return $this;
        }

        return (bool) $this->getData(self::IS_IDENTIFIED);
    }

    /**
     * @return mixed
     */
    public function getViewOperation()
    {
        return $this->getData(self::VIEW_OPERATION);
    }

    /**
     * @param $viewOperation
     */
    public function setViewOperation($viewOperation)
    {
        $this->storage->setData(self::VIEW_OPERATION, $viewOperation);
    }

    /**
     * @return mixed
     */
    public function getBillingDetails()
    {
        return $this->getData(self::BILLING_DETAILS);
    }

    /**
     * @param $billingDetails
     */
    public function setBillingDetails($billingDetails)
    {
        $this->storage->setData(self::BILLING_DETAILS, $billingDetails);
    }

    /**
     * @return mixed
     */
    public function getShippingDetails()
    {
        return $this->getData(self::SHIPPING_DETAILS);
    }

    /**
     * @param $shippingDetails
     */
    public function setShippingDetails($shippingDetails)
    {
        $this->storage->setData(self::SHIPPING_DETAILS, $shippingDetails);
    }

    /**
     * @return mixed
     */
    public function getConsumerProfileRef()
    {
        return $this->getData(self::CONSUMER_PROFILE_REF);
    }

    /**
     * @param $consumerProfileRef
     */
    public function setConsumerProfileRef($consumerProfileRef)
    {
        $this->storage->setData(self::CONSUMER_PROFILE_REF, $consumerProfileRef);
    }
    /**
     * @return mixed
     */
    public function getCheckinUrl()
    {
        return $this->getData(self::CHECKIN_URL);
    }

    /**
     * @param $checkinUrl
     */
    public function setCheckinUrl($checkinUrl)
    {
        $this->storage->setData(self::CHECKIN_URL, $checkinUrl);
    }
    /**
     * @return mixed
     */
    public function getActionType()
    {
        return $this->getData(self::ACTION_TYPE);
    }

    /**
     * @param $actionType
     */
    public function setActionType($actionType)
    {
        $this->storage->setData(self::ACTION_TYPE, $actionType);
    }
}
