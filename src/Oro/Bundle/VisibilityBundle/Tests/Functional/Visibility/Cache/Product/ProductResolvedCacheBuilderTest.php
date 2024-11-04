<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;

/**
 * @group CommunityEdition
 */
class ProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    #[\Override]
    public function buildCacheDataProvider(): array
    {
        return [
            [
                'expectedStaticCount' => 3,
                'expectedCategoryCount' => 0,
            ],
        ];
    }

    #[\Override]
    protected function getRepository(): ProductRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ProductVisibilityResolved::class);
    }

    #[\Override]
    protected function getCacheBuilder(): CacheBuilderInterface
    {
        return self::getContainer()->get(
            'oro_visibility.visibility.cache.product.product_resolved_cache_builder'
        );
    }
}
