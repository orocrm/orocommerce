<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShoppingListLineItem extends Element implements LineItemInterface
{
    #[\Override]
    public function getProductSKU(): string
    {
        return $this->getElement('ShoppingListLineItemProductSku')->getText();
    }

    public function delete()
    {
        $deleteButton = $this->find('css', '.theme-icon-trash');
        $deleteButton->click();
    }
}
