<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\CustomerPrice;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks that all required filters are provided.
 */
class HandleCustomerPriceFilters implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        $filterValues = $context->getFilterValues();
        if (!$filterValues->getOne('customer')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "customer" filter is required.'));
        }
        if (!$filterValues->getOne('website')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "website" filter is required.'));
        }
        if (!$filterValues->getOne('product')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "product" filter is required.'));
        }
    }
}
