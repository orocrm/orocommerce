<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for product sku
 */
class SkuRegex extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.sku.not_match_regex';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_product.validator_constraints.sku_regex_validator';
    }
}
