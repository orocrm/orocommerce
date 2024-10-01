<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether the OrderDiscount entity has either "amount" or "percent" value.
 */
class NotBlankDiscountValue extends Constraint
{
    public $message = 'This value should not be blank.';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
