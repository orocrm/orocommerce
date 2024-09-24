<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Element;

use Oro\Bundle\OrderBundle\Tests\Behat\Element\BackendOrder;

class TaxBackendOrder extends BackendOrder
{
    #[\Override]
    public function getLineItems()
    {
        return $this->getLineItemsFromTable('TaxBackendOrderLineItem');
    }
}
