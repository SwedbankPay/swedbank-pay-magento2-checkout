<?php

namespace SwedbankPay\Checkout\Test\Unit;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as MageOrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SwedbankPay\Api\Service\Paymentorder\Resource\Collection\Item\OrderItem;
use SwedbankPay\Checkout\Helper\Factory\OrderItemFactory;

class OrderItemFactoryTest extends TestCase
{
    /**
     * @var OrderItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var QuoteItem|MockObject
     */
    protected $quoteItem;

    /**
     * @var MageOrderItem|MockObject
     */
    protected $orderItem;

    /**
     * @var ProductRepository|MockObject
     */
    protected $productRepository;

    /**
     * @var CategoryRepository|MockObject
     */
    protected $categoryRepository;

    public function setUp()
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->orderItemFactory = new OrderItemFactory($this->productRepository, $this->categoryRepository);

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSku',
                'getName',
                'getProduct',
                'getQty',
                'getPriceInclTax',
                'getRowTotalInclTax',
                'getTaxAmount',
                'getTaxPercent',
                'getDiscountAmount'
            ])
            ->getMock();

        $this->orderItem = $this->getMockBuilder(MageOrderItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSku',
                'getName',
                'getProductId',
                'getQtyOrdered',
                'getPriceInclTax',
                'getRowTotalInclTax',
                'getTaxAmount',
                'getTaxPercent',
                'getDiscountAmount'
            ])
            ->getMock();
    }

    /**
     * @dataProvider orderItemDataProvider
     * @param array $orderItemArray
     */
    public function testCreateByQuoteItem($orderItemArray)
    {
        $productId = $orderItemArray['id'];
        $categoryIds = $orderItemArray['categoryIds'];
        $categoryName = $orderItemArray['categoryName'];
        $sku = $orderItemArray['sku'];
        $name = $orderItemArray['name'];
        $quantity = $orderItemArray['quantity'];
        $unitPrice = $orderItemArray['unitPrice'];
        $amount = $orderItemArray['amount'];
        $vatAmount = $orderItemArray['vatAmount'];
        $vatPercent = $orderItemArray['vatPercent'];

        $discountPrice = null;

        if (array_key_exists('discountAmount', $orderItemArray)) {
            $discountPrice = $orderItemArray['discountPrice'];
        }

        $payableAmount = $discountPrice ? $amount - $discountPrice : $amount;

        $product = $this->createMock(Product::class);
        $category = $this->createMock(Category::class);

        $product->expects($this->once())->method('getId')->willReturn($productId);
        $product->expects($this->once())->method('getCategoryIds')->willReturn($categoryIds);
        $category->expects($this->once())->method('getName')->willReturn($categoryName);
        $this->categoryRepository->expects($this->once())->method('get')->willReturn($category);
        $this->productRepository->expects($this->once())->method('getById')->willReturn($product);

        $this->quoteItem->expects($this->once())->method('getSku')->willReturn($sku);
        $this->quoteItem->expects($this->once())->method('getName')->willReturn($name);
        $this->quoteItem->expects($this->once())->method('getProduct')->willReturn($product);
        $this->quoteItem->expects($this->once())->method('getQty')->willReturn($quantity);
        $this->quoteItem->expects($this->once())->method('getPriceInclTax')->willReturn($unitPrice);
        $this->quoteItem->expects($this->once())->method('getTaxAmount')->willReturn($vatAmount);
        $this->quoteItem->expects($this->once())->method('getTaxPercent')->willReturn($vatPercent);
        $this->quoteItem->expects($this->once())->method('getRowTotalInclTax')->willReturn($amount);
        $this->quoteItem->expects($this->once())->method('getDiscountAmount')->willReturn($discountPrice);

        $orderItem = $this->orderItemFactory->createByQuoteItem($this->quoteItem);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals('PRODUCT', $orderItem->getType());
        $this->assertEquals($payableAmount * 100, $orderItem->getAmount());
        $this->assertEquals($discountPrice * 100, $orderItem->getDiscountPrice());
        $this->assertNull($orderItem->getDescription());
    }

    /**
     * @dataProvider orderItemDataProvider
     * @param array $orderItemArray
     */
    public function testCreateByOrderItem($orderItemArray)
    {
        $productId = $orderItemArray['id'];
        $categoryIds = $orderItemArray['categoryIds'];
        $categoryName = $orderItemArray['categoryName'];
        $sku = $orderItemArray['sku'];
        $name = $orderItemArray['name'];
        $quantity = $orderItemArray['quantity'];
        $unitPrice = $orderItemArray['unitPrice'];
        $amount = $orderItemArray['amount'];
        $vatAmount = $orderItemArray['vatAmount'];
        $vatPercent = $orderItemArray['vatPercent'];

        $discountPrice = null;

        if (array_key_exists('discountAmount', $orderItemArray)) {
            $discountPrice = $orderItemArray['discountPrice'];
        }

        $payableAmount = $discountPrice ? $amount - $discountPrice : $amount;

        $product = $this->createMock(Product::class);
        $category = $this->createMock(Category::class);

        $product->expects($this->once())->method('getCategoryIds')->willReturn($categoryIds);
        $category->expects($this->once())->method('getName')->willReturn($categoryName);
        $this->categoryRepository->expects($this->once())->method('get')->willReturn($category);
        $this->productRepository->expects($this->once())->method('getById')->willReturn($product);

        $this->orderItem->expects($this->once())->method('getSku')->willReturn($sku);
        $this->orderItem->expects($this->once())->method('getName')->willReturn($name);
        $this->orderItem->expects($this->once())->method('getProductId')->willReturn($productId);
        $this->orderItem->expects($this->once())->method('getQtyOrdered')->willReturn($quantity);
        $this->orderItem->expects($this->once())->method('getPriceInclTax')->willReturn($unitPrice);
        $this->orderItem->expects($this->once())->method('getTaxAmount')->willReturn($vatAmount);
        $this->orderItem->expects($this->once())->method('getTaxPercent')->willReturn($vatPercent);
        $this->orderItem->expects($this->once())->method('getRowTotalInclTax')->willReturn($amount);
        $this->orderItem->expects($this->once())->method('getDiscountAmount')->willReturn($discountPrice);

        $orderItem = $this->orderItemFactory->createByOrderItem($this->orderItem);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals('PRODUCT', $orderItem->getType());
        $this->assertEquals($categoryName, $orderItem->getItemClass());
        $this->assertEquals($payableAmount * 100, $orderItem->getAmount());
        $this->assertEquals($discountPrice * 100, $orderItem->getDiscountPrice());
        $this->assertNull($orderItem->getDescription());
    }

    /**
     * @dataProvider orderItemDataProvider
     * @param array $orderItemArray
     */
    public function testCreateShippingByQuote(array $orderItemArray)
    {
        $shippingAmount = $orderItemArray['shippingAmount'];
        $shippingInclTax = $orderItemArray['shippingInclTax'];
        $shippingTaxAmount = $orderItemArray['shippingTaxAmount'];
        $shippingTaxPercent = (int) (($shippingTaxAmount * 100 / $shippingAmount) * 100);

        /** @var Quote|MockObject $quote */
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAddress'])
            ->getMock();

        /** @var QuoteAddress|MockObject $shippingAddress */
        $shippingAddress = $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAmount', 'getShippingInclTax', 'getShippingTaxAmount'])
            ->getMock();

        $quote->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddress);
        $shippingAddress->expects($this->once())->method('getShippingAmount')->willReturn($shippingAmount);
        $shippingAddress->expects($this->once())->method('getShippingInclTax')->willReturn($shippingInclTax);
        $shippingAddress->expects($this->once())->method('getShippingTaxAmount')->willReturn($shippingTaxAmount);

        $orderItem = $this->orderItemFactory->createShippingByQuote($quote);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals(1, $orderItem->getQuantity());
        $this->assertEquals('pcs', $orderItem->getQuantityUnit());
        $this->assertEquals('SHIPPING_FEE', $orderItem->getType());
        $this->assertEquals($shippingTaxPercent, $orderItem->getVatPercent());
    }

    /**
     * @dataProvider orderItemDataProvider
     * @param array $orderItemArray
     */
    public function testCreateShippingByOrder(array $orderItemArray)
    {
        $shippingAmount = $orderItemArray['shippingAmount'];
        $shippingInclTax = $orderItemArray['shippingInclTax'];
        $shippingTaxAmount = $orderItemArray['shippingTaxAmount'];
        $shippingTaxPercent = (int) (($shippingTaxAmount * 100 / $shippingAmount) * 100);

        /** @var Order|MockObject $order */
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $order->expects($this->once())->method('getShippingAmount')->willReturn($shippingAmount);
        $order->expects($this->once())->method('getShippingInclTax')->willReturn($shippingInclTax);
        $order->expects($this->once())->method('getShippingTaxAmount')->willReturn($shippingTaxAmount);

        $orderItem = $this->orderItemFactory->createShippingByOrder($order);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals(1, $orderItem->getQuantity());
        $this->assertEquals('pcs', $orderItem->getQuantityUnit());
        $this->assertEquals('SHIPPING_FEE', $orderItem->getType());
        $this->assertEquals($shippingTaxPercent, $orderItem->getVatPercent());
    }

    public function testGetItemClassWithCategoryNameContainingSpaces()
    {
        $productId = 1;
        $categoryIds = [1];
        $categoryName = 'Hand Bag';

        $product = $this->createMock(Product::class);
        $category = $this->createMock(Category::class);

        $product->expects($this->once())->method('getCategoryIds')->willReturn($categoryIds);
        $category->expects($this->once())->method('getName')->willReturn($categoryName);

        $this->categoryRepository->expects($this->once())->method('get')->willReturn($category);
        $this->productRepository->expects($this->once())->method('getById')->willReturn($product);

        $itemClass = $this->orderItemFactory->getItemClass($productId);

        $this->assertEquals('HandBag', $itemClass);
    }

    public function testGetItemClassWithNoSuchEntityException()
    {
        $productId = 1;

        $this->productRepository
            ->expects($this->once())
            ->method('getById')
            ->willThrowException(new NoSuchEntityException());

        $itemClass = $this->orderItemFactory->getItemClass($productId);

        $this->assertEquals('ProductGroup1', $itemClass);
    }

    public function orderItemDataProvider()
    {
        return [
            'Test 1' => [
                [
                    'id' => 1,
                    'sku' => '24-WB04',
                    'name' => 'Push It Messenger Bag',
                    'categoryIds' => [101],
                    'categoryName' => 'Bag',
                    'quantity' => 3,
                    'unitPrice' => 56.75,
                    'amount' => 168.75,
                    'vatAmount' => 27.00,
                    'vatPercent' => 25.00,
                    'discountPrice' => 27.00,
                    'shippingAmount' => 25.00,
                    'shippingInclTax' => 28.00,
                    'shippingTaxAmount' => 3.00
                ]
            ],
            'Test 2' => [
                [
                    'id' => 2,
                    'sku' => '24-WB03',
                    'name' => 'Womens tops',
                    'categoryIds' => [102],
                    'categoryName' => 'Tops',
                    'quantity' => 2,
                    'unitPrice' => 62.5,
                    'amount' => 125.00,
                    'vatAmount' => 25.00,
                    'vatPercent' => 25.00,
                    'shippingAmount' => 25.00,
                    'shippingInclTax' => 28.00,
                    'shippingTaxAmount' => 3.00
                ]
            ]
        ];
    }
}
