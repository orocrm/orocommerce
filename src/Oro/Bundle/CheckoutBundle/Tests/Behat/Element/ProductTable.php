<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ConfigurableProductAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

class ProductTable extends Table implements ConfigurableProductAwareInterface
{
    private const PRODUCT_TABLE_ROW_ELEMENT = 'CheckoutProductTableRow';

    #[\Override]
    public function getProductRows(): array
    {
        return $this->getElements(self::PRODUCT_TABLE_ROW_ELEMENT);
    }
}
