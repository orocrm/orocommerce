<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Datagrid\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\EventListener\FrontendProductPriceDatagridListener;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class FrontendProductPriceDatagridListenerTest extends TestCase
{
    use EntityTrait;

    private ProductPriceScopeCriteriaRequestHandler|MockObject $scopeCriteriaRequestHandler;

    private UserCurrencyManager|MockObject $currencyManager;

    private FeatureChecker|MockObject $featureChecker;

    private ProductPriceProvider|MockObject $combinedProductPriceProvider;

    private FrontendProductPricesProvider|MockObject $frontendProductPricesProvider;

    private FrontendProductPriceDatagridListener $listener;

    protected function setUp(): void
    {
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->combinedProductPriceProvider = $this->createMock(ProductPriceProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnMap([
                ['oro.pricing.productprice.price.label', [], null, null, 'Price'],
            ]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($className, $id) {
                return $this->getEntity($className, ['id' => $id]);
            });

        $this->frontendProductPricesProvider = $this->createMock(FrontendProductPricesProvider::class);

        $this->listener = new FrontendProductPriceDatagridListener(
            $this->scopeCriteriaRequestHandler,
            $this->currencyManager,
            $this->combinedProductPriceProvider,
            $translator,
            $doctrineHelper,
            $this->frontendProductPricesProvider
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_pricing');
    }

    private function setUpPriceListRequestHandler(array $priceCurrencies = []): void
    {
        $this->scopeCriteriaRequestHandler->expects(self::any())
            ->method('getPriceScopeCriteria')
            ->willReturn(new ProductPriceScopeCriteria());

        $this->currencyManager->expects(self::any())
            ->method('getUserCurrency')
            ->willReturn(reset($priceCurrencies));
    }

    /**
     * @dataProvider onBuildBeforeDataProvider
     */
    public function testOnBuildBeforeCombined(
        array $priceCurrencies = [],
        array $expectedConfig = [],
        bool $isFlatPricing = false
    ): void {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_pricing', null, true],
                ['oro_price_lists_flat', null, $isFlatPricing]
            ]);

        $this->setUpPriceListRequestHandler($priceCurrencies);

        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        self::assertEquals($expectedConfig, $config->toArray());
    }

    public function onBuildBeforeDataProvider(): array
    {
        return [
            'no currencies' => [
                'priceCurrencies' => [],
            ],
            'valid currencies' => [
                'priceCurrencies' => ['EUR'],
                'expectedConfig' => [
                    'properties' => [
                        'prices' => ['type' => 'field', 'frontend_type' => 'row_array'],
                        'shoppingListPrices' => ['type' => 'field', 'frontend_type' => 'row_array'],
                    ],
                    'columns' => [
                        'minimal_price' => ['label' => 'Price'],
                        'minimal_price_sort' => [
                            'label' => 'oro.pricing.price.label',
                        ]
                    ],
                    'filters' => [
                        'columns' => [
                            'minimal_price' => [
                                'type' => 'frontend-product-price',
                                'data_name' => 'minimal_price.CPL_ID_CURRENCY_UNIT'
                            ]
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'minimal_price_sort' => [
                                'data_name' => 'decimal.minimal_price.CPL_ID_CURRENCY',
                                'type' => 'decimal',
                            ]
                        ]
                    ],
                ],
                'isFlatPricing' => false
            ],
            'valid currencies flat pricing' => [
                'priceCurrencies' => ['EUR'],
                'expectedConfig' => [
                    'properties' => [
                        'prices' => ['type' => 'field', 'frontend_type' => 'row_array'],
                        'shoppingListPrices' => ['type' => 'field', 'frontend_type' => 'row_array'],
                    ],
                    'columns' => [
                        'minimal_price' => ['label' => 'Price'],
                        'minimal_price_sort' => [
                            'label' => 'oro.pricing.price.label',
                        ]
                    ],
                    'filters' => [
                        'columns' => [
                            'minimal_price' => [
                                'type' => 'frontend-product-price',
                                'data_name' => 'minimal_price.PRICE_LIST_ID_CURRENCY_UNIT'
                            ]
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'minimal_price_sort' => [
                                'data_name' => 'decimal.minimal_price.PRICE_LIST_ID_CURRENCY',
                                'type' => 'decimal',
                            ]
                        ]
                    ],
                ],
                'isFlatPricing' => true
            ],
        ];
    }

    public function testOnResultAfterNoRecords(): void
    {
        $this->currencyManager->expects(self::never())
            ->method($this->anything());

        $query = $this->createMock(SearchQueryInterface::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new SearchResultAfter($datagrid, $query, []);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_pricing')
            ->willReturn(false);

        $event = $this->createMock(SearchResultAfter::class);
        $event->expects(self::never())
            ->method($this->anything());

        $this->listener->onResultAfter($event);
    }

    public function testOnBuildBeforeFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_pricing')
            ->willReturn(false);

        $event = $this->createMock(BuildBefore::class);
        $event->expects(self::never())
            ->method($this->anything());

        $this->listener->onBuildBefore($event);
    }

    /**
     * @dataProvider onResultWithCombinedPricesProvider
     */
    public function testOnResultWithCombinedPrices(array $products, array $combinedProductPrices, array $expected): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_pricing')
            ->willReturn(true);

        $this->setUpPriceListRequestHandler(['USD']);

        $records = [new ResultRecord($products)];
        $priceScopeCriteria = new ProductPriceScopeCriteria();

        $this->scopeCriteriaRequestHandler->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($priceScopeCriteria);

        $this->combinedProductPriceProvider->expects(self::once())
            ->method('getPricesForProductsByPriceList')
            ->with($records, $priceScopeCriteria, 'USD')
            ->willReturn($combinedProductPrices);

        $query = $this->createMock(SearchQueryInterface::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new SearchResultAfter($datagrid, $query, $records);
        $this->listener->onResultAfter($event);

        $actualResults = $event->getRecords();

        self::assertSameSize($expected, $actualResults);
        foreach ($expected as $key => $expectedResult) {
            $actualResult = $actualResults[$key];
            foreach ($expectedResult as $name => $value) {
                self::assertEquals($value, $actualResult->getValue($name));
            }
        }
    }

    public function onResultWithCombinedPricesProvider(): array
    {
        return [
            'valid data' => [
                'sourceResults' => [
                    'id' => 2
                ],
                [
                    2 => [
                        'item_1' => [
                            'price' => 20,
                            'currency' => 'EUR',
                            'formatted_price' => 'EUR20',
                            'unit' => 'item',
                            'formatted_unit' => 'item-formatted',
                            'quantity' => 1,
                            'quantity_with_unit' => '1-item-formatted',
                        ],
                        'item_2' => [
                            'price' => 21,
                            'currency' => 'EUR',
                            'formatted_price' => 'EUR21',
                            'unit' => 'item',
                            'formatted_unit' => 'item-formatted',
                            'quantity' => 2,
                            'quantity_with_unit' => '2-item-formatted',
                        ],
                    ],
                ],
                'expectedResults' => [
                    [
                        'id' => 2,
                        'prices' => [
                            'item_1' => [
                                'price' => 20,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR20',
                                'unit' => 'item',
                                'formatted_unit' => 'item-formatted',
                                'quantity' => 1,
                                'quantity_with_unit' => '1-item-formatted',
                            ],
                            'item_2' => [
                                'price' => 21,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR21',
                                'unit' => 'item',
                                'formatted_unit' => 'item-formatted',
                                'quantity' => 2,
                                'quantity_with_unit' => '2-item-formatted',
                            ],
                        ],
                        'shoppingListPrices' => [],
                        'price_units' => null,
                        'price_quantities' => null,
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider onResultWithShoppingListPricesDataProvider
     */
    public function testOnResultWithShoppingListPrices(
        array $productData,
        array $shoppingListPrices,
        array $expectedResults
    ): void {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_pricing')
            ->willReturn(true);

        $this->setUpPriceListRequestHandler(['USD']);

        $records = [new ResultRecord($productData)];
        $priceScopeCriteria = new ProductPriceScopeCriteria();

        $this->scopeCriteriaRequestHandler->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($priceScopeCriteria);

        $this->combinedProductPriceProvider->expects(self::once())
            ->method('getPricesForProductsByPriceList')
            ->with($records, $priceScopeCriteria, 'USD')
            ->willReturn([]);

        $product = $this->getEntity(Product::class, ['id' => $productData['id']]);
        $this->frontendProductPricesProvider->expects(self::once())
            ->method('getShoppingListPricesByProducts')
            ->with([$product])
            ->willReturn($shoppingListPrices);

        $query = $this->createMock(SearchQueryInterface::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new SearchResultAfter($datagrid, $query, $records);
        $this->listener->onResultAfter($event);

        $actualResults = $event->getRecords();

        self::assertSameSize($expectedResults, $actualResults);
        foreach ($expectedResults as $key => $expectedResult) {
            $actualResult = $actualResults[$key];
            foreach ($expectedResult as $name => $value) {
                self::assertEquals($value, $actualResult->getValue($name));
            }
        }
    }

    public function onResultWithShoppingListPricesDataProvider(): array
    {
        return [
            [
                'product' => [
                    'id' => 2
                ],
                'shoppingListPrices' => [
                    2 => [
                        'item' => [
                            'price' => 19,
                            'currency' => 'EUR',
                            'formatted_price' => 'EUR19',
                            'unit' => 'item',
                            'formatted_unit' => 'item-formatted',
                            'quantity' => 10.0,
                            'quantity_with_unit' => '10-items-formatted',
                        ],
                    ],
                ],
                'expectedResults' => [
                    [
                        'id' => 2,
                        'prices' => [],
                        'shoppingListPrices' => [
                            'item' => [
                                'price' => 19,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR19',
                                'unit' => 'item',
                                'formatted_unit' => 'item-formatted',
                                'quantity' => 10.0,
                                'quantity_with_unit' => '10-items-formatted',
                            ],
                        ],
                        'price_units' => null,
                        'price_quantities' => null,
                    ],
                ],
            ],
        ];
    }
}
