<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Default category filter implementation
 */
class DefaultCategoryFilter implements CategoryFilterInterface
{
    #[\Override]
    public function getName(): string
    {
        return CategoryFilterRegistryInterface::DEFAULT_NAME;
    }

    #[\Override]
    public function getFieldName(QueryBuilder $qb): string
    {
        $alias = QueryBuilderUtil::getSingleRootAlias($qb);
        return sprintf('%s.category', $alias);
    }

    #[\Override]
    public function prepareQueryBuilder(QueryBuilder $qb): void
    {
    }
}
