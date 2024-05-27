<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionRowType;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemsCollectionType;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemCurrencyHandler;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderTypeTest extends TypeTestCase
{
    use QuantityTypeTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressSecurityProvider */
    private $orderAddressSecurityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderCurrencyHandler */
    private $orderCurrencyHandler;

    /** @var OrderType */
    private $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TotalProcessorProvider */
    private $totalsProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LineItemSubtotalProvider */
    private $lineItemSubtotalProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DiscountSubtotalProvider */
    private $discountSubtotalProvider;

    /** @var PriceMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $priceMatcher;

    /** @var RateConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateConverter;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var ValidatorInterface */
    private $validator;

    /** @var OrderLineItemCurrencyHandler */
    private $currencyHandler;

    protected function setUp(): void
    {
        $this->orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $this->orderCurrencyHandler = $this->createMock(OrderCurrencyHandler::class);
        $this->totalsProvider = $this->createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->discountSubtotalProvider = $this->createMock(DiscountSubtotalProvider::class);
        $this->priceMatcher = $this->createMock(PriceMatcher::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);
        $this->currencyHandler = $this->createMock(OrderLineItemCurrencyHandler::class);

        $totalHelper = new TotalHelper(
            $this->totalsProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider,
            $this->rateConverter
        );

        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        // create a type instance with the mocked dependencies
        $this->type = new OrderType(
            $this->orderAddressSecurityProvider,
            $this->orderCurrencyHandler,
            new SubtotalSubscriber($totalHelper, $this->priceMatcher, $this->currencyHandler)
        );

        $this->type->setDataClass(Order::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Order',
                    'csrf_token_id' => 'order'
                ]
            );

        $this->type->setDataClass('Order');
        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmitValidData(array $submitData, Order $expectedOrder)
    {
        $order = new Order();
        $order->setUuid($expectedOrder->getUuid());// Sync order.
        $order->setTotalDiscounts(Price::create(99, 'USD'));

        $options = [
            'data' => $order
        ];

        $this->orderCurrencyHandler->expects($this->any())
            ->method('setOrderCurrency');

        $form = $this->factory->create(OrderType::class, null, $options);

        $subtotal = new Subtotal();
        $subtotal->setAmount(99);
        $subtotal->setCurrency('USD');
        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $total = new Subtotal();
        $total->setAmount(0);
        $total->setCurrency('USD');

        $this->totalsProvider->expects($this->once())
            ->method('enableRecalculation')
            ->willReturnSelf();
        $this->totalsProvider->expects($this->once())
            ->method('getTotal')
            ->willReturn($total);

        $this->discountSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([]);

        $this->rateConverter->expects($this->exactly(2))
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $value) {
                return $value->getValue();
            });

        $form->submit($submitData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedOrder, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'valid data' => [
                'submitData' => [
                    'sourceEntityClass' => 'Class',
                    'sourceEntityId' => '1',
                    'sourceEntityIdentifier' => '1',
                    'customerUser' => 1,
                    'customer' => 2,
                    'poNumber' => '11',
                    'shipUntil' => null,
                    'subtotal' => 0.0,
                    'total' => 0.0,
                    'totalDiscounts' => 0.0,
                    'lineItems' => [
                        [
                            'productSku' => 'HLCU',
                            'product' => 3,
                            'freeFormProduct' => '',
                            'quantity' => 39,
                            'productUnit' => 'piece',
                            'price' => [
                                'value' => 26.5050,
                                'currency' => 'USD',
                            ],
                            'priceType' => 10,
                            'shipBy' => '',
                            'comment' => ''
                        ],
                    ],
                    'currency' => 'USD',
                    'shippingMethod' => 'shippingMethod1',
                    'shippingMethodType' => 'shippingType1',
                    'estimatedShippingCostAmount' => 10,
                    'overriddenShippingCostAmount' => [
                        'value' => 5,
                        'currency' => 'USD',
                    ]
                ],
                'expectedOrder' => $this->getOrder(
                    [
                        'sourceEntityClass' => 'Class',
                        'sourceEntityId' => '1',
                        'sourceEntityIdentifier' => '1',
                        'customerUser' => 1,
                        'customer' => 2,
                        'poNumber' => '11',
                        'shipUntil' => null,
                        'subtotalObject' => MultiCurrency::create(99, 'USD', 99),
                        'totalObject' => MultiCurrency::create(0, 'USD', 0),
                        'totalDiscounts' => new Price(),
                        'lineItems' => [
                            [
                                'productSku' => 'HLCU',
                                'product' => 3,
                                'freeFormProduct' => '',
                                'quantity' => 39,
                                'price' => [
                                    'value' => 26.5050,
                                    'currency' => 'USD',
                                ],
                                'priceType' => 10,
                                'comment' => null
                            ],
                        ],
                        'currency' => 'USD',
                        'shippingMethod' => 'shippingMethod1',
                        'shippingMethodType' => 'shippingType1',
                        'estimatedShippingCostAmount' => '10',
                        'overriddenShippingCostAmount' => 5.0
                    ]
                )
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $userSelectType = new StubEntityType(
            [
                1 => $this->getEntity(User::class, 1),
                2 => $this->getEntity(User::class, 2),
            ],
            UserSelectType::class
        );

        $customerSelectType = new StubEntityType(
            [
                1 => $this->getEntity(Customer::class, 1),
                2 => $this->getEntity(Customer::class, 2),
            ],
            CustomerSelectType::NAME
        );

        $customerUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity(CustomerUser::class, 1),
                2 => $this->getEntity(CustomerUser::class, 2),
            ],
            CustomerUserSelectType::NAME
        );

        $priceListSelectType = new StubEntityType(
            [
                1 => $this->getEntity(PriceList::class, 1),
                2 => $this->getEntity(PriceList::class, 2),
            ],
            PriceListSelectType::NAME
        );

        $productUnitSelectionType = $this->prepareProductUnitSelectionType();
        $productSelectType = new ProductSelectTypeStub();
        $entityType = $this->prepareProductEntityType();
        $priceType = $this->preparePriceType();

        /** @var \PHPUnit\Framework\MockObject\MockObject|ProductUnitsProvider $productUnitsProvider */
        $productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([
                'item' => 'item',
                'kg' => 'kilogram',
            ]);

        $orderLineItemType = new OrderLineItemType($productUnitsProvider);
        $orderLineItemType->setDataClass(OrderLineItem::class);
        $currencySelectionType = new CurrencySelectionTypeStub();

        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    CollectionType::class => new CollectionType(),
                    OroDateType::class => new OroDateType(),
                    PriceType::class => $priceType,
                    EntityType::class => $entityType,
                    UserSelectType::class => $userSelectType,
                    ProductSelectType::class => $productSelectType,
                    ProductUnitSelectionType::class => $productUnitSelectionType,
                    CustomerSelectType::class => $customerSelectType,
                    CurrencySelectionType::class => $currencySelectionType,
                    CustomerUserSelectType::class => $customerUserSelectType,
                    PriceListSelectType::class => $priceListSelectType,
                    OrderLineItemsCollectionType::class => new OrderLineItemsCollectionType(),
                    OrderDiscountCollectionTableType::class => new OrderDiscountCollectionTableType(),
                    OrderLineItemType::class => $orderLineItemType,
                    OrderDiscountCollectionRowType::class => new OrderDiscountCollectionRowType(),
                    QuantityType::class => $this->getQuantityType(),
                    OroHiddenNumberType::class => new OroHiddenNumberType($this->numberFormatter),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    private function getEntity(string $className, int|string $id, string $primaryKey = 'id'): object
    {
        static $entities = [];
        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className();
            ReflectionUtil::setPropertyValue($entities[$className][$id], $primaryKey, $id);
        }

        return $entities[$className][$id];
    }

    private function prepareProductEntityType(): StubEntityType
    {
        return new StubEntityType(
            [
                2 => $this->getEntity(Product::class, 2),
                3 => $this->getEntity(Product::class, 3),
            ]
        );
    }

    private function prepareProductUnitSelectionType(): ProductUnitSelectionTypeStub
    {
        return new ProductUnitSelectionTypeStub(
            [
                'kg' => $this->getEntity(ProductUnit::class, 'kg', 'code'),
                'item' => $this->getEntity(ProductUnit::class, 'item', 'code'),
            ]
        );
    }

    private function preparePriceType(): PriceType
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return $priceType;
    }

    private function getOrder(array $data): Order
    {
        $order = new Order();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $fieldName => $value) {
            if ($fieldName === 'lineItems') {
                foreach ($value as $lineItem) {
                    $lineItem = $this->getLineItem($lineItem);
                    $order->addLineItem($lineItem);
                }
            } elseif ($fieldName === 'customerUser') {
                $order->setCustomerUser($this->getEntity(CustomerUser::class, $value));
            } elseif ($fieldName === 'customer') {
                $order->setCustomer($this->getEntity(Customer::class, $value));
            } else {
                $accessor->setValue($order, $fieldName, $value);
            }
        }

        return $order;
    }

    private function getLineItem(array $data): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $fieldName => $value) {
            if ($fieldName === 'product') {
                $lineItem->setProduct($this->getEntity(Product::class, $value));
            } elseif ($fieldName === 'price') {
                $price = new Price();
                $price->setCurrency($value['currency']);
                $price->setValue($value['value']);
                $lineItem->setPrice($price);
            } else {
                $accessor->setValue($lineItem, $fieldName, $value);
            }
        }

        return $lineItem;
    }
}
