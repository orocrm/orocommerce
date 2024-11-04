<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSelectTypeStub extends AbstractType
{
    #[\Override]
    public function getBlockPrefix(): string
    {
        return ProductSelectType::NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'oro_product_visibility_limited',
            'data_parameters' => [],
            'class' => 'Oro\Bundle\ProductBundle\Entity\Product',
            'choice_label' => 'sku',
            'create_enabled' => true,
            'configs' => [
                'placeholder' => null,
            ],
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
