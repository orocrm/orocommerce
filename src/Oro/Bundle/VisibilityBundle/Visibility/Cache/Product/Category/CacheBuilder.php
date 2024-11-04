<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CompositeCacheBuilder;

class CacheBuilder extends CompositeCacheBuilder implements CategoryCaseCacheBuilderInterface
{
    #[\Override]
    public function categoryPositionChanged(Category $category)
    {
        foreach ($this->builders as $builder) {
            if ($builder instanceof CategoryCaseCacheBuilderInterface) {
                $builder->categoryPositionChanged($category);
            }
        }
    }
}
