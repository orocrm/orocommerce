<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceTypeSelectorType extends AbstractType
{
    const NAME = 'oro_pricing_price_type';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    'oro.pricing.price_type.unit' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
                    'oro.pricing.price_type.bundled' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
                ]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
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
}
