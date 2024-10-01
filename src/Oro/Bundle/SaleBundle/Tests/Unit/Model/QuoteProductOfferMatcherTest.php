<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;

class QuoteProductOfferMatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var QuoteProductOfferMatcher */
    private $matcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->matcher = new QuoteProductOfferMatcher();
    }

    /**
     * @dataProvider matchDataProvider
     */
    public function testMatch(
        QuoteProduct $quoteProduct,
        ?string $unitCode,
        ?string $quantity,
        ?QuoteProductOffer $expectedResult
    ) {
        $this->assertEquals($expectedResult, $this->matcher->match($quoteProduct, $unitCode, $quantity));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function matchDataProvider(): array
    {
        return [
            'empty quote product' => [
                'quoteProduct' => $this->createQuoteProduct(),
                'unitCode' => 'item',
                'quantity' => '100',
                'expectedResult' => null,
            ],
            'empty request' => [
                'quoteProduct' => $this->createQuoteProduct(),
                'unitCode' => null,
                'quantity' => null,
                'expectedResult' => null,
            ],
            'quote product without expected unit code' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 1, true],
                        ['kg', 100, false],
                        ['liter', 1, true],
                        ['liter', 100, false]
                    ]
                ),
                'unitCode' => 'item',
                'quantity' => '100',
                'expectedResult' => null,
            ],
            'quote product with one selected matched offer' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '50',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, false),
            ],
            'quote product with no selected matched offer' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '51',
                'expectedResult' => null,
            ],
            'quote product with one matched offer with open condition' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '60',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with one more than matched offer with open condition' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '65',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with not matched offer with open condition' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '50',
                'expectedResult' => null,
            ],
            'quote product with two matched offers first selected' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                        ['kg', 50, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '50',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, false),
            ],
            'quote product with two matched offers second selected' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                        ['kg', 60, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '60',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, false),
            ],
            'quote product with two matched offers none selected' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                        ['kg', 60, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '55',
                'expectedResult' => null,
            ],
            'quote product with two offers with opened conditions first limit' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                        ['kg', 50, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '50',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, true),
            ],
            'quote product with two offers with opened conditions more than first' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, true],
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '55',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, true),
            ],
            'quote product with two offers with opened conditions second limit' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, true],
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '60',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with two offers with opened conditions more than second' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                        ['kg', 50, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '65',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with two offers with opened not matched' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, true],
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '45',
                'expectedResult' => null,
            ],
            'quote product with two offers and first opened condition first limit' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, true],
                        ['kg', 60, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '50',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, true),
            ],
            'quote product with two offers and first opened condition more than first' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, true],
                        ['kg', 60, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '55',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, true),
            ],
            'quote product with two offers and first opened condition second limit' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, false],
                        ['kg', 50, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '60',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, false),
            ],
            'quote product with two offers and first opened condition more than second' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, true],
                        ['kg', 60, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '65',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, true),
            ],
            'quote product with two offers and first opened not matched' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, false],
                        ['kg', 50, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '45',
                'expectedResult' => null,
            ],
            'quote product with two offers and second opened condition first limit' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '50',
                'expectedResult' => $this->createQuoteProductOffer('kg', 50, false),
            ],
            'quote product with two offers and second opened condition more than first' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                        ['kg', 50, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '55',
                'expectedResult' => null,
            ],
            'quote product with two offers and second opened condition second limit' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '60',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with two offers and second opened condition more than second' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '65',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with two offers and second opened not matched' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 50, false],
                        ['kg', 60, true],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '45',
                'expectedResult' => null,
            ],
            'quote product without expected quantity' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 500, true],
                        ['kg', 1000, true],
                        ['liter', 10, true],
                        ['liter', 100, true]
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '100',
                'expectedResult' => null,
            ],
            'quote product with expected unit code and int quantity mixed order' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 60, true],
                        ['kg', 1, true],
                        ['kg', 100, false],
                        ['kg', 50, false],
                        ['liter', 120, false]
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '120',
                'expectedResult' => $this->createQuoteProductOffer('kg', 60, true),
            ],
            'quote product with expected unit code and float quantity mixed order' => [
                'quoteProduct' => $this->createQuoteProduct(
                    [
                        ['kg', 1, true],
                        ['kg', 100, false],
                        ['liter', 100.5, true],
                        ['kg', 101, true],
                        ['liter', 100, true],
                        ['kg', '100.5', false],
                        ['liter', 120, false],
                        ['kg', 50, false],
                    ]
                ),
                'unitCode' => 'kg',
                'quantity' => '100.5',
                'expectedResult' => $this->createQuoteProductOffer('kg', 100.5, false),
            ],
        ];
    }

    private function createQuoteProduct(array $offers = []): QuoteProduct
    {
        $quoteProduct = new QuoteProduct();

        foreach ($offers as $offer) {
            [$unitCode, $quantity, $allowIncrements] = $offer;

            $offer = $this->createQuoteProductOffer($unitCode, $quantity, $allowIncrements);

            $quoteProduct->addQuoteProductOffer($offer);
            $offer->setQuoteProduct(null);
        }

        return $quoteProduct;
    }

    private function createQuoteProductOffer(
        string $unitCode,
        float $quantity,
        bool $allowIncrements
    ): QuoteProductOffer {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $item = new QuoteProductOffer();
        $item->setProductUnit($unit)->setQuantity($quantity)->setAllowIncrements($allowIncrements);

        return $item;
    }
}
