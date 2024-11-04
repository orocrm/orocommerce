<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ConfigurableProductTableRowAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class OrderLineItem extends Element implements LineItemInterface, ConfigurableProductTableRowAwareInterface
{
    #[\Override]
    public function clickProductLink(): void
    {
        $this->getElement('Frontend Order Line Item Product View Link')->click();
    }

    #[\Override]
    public function isRowContainingAttributes(array $attributeLabels): bool
    {
        foreach ($attributeLabels as $attributeLabel) {
            $attributeElement =
                $this->findElementContains('Frontend Order Line Item Product Attribute', $attributeLabel);

            if (!$attributeElement->isValid()) {
                return false;
            }
        }

        return true;
    }

    #[\Override]
    public function getProductSKU(): string
    {
        return $this->getElement('OrderLineItemProductSku')->getText();
    }
}
