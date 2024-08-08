<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\FrontendProductUnitsProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityProvider;

/**
 * Provides methods to get prices with currencies, units and quantities
 * for regular products, configurable products and product variants.
 */
class FrontendProductPricesProvider
{
    private ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler;
    private ProductVariantAvailabilityProvider $productVariantAvailabilityProvider;
    private UserCurrencyManager $userCurrencyManager;
    private ProductPriceFormatter $productPriceFormatter;
    private ProductPriceProviderInterface $productPriceProvider;
    private FrontendProductUnitsProvider $productUnitsProvider;
    private FrontendShoppingListProductUnitsQuantityProvider $shoppingListProvider;

    /** @var array [product id => [formatted price (array), ...], ...] */
    private array $productPrices = [];

    /** @var array [configurable product id => [simple product id => [formatted price (array), ...], ...], ...] */
    private array $variantsPrices = [];

    public function __construct(
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceFormatter $productPriceFormatter,
        ProductPriceProviderInterface $productPriceProvider,
        FrontendProductUnitsProvider $productUnitsProvider,
        FrontendShoppingListProductUnitsQuantityProvider $shoppingListProvider
    ) {
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->productPriceFormatter = $productPriceFormatter;
        $this->productPriceProvider = $productPriceProvider;
        $this->productUnitsProvider = $productUnitsProvider;
        $this->shoppingListProvider = $shoppingListProvider;
    }

    /**
     * @param Product|ProductView $product
     *
     * @return array [formatted price (array), ...]
     */
    public function getByProduct(Product|ProductView $product): array
    {
        $productId = $product->getId();
        $this->prepareAndSetPricesForProduct($productId);

        return $this->getProductPrices($productId);
    }

    /**
     * @param Product $product
     *
     * @return array [simple product id => [formatted price (array), ...], ...]
     */
    public function getVariantsPricesByProduct(Product $product): array
    {
        $productId = $product->getId();
        $this->prepareAndSetPricesForProduct($productId);

        return $this->variantsPrices[$productId] ?? [];
    }

    /**
     * @param ProductView[] $products
     *
     * @return array [product id => [formatted price (array), ...], ...]
     */
    public function getByProducts(array $products): array
    {
        $productIds = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            if (!\array_key_exists($productId, $this->productPrices)) {
                $productIds[] = $productId;
            }
        }
        if ($productIds) {
            $this->setProductsAndVariantsPrices($productIds);
        }

