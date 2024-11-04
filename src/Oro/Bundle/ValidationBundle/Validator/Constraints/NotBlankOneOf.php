<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that one of fields is required.
 */
class NotBlankOneOf extends Constraint
{
    public string $message = 'One of fields: %fields% is required';

    public array $fields = [];

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
