<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\Frontend;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Extension\Frontend\RequestProductExtension;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType as BaseRequestProductType;
use Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\AbstractTest;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestProductTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    private const HIDDEN_PRODUCT_ID = 12;

    /** @var RequestProductType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new RequestProductType();
        $this->formType->setDataClass(RequestProduct::class);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    /**
     * {@inheritDoc}
     */
    public function submitProvider(): array
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', Price::create(20, 'USD'));

        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequestProduct(),
                'defaultData'   => $this->getRequestProduct(),
            ],
            'invalid product and empty items' => [
                'isValid'       => false,
                'submittedData' => [
                    'product' => 333,
                ],
                'expectedData'  => $this->getRequestProduct(),
                'defaultData'   => $this->getRequestProduct(),
            ],
            'empty request' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment',
                    'requestProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this
                    ->getRequestProduct(2, 'comment_stripped', [$requestProductItem])->setRequest(null),
                'defaultData'   => $this->getRequestProduct(2, 'comment', [$requestProductItem])->setRequest(null),
            ],
            'empty items' => [
                'isValid'       => false,
                'submittedData' => [
                    'product' => 1,
                ],
                'expectedData'  => $this->getRequestProduct(1),
                'defaultData'   => $this->getRequestProduct(1),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment',
                    'requestProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this->getRequestProduct(2, 'comment_stripped', [$requestProductItem]),
                'defaultData'   => $this->getRequestProduct(2, 'comment', [$requestProductItem]),
            ],
            'hidden product' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => self::HIDDEN_PRODUCT_ID,
                    'comment'   => 'comment',
                    'requestProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this->getRequestProduct(null, 'comment_stripped', [$requestProductItem]),
                'defaultData'   => $this->getRequestProduct(null, 'comment', [$requestProductItem]),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType = $this->preparePriceType();
        $entityType = $this->prepareProductSelectType();
        $currencySelectionType = new CurrencySelectionTypeStub();
        $requestProductItemType = $this->prepareRequestProductItemType();
        $productUnitSelectionType = $this->prepareProductUnitSelectionType();

        $requestProductType = new BaseRequestProductType();
        $requestProductType->setDataClass(RequestProduct::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    PriceType::class                => $priceType,
                    ProductSelectType::class        => $entityType,
                    RequestProductType::class       => $requestProductType,
                    RequestProductItemType::class   => $requestProductItemType,
                    CurrencySelectionType::class    => $currencySelectionType,
                    ProductUnitSelectionType::class => $productUnitSelectionType,
                    QuantityType::class             => $this->getQuantityType(),
                ],
                [
                    FormType::class => [
                        new StripTagsExtensionStub($this),
                    ]
                ]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    protected function getTypeExtensions(): array
    {
        $productVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);
        $productVisibilityProvider->expects(self::any())
            ->method('isVisible')
            ->willReturnCallback(function ($productId) {
                return self::HIDDEN_PRODUCT_ID !== $productId;
            });

        return [new RequestProductExtension($productVisibilityProvider)];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators()
    {
        $quantityUnitPrecision = new QuantityUnitPrecision();
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });
        $quantityUnitPrecisionValidator = new QuantityUnitPrecisionValidator($roundingService);

        return [
            $quantityUnitPrecision->validatedBy() => $quantityUnitPrecisionValidator,
        ];
    }
}
