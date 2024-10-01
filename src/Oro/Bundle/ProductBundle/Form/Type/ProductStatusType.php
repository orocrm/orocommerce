<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Product status
 */
class ProductStatusType extends AbstractType
{
    const NAME = 'oro_product_status';

    /**
     * @var  ProductStatusProvider $productStatuses
     */
    protected $productStatusProvider;

    public function __construct(ProductStatusProvider $productStatusProvider)
    {
        $this->productStatusProvider = $productStatusProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->productStatusProvider->getAvailableProductStatuses(),
            'preferred_choices' => [Product::STATUS_DISABLED],
            'duplicate_preferred_choices' => false
        ]);
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
