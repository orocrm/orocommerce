<?php

namespace Oro\Bundle\ProductBundle\Rounding;

use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;
use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Service for quantity rounding.
 */
class QuantityRoundingService extends AbstractRoundingService
{
    #[\Override]
    public function getRoundType()
    {
        return $this->configManager->get('oro_product.unit_rounding_type') ?? self::ROUND_HALF_UP;
    }

    #[\Override]
    public function getPrecision()
    {
        throw new \BadMethodCallException('ProductUnit required to get a precision');
    }

    /**
     * @param float|int $quantity
     * @param Product|null $product
     * @param ProductUnit|null $unit
     * @return float|int
     * @throws InvalidRoundingTypeException
     */
    public function roundQuantity($quantity, ?ProductUnit $unit = null, ?Product $product = null)
    {
        if (!$unit) {
            return $quantity;
        }

        if ($product) {
            $productUnit = $product->getUnitPrecision($unit->getCode());
            if ($productUnit) {
                return $this->round($quantity, $productUnit->getPrecision());
            }
        }

        return $this->round($quantity, $unit->getDefaultPrecision());
    }
}
