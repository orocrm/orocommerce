<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Moves customers that require the price list relations update from shared data to the current context.
 * It is done to avoid loading of the {@see \Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListCustomers}
 * processor and services are used by it when there are no customers that require the price list relations update.
 */
class MovePriceListCustomersToUpdateToContext implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        if (!$context instanceof CustomizeFormDataContext || $context->isPrimaryEntityRequest()) {
            UpdatePriceListCustomers::moveCustomersToUpdatePriceListsToContext($context);
        }
    }
}
