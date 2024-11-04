<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadProductCategoryDemoData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Loads fallback fields data.
 */
class LoadFallbackFieldsData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    private const FALLBACK_FIELDS = [
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
        'manageInventory',
        LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION,
        'inventoryThreshold',
        LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION,
        'decrementQuantity',
        'backOrder',
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadProductCategoryDemoData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository(Category::class);

        /** @var Category[] $categories */
        $categories = $categoryRepository->findAll();
        foreach ($categories as $category) {
            $this->addFallbacksToEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID, $category);
        }

        /** @var Product[] $products */
        $products = $manager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $category = $categoryRepository->findOneByProduct($product);
            if ($category) {
                $this->addFallbacksToEntity($manager, CategoryFallbackProvider::FALLBACK_ID, $product);
            } else {
                $this->addFallbacksToEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID, $product);
            }
        }

        $manager->flush();
    }

    private function addFallbacksToEntity(ObjectManager $manager, string $fallbackId, object $entity): object
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach (self::FALLBACK_FIELDS as $fallbackField) {
            $fallbackEntity = $this->createFallbackEntity($manager, $fallbackId);
            $accessor->setValue($entity, $fallbackField, $fallbackEntity);
        }

        return $entity;
    }

    private function createFallbackEntity(ObjectManager $manager, string $fallbackId): EntityFieldFallbackValue
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setFallback($fallbackId);
        $manager->persist($entityFallback);

        return $entityFallback;
    }
}
