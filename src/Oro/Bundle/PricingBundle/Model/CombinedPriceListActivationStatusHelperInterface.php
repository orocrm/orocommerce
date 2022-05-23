<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;

/**
 * Provide information about activation status for a given Combined Price List.
 */
interface CombinedPriceListActivationStatusHelperInterface
{
    public function isReadyForBuild(CombinedPriceList $cpl): bool;

    /**
     * @deprecated
     */
    public function getActivateDate(): \DateTime;
}
