<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Default Product Variant
 */
class DefaultVariantChoiceType extends AbstractType
{
    public const DEFAULT_VARIANT_FORM_FIELD = 'defaultVariant';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class'    => Product::class,
            'required' => false,
            'multiple' => false,
            'choices'     => function (Options $options) {
                /** @var Product $parentProduct */
                $parentProduct = $options['parentProduct'];
                return $parentProduct->getDefaultVariant() ? [$parentProduct->getDefaultVariant()] : [];
            },
            'parentProduct' => null,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2EntityType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_product_default_variant_choice';
    }
}