        $productPrices = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            if ($this->productPrices[$productId]) {
                $productPrices[$productId] = $this->getProductPrices($productId);
            }
        }

        return $productPrices;
    }

    /**
     * @param Product|ProductView $product
     *
     * @return array [unit_code => formatted price (array), ...]
     */
    public function getShoppingListPricesByProduct(Product|ProductView $product): array
    {
        $productPrices = $this->getByProduct($product);
        $shoppingLists = $this->shoppingListProvider->getByProduct($product) ?? [];

        $shoppingListPrices = [];
        foreach ($shoppingLists as $shoppingList) {
            $isCurrentShoppingList = $shoppingList['is_current'] ?? false;
            if ($isCurrentShoppingList) {
                foreach ($shoppingList['line_items'] as $lineItem) {
                    $shoppingListPrices[$lineItem['unit']] =
                        $this->findPriceForShoppingListsLineItem($productPrices, $lineItem);
                }
            }
        }

        return $shoppingListPrices;
    }

    /**
     * @param ProductView[] $products
     *
     * @return array [product id => [unit code => formatted price (array)]]
     */
    public function getShoppingListPricesByProducts(array $products): array
    {
        $productsPrices = $this->getByProducts($products);
        $shoppingLists = $this->shoppingListProvider->getByProducts($products);

        $shoppingListPrices = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            $productPrices = $productsPrices[$productId] ?? [];
            $shoppingListsByProduct = $shoppingLists[$productId] ?? [];

            foreach ($shoppingListsByProduct as $shoppingListByProduct) {
                $isCurrentShoppingList = $shoppingListByProduct['is_current'] ?? false;
                if ($isCurrentShoppingList) {
                    foreach ($shoppingListByProduct['line_items'] as $lineItem) {
                        $shoppingListPrices[$productId][$lineItem['unit']] =
                            $this->findPriceForShoppingListsLineItem($productPrices, $lineItem);
                    }
                }
            }
        }

        return $shoppingListPrices;
    }

    public function isShowProductPriceContainer(Product $product): bool
    {
        return
            $product->getType() !== Product::TYPE_CONFIGURABLE
            || $this->isProductHasPrices($product->getId());
    }

    /**
     * @param array $productPrices
     * @param array $lineItem
     *
     * @return array|null formatted price (array)
     */
    private function findPriceForShoppingListsLineItem(array $productPrices, array $lineItem): ?array
    {
        array_walk($productPrices, function (&$productPrice, $key) {
            $productPrice['hasDiscount'] = $key > 0;

            return $productPrice;
        });

        $suitablePrices = array_filter($productPrices, function ($price) use ($lineItem) {
            return $price['unit'] === $lineItem['unit'] && $price['quantity'] <= $lineItem['quantity'];
        });
        $qtyIndexed = array_column($suitablePrices, null, 'quantity');
        krsort($qtyIndexed);
        return reset($qtyIndexed) ?: null;
    }

    /**
     * @param int $productId
     *
     * @return array [formatted price (array), ...]
     */
    private function getProductPrices(int $productId): array
    {
        return $this->productPrices[$productId] ?? [];
    }

    private function prepareAndSetPricesForProduct(int $productId): void
    {
        if (!\array_key_exists($productId, $this->productPrices)) {
            $this->setProductsAndVariantsPrices([$productId]);
        }
    }

    /**
     * @param int[] $productIds
     */
    private function setProductsAndVariantsPrices(array $productIds): void
    {
        $simpleProductIdsMap = $this->productVariantAvailabilityProvider
            ->getSimpleProductIdsGroupedByConfigurable($productIds);
        if ($simpleProductIdsMap) {
            $productIds = array_values(array_unique(array_merge(
                $productIds,
                array_merge(...array_values($simpleProductIdsMap))
            )));
        }

        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $this->scopeCriteriaRequestHandler->getPriceScopeCriteria(),
            $productIds,
            [$this->userCurrencyManager->getUserCurrency()]
        );
        $this->setProductsPrices($productIds, $prices);
        $this->setVariantsPrices($simpleProductIdsMap);
    }

    /**
     * @param int[] $productIds
     * @param array $prices [product id => [ProductPriceInterface, ...], ...]
     */
    private function setProductsPrices(array $productIds, array $prices): void
    {
        $productUnits = $this->productUnitsProvider->getUnitsForProducts($productIds);
        $formattedProductsPricesByUnit = $this->formatProductPrices($prices);
        foreach ($productIds as $productId) {
            $productPrices = [];
            $units = $productUnits[$productId] ?? [];
            $formattedProductPrices = $formattedProductsPricesByUnit[$productId] ?? [];
            foreach ($formattedProductPrices as $formattedProductPrice) {
                if (\in_array($formattedProductPrice['unit'], $units, true)) {
                    $productPrices[] = $formattedProductPrice;
                }
            }
            $this->productPrices[$productId] = $productPrices;
        }
    }

    /**
     * @param array $simpleProductIdsMap [configurable product id => [simple product id, ...], ...]
     */
    private function setVariantsPrices(array $simpleProductIdsMap): void
    {
        foreach ($simpleProductIdsMap as $configurableId => $simpleProductIds) {
            foreach ($simpleProductIds as $simpleProductId) {
                if ($this->productPrices[$simpleProductId]) {
                    $this->variantsPrices[$configurableId][$simpleProductId] = $this->productPrices[$simpleProductId];
                }
            }
        }
    }

    /**
     * @param array $prices [product id => [ProductPriceInterface, ...], ...]
     *
     * @return array [product id => ['{unit}_{quantity}' => formatted price (array), ...], ...]
     */
    private function formatProductPrices(array $prices): array
    {
        $productsPricesByUnit = [];
        foreach ($prices as $productId => $productPrices) {
            /** @var ProductPriceInterface $price */
            foreach ($productPrices as $price) {
                $productsPricesByUnit[$productId][$price->getUnit()->getCode()][] = $price;
            }
        }

        return $this->productPriceFormatter->formatProducts($productsPricesByUnit);
    }

    private function isProductHasPrices(int $productId): bool
    {
        $this->prepareAndSetPricesForProduct($productId);

        return !empty($this->productPrices[$productId]);
    }
}
