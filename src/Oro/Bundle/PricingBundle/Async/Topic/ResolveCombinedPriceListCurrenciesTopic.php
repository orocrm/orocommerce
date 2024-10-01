<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Updates supported currencies for combined price lists by price lists.
 */
class ResolveCombinedPriceListCurrenciesTopic extends AbstractTopic
{
    public const NAME = 'oro_pricing.price_lists.cpl.resolve_currencies';

    #[\Override]
    public static function getName(): string
    {
        return static::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Updates supported currencies for combined price lists by price lists.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', 'array');
    }
}
