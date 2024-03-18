<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBlockConfigInterface;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;

/**
 * Provides products related to a specific product.
 */
class RelatedItemDataProvider
{
    private array $relatedItems = [];

    public function __construct(
        private FinderStrategyInterface $finderStrategy,
        private ProductListBlockConfigInterface $configProvider,
        private UserAgentProviderInterface $userAgentProvider,
        private ManagerRegistry $doctrine,
        private ProductManager $productManager,
        private AclHelper $aclHelper,
        private ProductListBuilder $productListBuilder,
    ) {
    }

    /**
     * @param Product $product
     *
     * @return ProductView[]
     */
    public function getRelatedItems(Product $product): array
    {
        $productId = $product->getId();
        if (!isset($this->relatedItems[$productId])) {
            $this->relatedItems[$productId] = $this->loadRelatedItems($product);
        }

        return $this->relatedItems[$productId];
    }

    public function isSliderEnabled(): bool
    {
        return !$this->isMobile() || $this->isSliderEnabledOnMobile();
    }

    public function isAddButtonVisible(): bool
    {
        return $this->configProvider->isAddButtonVisible();
    }

    private function loadRelatedItems(Product $product): array
    {
        $relatedProductIds = $this->finderStrategy->findIds($product);

        if (!$this->hasMoreThanRequiredMinimum($relatedProductIds)) {
            return [];
        }

        $qb = $this->getProductRepository()
            ->getProductsQueryBuilder($relatedProductIds)
            ->select('p.id')
            ->orderBy('p.id');
        $limit = $this->configProvider->getMaximumItems();
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        $this->productManager->restrictQueryBuilder($qb, []);
        $rows = $this->aclHelper->apply($qb)->getArrayResult();
        if (!$rows || !$this->hasMoreThanRequiredMinimum($rows)) {
            return [];
        }

        return $this->productListBuilder->getProductsByIds(
            $this->configProvider->getProductListType(),
            array_column($rows, 'id')
        );
    }

    private function hasMoreThanRequiredMinimum(array $rows): bool
    {
        return count($rows) !== 0 && count($rows) >= (int)$this->configProvider->getMinimumItems();
    }

    private function isMobile(): bool
    {
        return $this->userAgentProvider->getUserAgent()->isMobile();
    }

    private function isSliderEnabledOnMobile(): bool
    {
        return $this->configProvider->isSliderEnabledOnMobile();
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(Product::class);
    }
}
