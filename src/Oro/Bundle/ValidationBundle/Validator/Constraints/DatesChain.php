<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Dates chain validator constraint
 */
class DatesChain extends Constraint implements \JsonSerializable
{
    /**
     * @var string
     */
    public $message = '{{ later }} date should follow after {{ earlier }}';

    /**
     * @var array
     */
    public $chain = [];

    /**
     * @return string
     */
    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->message,
            'chain' => $this->chain
        ];
    }
}
