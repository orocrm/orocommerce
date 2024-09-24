<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that there are no duplicated price lists.
 */
class UniquePriceList extends Constraint
{
    public string $message = 'oro.pricing.validators.price_list.unique_price_list.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
