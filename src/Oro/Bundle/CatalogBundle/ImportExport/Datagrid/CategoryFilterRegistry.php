<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Datagrid;

/**
 * Category filter registry implementation
 */
class CategoryFilterRegistry implements CategoryFilterRegistryInterface
{
    private array $filters = [];

    #[\Override]
    public function add(CategoryFilterInterface $filter): void
    {
        $this->filters[$filter->getName()] = $filter;
    }

    #[\Override]
    public function get(string $name): CategoryFilterInterface
    {
        return $this->filters[$name] ?? $this->filters[self::DEFAULT_NAME];
    }
}
