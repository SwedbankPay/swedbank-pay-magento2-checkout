<?php

namespace SwedbankPay\Checkout\Model\Config\Source;

use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class TosPage implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $res = [
            [
                'value' => '',
                'label' => __('Please select')
            ]
        ];

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', Page::STATUS_ENABLED);

        foreach ($collection as $page) {
            $data['value'] = $page->getData('identifier');
            $data['label'] = $page->getData('title');
            $res[] = $data;
        }

        return $res;
    }
}
