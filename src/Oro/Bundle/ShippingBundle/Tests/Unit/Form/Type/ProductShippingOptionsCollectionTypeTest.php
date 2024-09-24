<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use Oro\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductShippingOptionsCollectionTypeTest extends FormIntegrationTestCase
{
    private const DATA_CLASS = 'stdClass';

    private ProductShippingOptionsCollectionType $formType;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new ProductShippingOptionsCollectionType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'entry_type' => ProductShippingOptionsType::class,
                'show_form_when_empty' => false,
                'entry_options' => [
                    'data_class' => self::DATA_CLASS
                ],
                'check_field_name' => null
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductShippingOptionsCollectionType::NAME, $this->formType->getBlockPrefix());
    }
}
