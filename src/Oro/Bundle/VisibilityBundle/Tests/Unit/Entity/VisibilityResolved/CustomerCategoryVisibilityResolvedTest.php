<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\VisibilityResolved;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CustomerCategoryVisibilityResolvedTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var CustomerCategoryVisibilityResolved */
    private $customerCategoryVisibilityResolved;

    /** @var Category */
    private $category;

    /** @var Scope */
    private $scope;

    #[\Override]
    protected function setUp(): void
    {
        $this->category = new Category();
        $this->scope = new Scope();

        $this->customerCategoryVisibilityResolved = new CustomerCategoryVisibilityResolved(
            $this->category,
            $this->scope
        );
    }

    public function testGettersAndSetters()
    {
        $this->assertPropertyAccessors(
            $this->customerCategoryVisibilityResolved,
            [
                ['visibility', 0],
                ['sourceCategoryVisibility', new CustomerCategoryVisibility()],
                ['source', BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE],
            ]
        );
    }

    public function testGetScope()
    {
        $this->assertEquals($this->scope, $this->customerCategoryVisibilityResolved->getScope());
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->customerCategoryVisibilityResolved->getCategory());
    }
}
