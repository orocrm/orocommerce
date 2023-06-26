<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base methods for line item controllers
 */
abstract class AbstractLineItemController extends AbstractController
{
    protected function getSuccessResponse(ShoppingList $shoppingList, Product $product, string $message): array
    {
        return [
            'successful' => true,
            'message' => $this->getSuccessMessage($shoppingList, $message),
            'product' => $this->getProductResponseData($product),
            'shoppingList' => $this->getShoppingListResponseData($shoppingList),
        ];
    }

    protected function getProductResponseData(Product $product): array
    {
        $productShoppingLists = $this->get(ProductShoppingListsDataProvider::class)
            ->getProductUnitsQuantity($product->getId());

        return [
            'id' => $product->getId(),
            'shopping_lists' => $productShoppingLists,
        ];
    }

    protected function getShoppingListResponseData(ShoppingList $shoppingList): array
    {
        return [
            'id' => $shoppingList->getId(),
            'label' => $shoppingList->getLabel()
        ];
    }

    protected function getSuccessMessage(ShoppingList $shoppingList, string $translationKey): string
    {
        $link = $this->get(ShoppingListUrlProvider::class)->getFrontendUrl($shoppingList);
        $label = htmlspecialchars($shoppingList->getLabel());

        return $this->get(TranslatorInterface::class)->trans(
            $translationKey,
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $label)]
        );
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            ProductShoppingListsDataProvider::class,
            ShoppingListUrlProvider::class,
            RouterInterface::class,
            TranslatorInterface::class,
        ]);
    }
}
