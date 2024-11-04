<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Basic functionality when product single unit mode is disabled.
 */
class BasicProductUnitFieldsSettings implements ProductUnitFieldsSettingsInterface
{
    public function __construct(
        private DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function isProductUnitSelectionVisible(Product $product): bool
    {
        return true;
    }

    #[\Override]
    public function isProductPrimaryUnitVisible(?Product $product = null): bool
    {
        return true;
    }

    #[\Override]
    public function isAddingAdditionalUnitsToProductAvailable(?Product $product = null): bool
    {
        return true;
    }

    #[\Override]
    public function getAvailablePrimaryUnitChoices(?Product $product = null): array
    {
        return $this->doctrineHelper->getEntityRepository(ProductUnit::class)->findAll();
    }
}
