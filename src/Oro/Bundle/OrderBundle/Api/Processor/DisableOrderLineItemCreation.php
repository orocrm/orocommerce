<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Disables "create" action for an order line item resource if it is executed as a master request.
 */
class DisableOrderLineItemCreation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isMainRequest()) {
            throw new AccessDeniedException(
                'Use API resource to create an order. An order line item can be created only together with an order.'
            );
        }
    }
}
