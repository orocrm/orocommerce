<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;

/**
 * Class for building and updating visibility cache for category subtrees, considering customer and customer
 * group visibility changes.
 */
class VisibilityChangeCategorySubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /**
     * @param Category $category
     * @param int      $visibility
     *
     * @return array|int[] Affected categories id
     */
    public function resolveVisibilitySettings(Category $category, $visibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category);

        /** @var CategoryRepository $repository */
        $repository = $this->registry->getManagerForClass(CategoryVisibilityResolved::class)
            ->getRepository(CategoryVisibilityResolved::class);
        $repository
            ->updateCategoryVisibilityByCategory($childCategoryIds, $visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $this->clearChangedEntities();

        return $categoryIds;
    }

    #[\Override]
    protected function updateCustomerGroupsFirstLevel(Category $category, $visibility)
    {
        $customerGroupIds = $this->getCustomerGroupIdsFirstLevel($category);
        if ($customerGroupIds === null) {
            return [];
        }

        $this->updateCustomerGroupsProductVisibility($category, $customerGroupIds, $visibility);
        $this->updateCustomerGroupsCategoryVisibility($category, $customerGroupIds, $visibility);

        return $customerGroupIds;
    }

    /**
     * Get customers groups with customer visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return array
     */
    protected function getCustomerGroupIdsFirstLevel(Category $category)
    {
        return $this->getCustomerGroupIdsWithFallbackToAll($category);
    }

    #[\Override]
    protected function updateCustomersFirstLevel(Category $category, $visibility)
    {
        $customerIdsForUpdate = $this->getCustomerIdsFirstLevel($category);

        if ($customerIdsForUpdate === null) {
            return [];
        }

        /**
         * Cache updated customer for current category into appropriate section
         */
        $this->customerIdsWithChangedVisibility[$category->getId()] = $customerIdsForUpdate;

        $this->updateCustomersProductVisibility($category, $customerIdsForUpdate, $visibility);
        $this->updateCustomersCategoryVisibility($category, $customerIdsForUpdate, $visibility);

        return $customerIdsForUpdate;
    }

    /**
     * Get customers with customer group visibility fallback to 'Visibility To All' for current category
     *
     * @param Category $category
     * @return array
     */
    protected function getCustomerIdsFirstLevel(Category $category)
    {
        $customerIdsForUpdate = $this->getCustomerIdsWithFallbackToAll($category);
        $customerGroupIdsForUpdate = $this->customerGroupIdsWithChangedVisibility[$category->getId()];
        if (!empty($customerGroupIdsForUpdate)) {
            $customerIdsForUpdate = array_merge(
                $customerIdsForUpdate,
                /**
                 * Get customers with customer visibility fallback to 'Customer Group'
                 * for customer groups with fallback 'Visibility To All'
                 * for current category
                 */
                $this->getCustomerIdsForUpdate($category, $customerGroupIdsForUpdate)
            );
        }

        return $customerIdsForUpdate;
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getCustomerGroupIdsWithFallbackToAll(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass(CustomerGroup::class)
            ->createQueryBuilder();

        /** @var QueryBuilder $subQb */
        $subQb = $this->registry
            ->getManagerForClass(CustomerGroupCategoryVisibility::class)
            ->createQueryBuilder();
        $subQb->select('1')
            ->from(CustomerGroupCategoryVisibility::class, 'customerGroupCategoryVisibility')
            ->join('customerGroupCategoryVisibility.scope', 'scope')
            ->where($qb->expr()->eq('customerGroupCategoryVisibility.category', ':category'))
            ->andWhere('scope.customerGroup = customerGroup.id');

        $qb->select('customerGroup.id')
            ->from(CustomerGroup::class, 'customerGroup')
            ->where($qb->expr()->not($qb->expr()->exists($subQb->getQuery()->getDQL())))
            ->setParameter('category', $category);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    #[\Override]
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNotNull('cv.visibility'));
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    #[\Override]
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNull('cv.visibility'));
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass(ProductVisibilityResolved::class)
            ->createQueryBuilder();

        $qb->update(ProductVisibilityResolved::class, 'pvr')
            ->set('pvr.visibility', ':visibility')
            ->andWhere($qb->expr()->in('IDENTITY(pvr.category)', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds)
            ->setParameter('visibility', $visibility);

        $qb->getQuery()->execute();
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     */
    protected function updateCategoryVisibilityByCategory(array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass(CategoryVisibilityResolved::class)
            ->createQueryBuilder();

        $qb->update(CategoryVisibilityResolved::class, 'cvr')
            ->set('cvr.visibility', ':visibility')
            ->andWhere($qb->expr()->in('IDENTITY(cvr.category)', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds)
            ->setParameter('visibility', $visibility);

        $qb->getQuery()->execute();
    }

    #[\Override]
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            CategoryVisibility::class,
            'cv',
            Join::WITH,
            $qb->expr()->eq('node', 'cv.category')
        );
    }
}
