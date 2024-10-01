<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencySelectionTypeStub extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'additional_currencies' => null,
                'compact'               => false,
                'currencies_list'       => null,
                'full_currency_list'    => true,
                'full_currency_name'    => false,
            ]
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return CurrencySelectionType::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CurrencyType::class;
    }
}
