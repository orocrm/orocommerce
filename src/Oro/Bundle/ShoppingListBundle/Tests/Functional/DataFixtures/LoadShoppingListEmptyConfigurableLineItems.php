<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;

class LoadShoppingListEmptyConfigurableLineItems extends AbstractShoppingListLineItemsFixture
{
    public const LINE_ITEM_1 = 'shopping_list_configurable_line_item.1';

    protected static array $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.liter',
            'quantity' => 0
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [
            LoadConfigurableProductWithVariants::class,
            LoadShoppingLists::class,
        ]);
    }
}
