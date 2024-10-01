<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryInventoryDecrementFormExtension extends AbstractFallbackCategoryTypeExtension
{
    #[\Override]
    public function getFallbackProperties()
    {
        return [
            'decrementQuantity'
        ];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'decrementQuantity',
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.decrement_inventory.label',
            ]
        );
    }
}
