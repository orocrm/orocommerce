<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NumericRange;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * This extension adds 'inventoryThreshold' field to product form
 */
class ProductInventoryThresholdFormExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $product = $builder->getData();
        // set category as default fallback
        if (!$product->getInventoryThreshold()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $product->setInventoryThreshold($entityFallback);
        }

        $builder->add(
            'inventoryThreshold',
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.inventory_threshold.label',
                'required' => false,
                'value_options' => [
                    // Here we overwrite settings from system_configuration.yml
                    // for oro_inventory.low_inventory_threshold
                    // because this value can be blank in case of product.
                    // Also constraints are needed both here and in validation.yml to make frontend validation work.
                    'constraints' => [new NumericRange(['min' => -100000000, 'max' => 100000000])]
                ]
            ]
        );
    }
}
