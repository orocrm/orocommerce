<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Product type
 */
class ProductTypeType extends AbstractType
{
    const NAME = 'oro_product_type';

    /**
     * @var ProductTypeProvider
     */
    private $provider;

    public function __construct(ProductTypeProvider $provider)
    {
        $this->provider = $provider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->provider->getAvailableProductTypes(),
            'preferred_choices' => [Product::TYPE_SIMPLE],
            'duplicate_preferred_choices' => false
        ));
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
