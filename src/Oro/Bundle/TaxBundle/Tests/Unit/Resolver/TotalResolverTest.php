<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;
use Oro\Bundle\TaxBundle\Resolver\TotalResolver;
use Oro\Bundle\TaxBundle\Tests\ResultComparatorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TotalResolverTest extends TestCase
{
    use ResultComparatorTrait;

    private TaxationSettingsProvider|MockObject $settingsProvider;

    private TotalResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->resolver = new TotalResolver($this->settingsProvider, new RoundingResolver());
    }

    public function testResolveEmptyItems(): void
    {
        $taxable = new Taxable();

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf(Result::class, $taxable->getResult());
        $this->assertInstanceOf(ResultElement::class, $taxable->getResult()->getTotal());
        $this->compareResult([], $taxable->getResult());
    }

    public function testResolveLockedResult(): void
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());
        $taxable->getResult()->lockResult();

        $this->resolver->resolve($taxable);

        $this->assertNull($taxable->getResult()->getOffset(Result::TOTAL));
        $this->assertNull($taxable->getResult()->getOffset(Result::TAXES));
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(
        array $items,
        ?ResultElement $shippingResult,
        ResultElement $expectedTotalResult,
        array $expectedTaxes,
        bool $startOnItem = false
    ): void {
        $this->settingsProvider->expects($this->any())
            ->method('isStartCalculationOnItem')
            ->willReturn($startOnItem);

        $taxable = new Taxable();
        if ($shippingResult) {
            $taxable->getResult()->offsetSet(Result::SHIPPING, $shippingResult);
        }
        foreach ($items as $item) {
            $itemTaxable = new Taxable();
            $itemTaxable->setResult(new Result($item));
            $taxable->addItem($itemTaxable);
        }

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf(Result::class, $taxable->getResult());
        $this->assertInstanceOf(ResultElement::class, $taxable->getResult()->getTotal());
        $this->assertEquals($expectedTotalResult, $taxable->getResult()->getTotal());
        $this->assertEquals($expectedTaxes, $taxable->getResult()->getTaxes());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function resolveDataProvider(): array
    {
        return [
            'plain' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('24.1879', '19.99', '4.1979', '0.0021'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.3993'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.1994'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create('24.1879', '19.99', '4.1979', '0.0021'),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                    TaxResultElement::create('2', '0.07', '19.99', '1.3993'),
                    TaxResultElement::create('3', '0.06', '19.99', '1.1994'),
                ],
            ],
            'multiple items same tax' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.7492', '21.99', '1.7592', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.99', '1.7592'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.9092', '23.99', '1.9192', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.99', '1.9192'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create('71.2476', '65.97', '5.2776', '0.0024'),
                'expectedTaxes' => [TaxResultElement::create('1', '0.08', '65.97', '5.2776')],
            ],
            'tax excluded, start from total' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('22.035', '19.50', '2.535', '0.0013'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.50', '1.365'),
                            TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.0686', '21.99', '3.0786', '0.0014'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.99', '1.7592'),
                            TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('28.0683', '23.97', '4.0749', '0.0017'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.97', '1.9176'),
                            TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create(
                    '75.1719', // 22.035 + 25.0686 + 28.0683
                    '65.46',
                    '9.6885', // 2.535 + 3.0786 + 4.0749
                    '0.0044'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '65.46', '5.0418'),
                    TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                    TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                    TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                ],
            ],
            'tax excluded, start from item' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('22.035', '19.50', '2.535', '0.0013'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.50', '1.365'),
                            TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.0686', '21.99', '3.0786', '0.0014'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.99', '1.7592'),
                            TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('28.0683', '23.97', '4.0749', '0.0017'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.97', '1.9176'),
                            TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create(
                    '75.18', // sum of rounded includingTaxes
                    '65.46',
                    '9.69', // sum of rounded taxes
                    '0.0044'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '65.46', '5.0418'),
                    TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                    TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                    TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                ],
                'startOnItem' => true,
            ],
            'tax excluded, start from item, additional data set' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('64.3168', '63.68', '0.6368', '-0.0032'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.01', '63.68', '0.6368'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('584.487', '578.70', '5.7870', '-0.0030'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.01', '578.70', '5.7870'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('1576.5393', '1560.93', '15.6093', '-0.0007'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.01', '1560.93', '15.6093'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create(
                    '2225.35',
                    '2203.31',
                    '22.04',
                    '-0.0069'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.01', '2203.31', '22.0331'),
                ],
                'startOnItem' => true,
            ],
            'tax included, start from total' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('19.50', '19.2497', '0.2503', '0.0003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.2497', '1.3475'),
                            TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('21.99', '21.6863', '0.3037', '0.0037'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.6863', '1.7349'),
                            TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.15', '22.7630', '0.3870', '-0.003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '22.7630', '1.8210'),
                            TaxResultElement::create('4', '0.09', '22.7630', '2.0490'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create(
                    '64.64', // 19.5 + 21.99 + 23.15
                    '63.6990', // 19.2497 + 21.6863 + 22.7630
                    '0.9410', // 0.2503 + 0.3037 + 0.3870
                    '0.0010'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '63.6990', '4.9034'),
                    TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                    TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                    TaxResultElement::create('4', '0.09', '22.7630', '2.0490'),
                ],
            ],
            'tax included, start from item' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('19.50', '19.2497', '0.2503', '0.0003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.2497', '1.3475'),
                            TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('21.99', '21.6863', '0.3037', '0.0037'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.6863', '1.7349'),
                            TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.15', '22.7630', '0.3870', '-0.003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '22.7630', '1.8210'),
                            TaxResultElement::create('4', '0.09', '22.7630', '2.0490'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create(
                    '64.64', // sum of rounded includingTaxes
                    '63.70',
                    '0.94', // sum of rounded taxes
                    '0.0010'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '63.6990', '4.9034'),
                    TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                    TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                    TaxResultElement::create('4', '0.09', '22.7630', '2.0490'),
                ],
                'startOnItem' => true,
            ],
            'tax included, start from item to adjust amounts' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('19.50', '19.2497', '0.2503', '0.0003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.2497', '1.3475'),
                            TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('21.99', '21.6863', '0.3037', '0.0037'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.6863', '1.7349'),
                            TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.15', '22.7630', '0.3870', '-0.003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '22.7630', '1.8210'),
                            TaxResultElement::create('4', '0.09', '22.7630', '2.0490'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create(
                    '64.64',
                    '63.70', // sum of rounded excludingTaxes
                    '0.94', // sum of rounded taxes
                    '0.0010'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '63.6990', '4.9034'),
                    TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                    TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                    TaxResultElement::create('4', '0.09', '22.7630', '2.0490'),
                ],
                'startOnItem' => true,
            ],
            'failed' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('', ''),
                        Result::TAXES => [],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create('0', '0', '0', '0'),
                'expectedTaxes' => [],
            ],
            'safe if row failed' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('', '23.99', '1.9192', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.99', '1.9192'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                'expectedTaxes' => [TaxResultElement::create('1', '0.08', '19.99', '1.5992')],
            ],
            'safe if applied tax failed' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.9092', '23.99', '1.9192', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '', '1.9192'),
                        ],
                    ],
                ],
                'shippingResult' => ResultElement::create('0', '0'),
                'expectedTotalResult' => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                'expectedTaxes' => [TaxResultElement::create('1', '0.08', '19.99', '1.5992')],
            ],
            'no shipping taxes' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('21.50', '20.00', '1.50', '0.00'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '20.00', '1.50'),
                        ],
                    ],
                ],
                'shippingResult' => null,
                'expectedTotalResult' => ResultElement::create('21.50', '20.00', '1.50', '0.00'),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '20.00', '1.50'),
                ],
            ],
        ];
    }
}
