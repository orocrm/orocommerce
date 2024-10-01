<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Moves price lists that require the price lists with schedules update from shared data to the current context.
 * It is done to avoid loading of the {@see \Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListsContainSchedule}
 * processor and services are used by it when there are no price lists that require the price lists
 * with schedules update.
 */
class MovePriceListsToUpdatePriceListsContainScheduleToContext implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        if (!$context instanceof CustomizeFormDataContext || $context->isPrimaryEntityRequest()) {
            UpdatePriceListsContainSchedule::movePriceListsToUpdatePriceListsContainScheduleToContext($context);
        }
    }
}
