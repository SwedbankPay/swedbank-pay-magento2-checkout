<?php

namespace SwedbankPay\Checkout\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use SwedbankPay\Checkout\Api\Data\QuoteInterface;

class Quote extends AbstractExtensibleModel implements QuoteInterface
{
    /**
     * Constructor
     *
     * phpcs:disable
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        // phpcs:enable
        $this->_init(ResourceModel\Quote::class);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_getData(self::ID);
    }

    /**
     * @param int $entityId
     * @return void
     */
    public function setId($entityId)
    {
        $this->setData(self::ID, $entityId);
    }

    /**
     * @return string
     */
    public function getPaymentOrderId()
    {
        return $this->_getData(self::PAYMENT_ORDER_ID);
    }

    /**
     * @param string $paymentOrderId
     * @return void
     */
    public function setPaymentOrderId($paymentOrderId)
    {
        $this->setData(self::PAYMENT_ORDER_ID, $paymentOrderId);
    }

    /**
     * @return string|null
     */
    public function getPaymentIdPath()
    {
        return $this->_getData(self::PAYMENT_ID_PATH);
    }

    /**
     * @param string $paymentIdPath
     * @return void
     */
    public function setPaymentIdPath($paymentIdPath)
    {
        $this->setData(self::PAYMENT_ID_PATH, $paymentIdPath);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_getData(self::DESCRIPTION);
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->_getData(self::OPERATION);
    }

    /**
     * @param string $operation
     * @return void
     */
    public function setOperation($operation)
    {
        $this->setData(self::OPERATION, $operation);
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->_getData(self::STATE);
    }

    /**
     * @param string $state
     * @return void
     */
    public function setState($state)
    {
        $this->setData(self::STATE, $state);
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->_getData(self::CURRENCY);
    }

    /**
     * @param string $currency
     * @return void
     */
    public function setCurrency($currency)
    {
        $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->_getData(self::AMOUNT);
    }

    /**
     * @param int $amount
     * @return void
     */
    public function setAmount($amount)
    {
        $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @return int
     */
    public function getVatAmount()
    {
        return $this->_getData(self::VAT_AMOUNT);
    }

    /**
     * @param int $vatAmount
     * @return void
     */
    public function setVatAmount($vatAmount)
    {
        $this->setData(self::VAT_AMOUNT, $vatAmount);
    }

    /**
     * @return int
     */
    public function getRemainingCapturingAmount()
    {
        return $this->_getData(self::REMAINING_CAPTURE_AMOUNT);
    }

    /**
     * @param int $amount
     * @return void
     */
    public function setRemainingCapturingAmount($amount)
    {
        $this->setData(self::REMAINING_CAPTURE_AMOUNT, $amount);
    }

    /**
     * @return int
     */
    public function getRemainingCancellationAmount()
    {
        return $this->_getData(self::REMAINING_CANCELLATION_AMOUNT);
    }

    /**
     * @param int $amount
     * @return void
     */
    public function setRemainingCancellationAmount($amount)
    {
        $this->setData(self::REMAINING_CANCELLATION_AMOUNT, $amount);
    }

    /**
     * @return int
     */
    public function getRemainingReversalAmount()
    {
        return $this->_getData(self::REMAINING_REVERSAL_AMOUNT);
    }

    /**
     * @param int $amount
     * @return void
     */
    public function setRemainingReversalAmount($amount)
    {
        $this->setData(self::REMAINING_REVERSAL_AMOUNT, $amount);
    }

    /**
     * @return string
     */
    public function getPayerToken()
    {
        return $this->_getData(self::PAYER_TOKEN);
    }

    /**
     * @param string $payerToken
     * @return void
     */
    public function setPayerToken($payerToken)
    {
        $this->setData(self::PAYER_TOKEN, $payerToken);
    }

    /**
     * @return int
     */
    public function getQuoteId()
    {
        return $this->_getData(self::QUOTE_ID);
    }

    /**
     * @param int $quoteId
     * @return void
     */
    public function setQuoteId($quoteId)
    {
        $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @return int
     */
    public function getIsUpdated()
    {
        return $this->_getData(self::IS_UPDATED);
    }

    /**
     * @param int $isUpdated
     * @return void
     */
    public function setIsUpdated($isUpdated)
    {
        $this->setData(self::IS_UPDATED, $isUpdated);
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * @param string $createdAt
     * @return void
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->_getData(self::UPDATED_AT);
    }

    /**
     * @param string $updatedAt
     * @return void
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
